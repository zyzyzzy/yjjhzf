<?php
/**
 * Created by 汪桂芳.
 * User: pq
 * Date: 2019/01/03
 * Time: 11:40
 */

namespace User\Controller;

use User\Model\SettleconfigModel;
use User\Model\DaifuModel;
use User\Model\SettledateModel;
use User\Model\UserbankcardModel;

//2019-4-9 任梦龙：用session
class UserSettleController extends UserCommonController
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 结算设置
     */
    //读取用户的结算设置
    public function settleConfig()
    {
        $config = $this->getUserSettleConfig(session('user_info.id'));
        $config['status'] = $config['status'] == 1 ? '开启' : '关闭';
        $config['auto_type'] = $config['auto_type'] == 1 ? '自动提款' : '手动提款';
        $config['deduction_type'] = $config['deduction_type'] == 1 ? '外扣' : '内扣';
        $config['daifu'] = M('daifu')->where('id=' . $config['daifu_id'])->getField('zh_payname');
        $config['account'] = M('payapiaccount')->where('id=' . $config['account_id'])->getField('bieming');
        $config['settle_feilv'] = $config['settle_feilv'] > 0 ? (($config['settle_feilv']) * 100) . '%' : 0;
        $this->assign('config', $config);
        $this->display();
    }

    /**
     * 结算申请
     */
    //选择结算类型页面
    public function settleApply()
    {
        $this->assign('user_id', session('user_info.id'));
        $this->display();
    }

    //结算申请状态判断(已不用,可删除)
    public function applyStatus()
    {
        $user_id = I('user_id');
        $type = I('type');

        //判断是否能结算
        $this->judgeSettleConfig($user_id);

        $return['status'] = 'ok';
        $return['usercode'] = M('user')->where('id=' . $user_id)->getField('usercode');
        $return['type'] = $type;
        $this->ajaxReturn($return, 'json');
    }

    //单笔结算页面
    public function applyInfo()
    {
        $user_id = session('user_info.id');
        $this->assign('user_id', $user_id);

        //查询用户结算配置
        $settle_config = $this->getUserSettleConfig($user_id);
        $settle_config['settle_feilv_info'] = ($settle_config['settle_feilv'] * 100) . '%';
        $this->assign('settle_config', $settle_config);

        //查询今日已结算金额,得到今日还可以结算的余额
        $date = date('Y-m-d');
        $where['userid'] = $user_id;
        $where['applytime'] = [['gt', $date . ' 00:00:00'], ['lt', $date . ' 23:59:59']];
        $sum_order_money = M('settle')->where($where)->sum('ordermoney');
        $can_settle_money = $settle_config['day_maxmoney'] - $sum_order_money;
        $this->assign('can_settle_money', $can_settle_money);

        //查询今日已结算次数,得到今日还可以结算的次数
        $sum_order_count = M('settle')->where($where)->count();
        $can_settle_count = $settle_config['day_maxnum'] - $sum_order_count;
        $this->assign('can_settle_count', $can_settle_count);

        //查询用户代付通道信息
        $daifu = DaifuModel::getDaifuInfo($settle_config['daifu_id']);
        $this->assign('daifu', $daifu);

        //查询用户的可用余额
        $money = M('usermoney')->where('userid=' . $user_id)->getField('money');
        $money = substr($money, 0, strlen($money) - 2);
        $money = $money ? $money : 0;
        $this->assign('money', $money);

        //查询用户所有启用的银行卡
        $bankcard = UserbankcardModel::getUserBanks($user_id);
        foreach ($bankcard as $k => $v) {
            $bankcard[$k]['banknumber'] = '****' . substr($v['banknumber'], -4);
        }
        $this->assign('bankcard', $bankcard);

        $this->display();
    }

    //计算手续费
    public function calculationFee()
    {
        $ordermoney = I('ordermoney');//结算金额
        $money = I('money');//用户可用余额
        $deduction_type = I('deduction_type');//手续费扣款方式 0:内扣 1:外扣
        $settle_feilv = I('settle_feilv');//结算费率
        $settle_min_feilv = I('settle_min_feilv');//单笔最低手续费

        //计算手续费
        $fee = $ordermoney * $settle_feilv;
        if ($fee < $settle_min_feilv) {
            $fee = $settle_min_feilv;
        }

        //判断余额是否足够
        if ($deduction_type == 1) {
            //外扣,保证结算金额+手续费<可用余额,到账金额为结算金额
            if (($fee + $ordermoney) > $money) {
                $this->ajaxReturn(['status' => 'no', 'msg' => '结算金额加手续费超过可用余额,结算金额为: ' . $ordermoney . ' 元时,手续费为: ' . $fee . ' 元']);
            }
            $arrival_money = $ordermoney;
        } else {
            //内扣,结算金额可以跟可用余额相等,到账金额为结算金额-手续费
            $arrival_money = $ordermoney - $fee;
        }
        $this->ajaxReturn(['status' => 'ok', 'fee' => $fee, 'arrival_money' => $arrival_money]);
    }

    //单笔结算提交程序
    //2019-4-11 rml：更改操作记录方法
    public function applyConfirm()
    {
        $user_id = session('user_info.id');
        $ordermoney = I('ordermoney');//申请结算金额
        $paypassword = I('paypassword');//支付密码
        $bankcard_id = I('bankcard_id');//银行卡id

        //判断用户id是否为空
        if (!$user_id) {
            $this->addUserOperate('用户申请单笔结算：参数错误');
            $this->ajaxReturn(['status' => 'no', 'msg' => '参数错误,请稍后重试']);
//            $this->operateReturn($user_id,'用户申请单笔结算：参数错误','no','参数错误,请稍后重试');
        }

        //判断结算金额是否为空
        if ($ordermoney <= 0) {
            $this->addUserOperate('用户申请单笔结算：输入结算金额为空');
            $this->ajaxReturn(['status' => 'no', 'msg' => '请输入结算金额']);
//            $this->operateReturn($user_id,'用户申请单笔结算：输入结算金额为空','no','请输入结算金额');
        }

        //判断支付密码是否为空
        if ($paypassword == '') {
            $this->addUserOperate('用户申请单笔结算：输入支付密码为空');
            $this->ajaxReturn(['status' => 'no', 'msg' => '请输入支付密码']);
//            $this->operateReturn($user_id,'用户申请单笔结算：输入支付密码为空','no','请输入支付密码');
        }

        //判断支付密码是否正确
        $count = M('userpassword')->where("userid=" . $user_id . " and manage_pwd='" . md5($paypassword) . "'")->count();
        if ($count <= 0) {
            $this->addUserOperate('用户申请单笔结算：支付密码输入错误');
            $this->ajaxReturn(['status' => 'no', 'msg' => '支付密码错误,请重新输入']);
//            $this->operateReturn($user_id,'用户申请单笔结算：支付密码输入错误','no','支付密码错误,请重新输入');
        }

        //判断结算状态等配置
        $this->judgeSettleConfig($user_id);//再一次判断是为了防止用户长时间停留在结算的页面,而这期间结算配置有所更改

        //查询用户所有启用的银行卡
        $bankcard = UserbankcardModel::getUserBanks($user_id);
        if (!$bankcard) {
            $this->addUserOperate('用户申请单笔结算：申请结算失败,原因是没有可用的银行卡');
            $this->ajaxReturn(['status' => 'no', 'msg' => '您还没有可用的银行卡,请先去账号管理中完善结算银行信息']);
//            $this->operateReturn($user_id,'用户申请单笔结算：申请结算失败,原因是没有可用的银行卡', 'no','您还没有可用的银行卡,请先去账号管理中完善结算银行信息');
        }

        //查询用户结算配置
        $settle_config = $this->getUserSettleConfig($user_id);
        $min_money = $settle_config['min_money'];//单笔最小结算金额
        $max_money = $settle_config['max_money'];//单笔最大结算金额
        $deduction_type = $settle_config['deduction_type'];//手续费扣款方式
        $settle_feilv = $settle_config['settle_feilv'];//费率
        $settle_min_feilv = $settle_config['settle_min_feilv'];//单笔最低手续费
        $daifu_id = $settle_config['daifu_id'];//代付通道
        $account_id = $settle_config['account_id'];//代付账号

        //查询今日已结算金额,得到今日还可以结算的余额
        $date = date('Y-m-d');
        $where['userid'] = $user_id;
        $where['applytime'] = [['gt', $date . ' 00:00:00'], ['lt', $date . ' 23:59:59']];
        $sum_order_money = M('settle')->where($where)->sum('ordermoney');
        $can_settle_money = $settle_config['day_maxmoney'] - $sum_order_money;

        //查询今日已结算次数,得到今日还可以结算的次数
        $sum_order_count = M('settle')->where($where)->count();
        $can_settle_count = $settle_config['day_maxnum'] - $sum_order_count;

        //查询代付通道信息
        $daifu = M('daifu')->where('id=' . $daifu_id)->find();

        //查询账号结算成本费率  (有一个问题:现在指定了通道和账号,实际提款时这个通道或账号没钱了怎么办,换账号涉及到成本费率的问题,换通道涉及到交易费率的问题)
        $settle_cbfeilv = M('payapiaccount')->where('id=' . $account_id)->getField('settle_cbfeilv');
        $settle_min_cbfeilv = M('payapiaccount')->where('id=' . $account_id)->getField('settle_min_cbfeilv');
        //计算成本手续费
        $settle_cbfee = $ordermoney * $settle_cbfeilv;
        if ($settle_cbfee < $settle_min_cbfeilv) {
            $settle_cbfee = $settle_min_cbfeilv;
        }

        //查询用户余额
        $money = M('usermoney')->where('userid=' . $user_id)->getField('money');//用户余额

        //判断单笔最小金额和单笔最大金额
        if ($ordermoney < $min_money || $ordermoney > $max_money) {
            $this->addUserOperate('用户申请单笔结算：申请结算的金额超出单笔结算金额范围');
            $this->ajaxReturn(['status' => 'no', 'msg' => '申请结算的金额超出单笔结算金额范围']);
//            $this->operateReturn($user_id,'用户申请单笔结算：申请结算的金额超出单笔结算金额范围', 'no','申请结算的金额超出单笔结算金额范围');
        }

        //判断结算金额是否大于可用余额
        if ($ordermoney > $money) {
            $this->addUserOperate('用户申请单笔结算：申请结算的金额超出可用余额');
            $this->ajaxReturn(['status' => 'no', 'msg' => '申请结算的金额超出可用余额']);
//            $this->operateReturn($user_id,'用户申请单笔结算：申请结算的金额超出可用余额', 'no','申请结算的金额超出可用余额');
        }

        //判断结算金额是否大于今日结算剩余额度
        if ($ordermoney > $can_settle_money) {
            $this->addUserOperate('用户申请单笔结算：结算金额超过今日结算剩余额度');
            $this->ajaxReturn(['status' => 'no', 'msg' => '结算金额超过今日结算剩余额度,您今天结算剩余额度为: ' . $can_settle_money . ' 元']);
//            $this->operateReturn($user_id,'用户申请单笔结算：结算金额超过今日结算剩余额度', 'no','结算金额超过今日结算剩余额度,您今天结算剩余额度为: '.$can_settle_money.' 元');
        }

        //判断结算次数是否大于0
        if ($can_settle_count <= 0) {
            $this->addUserOperate('用户申请单笔结算：今日结算次数已达上限');
            $this->ajaxReturn(['status' => 'no', 'msg' => '今日结算次数已达上限,请明日再试']);
//            $this->operateReturn($user_id,'用户申请单笔结算：今日结算次数已达上限', 'no','今日结算次数已达上限,请明日再试');
        }

        //查询用户选择的结算银行卡信息
        $settle_bankcard = M('userbankcard')->where('id=' . $bankcard_id)->find();

        //判断当前银行卡状态
        if ($settle_bankcard['status'] != 1) {
            $this->addUserOperate('用户申请单笔结算：申请结算的银行卡不可用');
            $this->ajaxReturn(['status' => 'no', 'msg' => '申请结算的银行卡不可用,请重新选择银行卡']);
//            $this->operateReturn($user_id,'用户申请单笔结算：申请结算的银行卡不可用', 'no','申请结算的银行卡不可用,请重新选择银行卡');
        }

        //计算手续费
        $fee = $ordermoney * $settle_feilv;
        if ($fee < $settle_min_feilv) {
            $fee = $settle_min_feilv;
        }

        //判断余额是否足够
        if ($deduction_type == 1) {
            //外扣,保证结算金额+手续费<可用余额,到账金额为结算金额,用户实际扣除金额为结算金额+手续费
            if (($fee + $ordermoney) > $money) {
                $this->addUserOperate('用户申请单笔结算：结算金额加手续费超过可用余额');
                $this->ajaxReturn(['status' => 'no', 'msg' => '结算金额加手续费超过可用余额,结算金额为: ' . $ordermoney . ' 元时,手续费为: ' . $fee . ' 元']);
//                $this->operateReturn($user_id,'用户申请单笔结算：结算金额加手续费超过可用余额', 'no','结算金额加手续费超过可用余额,结算金额为: '.$ordermoney.' 元时,手续费为: '.$fee.' 元');
            }
            $arrival_money = $ordermoney;
            $cut_money = $fee + $ordermoney;
        } else {
            //内扣,结算金额可以跟可用余额相等,到账金额为结算金额-手续费,用户实际扣除金额为结算金额
            $arrival_money = $ordermoney - $fee;
            $cut_money = $ordermoney;
        }

        //全部通过后可以开始存入settle数据库表了
        //查询商户号
        $memberid = M('secretkey')->where('userid=' . $user_id)->getField('memberid');
        $data['ordernumber'] = date('YmdHis') . $memberid . rand(1000, 9999);
        $data['userid'] = $user_id;
        $data['memberid'] = $memberid;
        $data['bankname'] = $settle_bankcard['bankname'];
        $data['bankzhiname'] = $settle_bankcard['bankzhiname'];
        $data['banknumber'] = $settle_bankcard['banklianhanghao'];
        $data['bankcode'] = '';
        $data['bankcardnumber'] = $settle_bankcard['banknumber'];
        $data['bankusername'] = $settle_bankcard['bankmyname'];
        $data['identitynumber'] = $settle_bankcard['shenfenzheng'];
        $data['phonenumber'] = $settle_bankcard['tel'];
        $data['province'] = $settle_bankcard['banksheng'];
        $data['city'] = $settle_bankcard['bankshi'];
        $data['applytime'] = date('Y-m-d H:i:s');
        $data['type'] = 0;//全部为T0结算,没有分类了
        $data['status'] = 0;
        $data['ordermoney'] = $ordermoney;
        $res = M('settle')->add($data);
        if ($res) {
            //存入settlemoney表
            $data_money['ordernumber'] = $data['ordernumber'];
            $data_money['ordermoney'] = $ordermoney;
            $data_money['traderate'] = $settle_feilv;
            $data_money['moneytrade'] = $fee;
            $data_money['costrate'] = $settle_cbfeilv;
            $data_money['moneycost'] = $settle_cbfee;
            $data_money['profit'] = $fee - $settle_cbfee;
            $data_money['money'] = $arrival_money;//有问题:这个到账金额是根据内扣和外扣计算的到账金额,还是直接用订单金额减去手续费
            $data_money['shangjiaid'] = $daifu['payapishangjiaid'];
            $data_money['daifuid'] = $daifu_id;
            $data_money['accountid'] = $account_id;
            $data_money['deduction_type'] = $deduction_type;
            M('settlemoney')->add($data_money);

            //存入moneychange表
            $data_change['userid'] = $user_id;
            $data_change['oldmoney'] = $money;
            $data_change['changemoney'] = '-' . $cut_money;
            $data_change['nowmoney'] = $money - $cut_money;
            $data_change['datetime'] = date('Y-m-d H:i:s');
            $data_change['transid'] = $data['ordernumber'];
            $data_change['payapiid'] = $daifu_id;
            $data_change['accountid'] = $account_id;
            $data_change['changetype'] = 5;
            $data_change['remarks'] = '用户申请单笔结算';
            M('moneychange')->add($data_change);

            //修改usermoney表
            $data_usermoney['money'] = $data_change['nowmoney'];
            M('usermoney')->where('userid=' . $user_id)->save($data_usermoney);
            $this->addUserOperate('用户申请单笔结算：申请结算成功');
            $this->ajaxReturn(['status' => 'ok', 'msg' => '结算申请成功']);
//            $this->operateReturn($user_id,'用户申请单笔结算：申请结算成功','ok','结算申请成功');
        } else {
            $this->addUserOperate('用户申请单笔结算：申请结算失败');
            $this->ajaxReturn(['status' => 'no', 'msg' => '结算申请失败,请稍后重试']);
//            $this->operateReturn($user_id,'用户申请单笔结算：申请结算失败','no','结算申请失败,请稍后重试');
        }

    }

    //批量结算页面
    public function manySettleApply()
    {
        $user_id = session('user_info.id');
        $this->assign('user_id', $user_id);

        //查询用户结算配置
        $settle_config = $this->getUserSettleConfig($user_id);
        $settle_config['settle_feilv_info'] = ($settle_config['settle_feilv'] * 100) . '%';
        $this->assign('settle_config', $settle_config);

        //查询用户代付通道信息
        $daifu = DaifuModel::getDaifuInfo($settle_config['daifu_id']);
        $this->assign('daifu', $daifu);

        //查询用户的可用余额
        $money = M('usermoney')->where('userid=' . $user_id)->getField('money');
        $money = substr($money, 0, strlen($money) - 2);
        $money = $money ? $money : 0;
        $this->assign('money', $money);
        //2019-4-17 rml：验证码
        $code_type = $this->getCodeType();
        $this->assign('code_type', $code_type);
        $this->display();
    }

    //批量结算提交程序
    //2019-4-17 rml：修改，还没改完
    public function manyApplyConfirm()
    {
        $msg = '用户申请批量结算:';
        $verfiy_code = I('post.verfiy_code', '', 'trim');  //修改数据时的验证码
        $code_type = I('post.code_type', 0, 'intval');  //验证码类型
        $this->checkVerifyCode($verfiy_code, $code_type, $msg);   //验证密码
        $user_id = session('user_info.id');

        //判断用户id是否为空
        if (!$user_id) {
            $this->addUserOperate($msg . '参数错误');
            $this->ajaxReturn(['status' => 'no', 'msg' => '参数错误,请稍后重试']);
        }

        //判断用户结算配置
        $this->judgeSettleConfig($user_id);

        //查询用户结算配置
        $settle_config = $this->getUserSettleConfig($user_id);

        //查询今日已结算金额,得到今日还可以结算的余额
        $date = date('Y-m-d');
        $where['userid'] = $user_id;
        $where['applytime'] = [['gt', $date . ' 00:00:01'], ['lt', $date . ' 23:59:59']];
        $sum_order_money = M('settle')->where($where)->sum('ordermoney');
        $can_settle_money = $settle_config['day_maxmoney'] - $sum_order_money;

        //查询今日已结算次数,得到今日还可以结算的次数
        $sum_order_count = M('settle')->where($where)->count();
        $can_settle_count = $settle_config['day_maxnum'] - $sum_order_count;

        //查询用户余额
        $money = M('usermoney')->where('userid=' . $user_id)->getField('money');//用户余额

        //上传文件
        $date_time = date('YmdHis');
        $save_name = $date_time . $user_id . rand(1000, 9999);
        $upload = new \Think\Upload(); // 实例化上传类
        $upload->maxSize = 2097152; // 设置附件上传大小
        $upload->exts = array('xls'); // 设置附件上传类型
        $upload->rootPath = C('MANYSETTLE_PATH'); // 设置附件上传目录
        $upload->saveName = $save_name;   //文件名
        $upload->subName = date('Y-m-d');   //子目录创建方式，以账号id命名
        // 上传文件
        $info = $upload->uploadOne($_FILES['file']);
        if (!$info) {
            $this->addUserOperate($msg . $upload->getError());
            $this->ajaxReturn(['status' => 'no', 'msg' => $upload->getError()]);
        } else { // 上传成功
            $all_path = C('MANYSETTLE_PATH') . $info['savepath'] . $info['savename'];
            $return_excel = $this->getExcelInfo($user_id, $all_path, $settle_config,$msg);

            //判断总结算金额加手续费是否大于可用余额
            $summoney = $return_excel['summoney'];
            $sumfee = $return_excel['sumfee'];
            if ($summoney > $money) {
                unlink(trim($all_path, '/'));
                $this->addUserOperate($msg . '批量结算总金额超过用户可用余额');
                $this->ajaxReturn(['status' => 'no', 'msg' => '批量结算总金额超过用户可用余额,请修改后重新申请']);
            }
            if ($settle_config['deduction_type'] == 1) {//外扣
                if (($summoney + $sumfee) > $money) {
                    unlink(trim($all_path, '/'));
                    $this->addUserOperate($msg . '批量结算总金额加手续费超过用户可用余额');
                    $this->ajaxReturn(['status' => 'no', 'msg' => '批量结算总金额加手续费超过用户可用余额,请修改后重新申请']);
                }
            }

            //判断总结算次数是否大于可用次数
            $sumcount = $return_excel['sumcount'];
            if ($sumcount > $can_settle_count) {
                unlink(trim($all_path, '/'));
                $this->addUserOperate($msg . '批量结算总条数超过用户今日还剩结算次数');
                $this->ajaxReturn(['status' => 'no', 'msg' => '批量结算总条数超过用户今日还剩结算次数,您今天结算剩余次数为: ' . $can_settle_count . ' 次']);
            }

            //判断结算金额是否大于今日结算剩余额度
            if ($summoney > $can_settle_money) {
                unlink(trim($all_path, '/'));
                $this->addUserOperate($msg . '批量结算总金额超过今日结算剩余额度');
                $this->ajaxReturn(['status' => 'no', 'msg' => '批量结算总金额超过今日结算剩余额度,您今天结算剩余额度为: ' . $can_settle_money . ' 元']);
            }

            //开始插入数据表
            $this->addExcelInfo($user_id, $all_path, $settle_config,$msg);
        }
    }

    //查询excel相关数据
    public function getExcelInfo($user_id, $filePath, $settle_config,$msg)
    {
        $min_money = $settle_config['min_money'];//单笔最小结算金额
        $max_money = $settle_config['max_money'];//单笔最大结算金额
        $settle_feilv = $settle_config['settle_feilv'];//费率
        $settle_min_feilv = $settle_config['settle_min_feilv'];//单笔最低手续费

        vendor("PHPExcel.PHPExcel");

        //建立reader对象
        $PHPReader = new \PHPExcel_Reader_Excel2007();
        if (!$PHPReader->canRead($filePath)) {
            $PHPReader = new \PHPExcel_Reader_Excel5();
            if (!$PHPReader->canRead($filePath)) {
                $this->addUserOperate($msg . '系统错误,表格不存在');
                $this->ajaxReturn(['status' => 'no', 'msg' => '系统错误,表格不存在,请稍后重试']);
            }
        }

        //建立excel对象，此时你即可以通过excel对象读取文件，也可以通过它写入文件
        $PHPExcel = $PHPReader->load($filePath);

        /**读取excel文件中的第一个工作表*/
        $currentSheet = $PHPExcel->getSheet(0);
        /**取得最大的列号*/
        $allColumn = $currentSheet->getHighestColumn();
        /**取得一共有多少行*/
        $allRow = $currentSheet->getHighestRow();

        $summoney = 0;  //总金额
        $sumcount = 0;  //总次数
        $sumfee = 0;  //总手续费

        //循环读取每个单元格的内容。注意行从1开始，列从A开始
        for ($rowIndex = 2; $rowIndex <= $allRow; $rowIndex++) {
            for ($colIndex = 'A'; $colIndex <= $allColumn; $colIndex++) {
                $addr = $colIndex . $rowIndex;
                $cell = $currentSheet->getCell($addr)->getValue();
                if ($cell instanceof PHPExcel_RichText) {     //富文本转换字符串
                    $cell = $cell->__toString();
                }
                if ($colIndex == "K") {
                    //判断单笔最小金额和单笔最大金额
                    if (floatval($cell) < $min_money || floatval($cell) > $max_money) {
                        unlink(trim($filePath, '/'));
                        $this->addUserOperate($msg . '其中一条申请结算的金额超出单笔结算金额范围');
                        $this->ajaxReturn(['status' => 'no', 'msg' => '其中一条申请结算的金额超出单笔结算金额范围']);
                    }
                    //计算手续费
                    $fee = floatval($cell) * $settle_feilv;
                    if ($fee < $settle_min_feilv) {
                        $fee = $settle_min_feilv;
                    }
                    $summoney = $summoney + floatval($cell);//代付总金额
                    $sumfee = $sumfee + floatval($fee);//代付总手续费
                    $sumcount++;//代付总次数
                }
            }
        }
        $data['summoney'] = $summoney;
        $data['sumfee'] = $sumfee;
        $data['sumcount'] = $sumcount;
        return $data;
    }

    //excel插入相关数据表
    public function addExcelInfo($user_id, $filePath, $settle_config,$msg)
    {
        $deduction_type = $settle_config['deduction_type'];//手续费扣款方式
        $settle_feilv = $settle_config['settle_feilv'];//费率
        $settle_min_feilv = $settle_config['settle_min_feilv'];//单笔最低手续费
        $daifu_id = $settle_config['daifu_id'];//代付通道
        $account_id = $settle_config['account_id'];//代付账号

        //查询代付通道信息
        $daifu = M('daifu')->where('id=' . $daifu_id)->find();

        //查询账号结算成本费率
        $settle_cbfeilv = M('payapiaccount')->where('id=' . $account_id)->getField('settle_cbfeilv');
        $settle_min_cbfeilv = M('payapiaccount')->where('id=' . $account_id)->getField('settle_min_cbfeilv');

        vendor("PHPExcel.PHPExcel");

        //建立reader对象
        $PHPReader = new \PHPExcel_Reader_Excel2007();
        if (!$PHPReader->canRead($filePath)) {
            $PHPReader = new \PHPExcel_Reader_Excel5();
            if (!$PHPReader->canRead($filePath)) {
                echo 'no Excel';
                return;
            }
        }

        //建立excel对象，此时你即可以通过excel对象读取文件，也可以通过它写入文件
        $PHPExcel = $PHPReader->load($filePath);

        /**读取excel文件中的第一个工作表*/
        $currentSheet = $PHPExcel->getSheet(0);
        /**取得最大的列号*/
        $allColumn = $currentSheet->getHighestColumn();
        /**取得一共有多少行*/
        $allRow = $currentSheet->getHighestRow();

        //循环读取每个单元格的内容。注意行从1开始，列从A开始
        for ($rowIndex = 2; $rowIndex <= $allRow; $rowIndex++) {

            //订单金额
            $addr = "K" . $rowIndex;
            $cell = $currentSheet->getCell($addr)->getValue();
            if ($cell instanceof PHPExcel_RichText) {     //富文本转换字符串
                $cell = $cell->__toString();
            }
            $ordermoney = floatval($cell);
            //计算手续费
            $fee = $ordermoney * $settle_feilv;
            if ($fee < $settle_min_feilv) {
                $fee = $settle_min_feilv;
            }
            //计算到账金额
            if ($deduction_type == 1) {
                $arrival_money = $ordermoney;
                $cut_money = $fee + $ordermoney;
            } else {
                //内扣,结算金额可以跟可用余额相等,到账金额为结算金额-手续费,用户实际扣除金额为结算金额
                $arrival_money = $ordermoney - $fee;
                $cut_money = $ordermoney;
            }
            //计算成本手续费
            $settle_cbfee = $ordermoney * $settle_cbfeilv;
            if ($settle_cbfee < $settle_min_cbfeilv) {
                $settle_cbfee = $settle_min_cbfeilv;
            }

            $addr = "A" . $rowIndex;
            $cell = $currentSheet->getCell($addr)->getValue();
            if ($cell instanceof PHPExcel_RichText) {     //富文本转换字符串
                $cell = $cell->__toString();
            }
            $bankname = $cell;

            $addr = "B" . $rowIndex;
            $cell = $currentSheet->getCell($addr)->getValue();
            if ($cell instanceof PHPExcel_RichText) {     //富文本转换字符串
                $cell = $cell->__toString();
            }
            $bankzhiname = $cell;

            $addr = "C" . $rowIndex;
            $cell = $currentSheet->getCell($addr)->getValue();
            if ($cell instanceof PHPExcel_RichText) {     //富文本转换字符串
                $cell = $cell->__toString();
            }
            $banklianhanghao = $cell;

            $addr = "D" . $rowIndex;
            $cell = $currentSheet->getCell($addr)->getValue();
            if ($cell instanceof PHPExcel_RichText) {     //富文本转换字符串
                $cell = $cell->__toString();
            }
            $bankcode = $cell;

            $addr = "E" . $rowIndex;
            $cell = $currentSheet->getCell($addr)->getValue();
            if ($cell instanceof PHPExcel_RichText) {     //富文本转换字符串
                $cell = $cell->__toString();
            }
            $bankusername = $cell;

            $addr = "F" . $rowIndex;
            $cell = $currentSheet->getCell($addr)->getValue();
            if ($cell instanceof PHPExcel_RichText) {     //富文本转换字符串
                $cell = $cell->__toString();
            }
            $bankcardnumber = $cell;

            $addr = "G" . $rowIndex;
            $cell = $currentSheet->getCell($addr)->getValue();
            if ($cell instanceof PHPExcel_RichText) {     //富文本转换字符串
                $cell = $cell->__toString();
            }
            $province = $cell;


            $addr = "H" . $rowIndex;
            $cell = $currentSheet->getCell($addr)->getValue();
            if ($cell instanceof PHPExcel_RichText) {     //富文本转换字符串
                $cell = $cell->__toString();
            }
            $city = $cell;

            $addr = "I" . $rowIndex;
            $cell = $currentSheet->getCell($addr)->getValue();
            if ($cell instanceof PHPExcel_RichText) {     //富文本转换字符串
                $cell = $cell->__toString();
            }
            $identitynumber = $cell;

            $addr = "J" . $rowIndex;
            $cell = $currentSheet->getCell($addr)->getValue();
            if ($cell instanceof PHPExcel_RichText) {     //富文本转换字符串
                $cell = $cell->__toString();
            }
            $phonenumber = $cell;

            //查询商户号
            $memberid = M('secretkey')->where('userid=' . $user_id)->getField('memberid');
            $data['ordernumber'] = date('YmdHis') . $memberid . $rowIndex . rand(1000, 9999);
            $data['userid'] = $user_id;
            $data['memberid'] = $memberid;
            $data['bankname'] = $bankname;
            $data['bankzhiname'] = $bankzhiname;
            $data['banknumber'] = $banklianhanghao;
            $data['bankcode'] = $bankcode;
            $data['bankcardnumber'] = $bankcardnumber;
            $data['bankusername'] = $bankusername;
            $data['identitynumber'] = $identitynumber;
            $data['phonenumber'] = $phonenumber;
            $data['province'] = $province;
            $data['city'] = $city;
            $data['applytime'] = date('Y-m-d H:i:s');
            $data['type'] = 0;//全部为T0结算,没有分类了
            $data['status'] = 0;
            $data['ordermoney'] = $ordermoney;
            $res = M('settle')->add($data);
            if ($res) {
                //存入settlemoney表
                $data_money['ordernumber'] = $data['ordernumber'];
                $data_money['ordermoney'] = $ordermoney;
                $data_money['traderate'] = $settle_feilv;
                $data_money['moneytrade'] = $fee;
                $data_money['costrate'] = $settle_cbfeilv;
                $data_money['moneycost'] = $settle_cbfee;
                $data_money['profit'] = $fee - $settle_cbfee;
                $data_money['money'] = $arrival_money;//有问题:这个到账金额是根据内扣和外扣计算的到账金额,还是直接用订单金额减去手续费
                $data_money['shangjiaid'] = $daifu['payapishangjiaid'];
                $data_money['daifuid'] = $daifu_id;
                $data_money['accountid'] = $account_id;
                $data_money['deduction_type'] = $deduction_type;
                M('settlemoney')->add($data_money);

                //存入moneychange表
                $money = M('usermoney')->where('userid=' . $user_id)->getField('money');
                $data_change['userid'] = $user_id;
                $data_change['oldmoney'] = $money;
                $data_change['changemoney'] = '-' . $cut_money;
                $data_change['nowmoney'] = $money - $cut_money;
                $data_change['datetime'] = date('Y-m-d H:i:s');
                $data_change['transid'] = $data['ordernumber'];
                $data_change['payapiid'] = $daifu_id;
                $data_change['accountid'] = $account_id;
                $data_change['changetype'] = 5;
                $data_change['remarks'] = '用户申请批量结算';
                M('moneychange')->add($data_change);

                //修改usermoney表
                $data_usermoney['money'] = $data_change['nowmoney'];
                M('usermoney')->where('userid=' . $user_id)->save($data_usermoney);
            } else {
                unlink(trim($filePath, '/'));
                $this->addUserOperate($msg . '申请结算失败');
                $this->ajaxReturn(['status' => 'no', 'msg' => '批量结算申请失败']);
            }
        }
        unlink(trim($filePath, '/'));
        $this->addUserOperate($msg . '批量结算申请成功');
        $this->ajaxReturn(['status' => 'ok', 'msg' => '批量结算申请成功']);

    }

    //获取用户结算配置
    public function getUserSettleConfig($user_id)
    {
        $settle_config = SettleconfigModel::getSettleConfigInfo($user_id);
        if (!$settle_config || $settle_config['user_type'] == 0) {
            //用户没有结算设置,默认应用系统的结算设置
            $settle_config = SettleconfigModel::getSettleConfigInfo(0);
        }
        return $settle_config;
    }

    //判断用户结算配置
    public function judgeSettleConfig($user_id)
    {
        //查询用户结算配置
        $settle_config = $this->getUserSettleConfig($user_id);

        //判断结算状态
        if ($settle_config['status'] != 1) {
            $this->operateReturn($user_id, '用户申请结算：申请结算失败,原因是结算功能应用已关闭',
                'no', '结算功能应用已关闭,请联系管理员处理');
        }

        //判断结算时间
        $time = date('H:i:s');
        if ($time < $settle_config['day_start'] || $time > $settle_config['day_end']) {
            $this->operateReturn($user_id, '用户申请结算：申请结算失败,原因是当前时间不能申请结算,当前时间为 ' . date('Y-m-d H:i:s') . ',用户可申请的结算时间范围是  ' . $settle_config['day_start'] . ' - ' . $settle_config['day_end'],
                'no', '当前时间不能申请结算 , 您的结算时间范围是  ' . $settle_config['day_start'] . ' - ' . $settle_config['day_end']);
        }

        //判断结算日期
        $date = date('Y-m-d');
        //不能结算的日期:周末+节假日
        //判断今天是否为周末,如果为周末判断是否在排除的日期内
        $w = date('l');
        $t = true;
        if ($w == 'Saturday' || $w == 'Sunday') {
            $t = false;
            //查询所有排除的日期
            $all_remove = SettledateModel::getAllRemove();
            if ($all_remove) {
                foreach ($all_remove as $k => $v) {
                    if ($date == $v['date']) {
                        $t = true;
                    }
                }
            }
        }
        //判断是否在节假日里面
        if ($t) {
            //查询所有的节假日
            $all_holiday = SettledateModel::getAllHoliday();
            if ($all_holiday) {
                foreach ($all_holiday as $key => $val) {
                    if ($date == $val['date']) {
                        $this->operateReturn($user_id, '用户申请结算：申请结算失败,原因是今日为节假日', 'no', '今日为节假日(' . $val['remarks'] . '),结算功能关闭');
                    }
                }
            }
        } else {
            $this->operateReturn($user_id, '用户申请结算：申请结算失败,原因是今日为休息日', 'no', '今日为休息日,结算功能关闭');
        }


        //判断结算金额上限
        $where['userid'] = $user_id;
        $where['applytime'] = [['gt', $date . ' 00:00:01'], ['lt', $date . ' 23:59:59']];
        $sum_order_money = M('settle')->where($where)->sum('ordermoney');
        if ($sum_order_money >= $settle_config['day_maxmoney']) {
            $this->operateReturn($user_id, '用户申请结算：申请结算失败,原因是当日结算金额已达上限',
                'no', '当日结算金额已达上限,请明日再来');
        }

        //判断结算次数上限
        $count_order_money = M('settle')->where($where)->count();
        if ($count_order_money >= $settle_config['day_maxnum']) {
            $this->operateReturn($user_id, '用户申请结算：申请结算失败,原因是当日结算次数已达上限',
                'no', '当日结算次数已达上限,请明日再来');
        }

        //查询用户的可用余额
        $money = M('usermoney')->where('userid=' . $user_id)->getField('money');
        if ($money <= 0) {
            $this->operateReturn($user_id, '用户申请结算：申请结算失败,原因是没有余额',
                'no', '您没有余额,暂时不可以申请结算');
        }

        //查询用户代付通道信息
        $daifu = DaifuModel::getDaifuInfo($settle_config['daifu_id']);
        //判断代付通道状态
        if ($daifu['status'] != 1 || $daifu['del'] == 1) {
            $this->operateReturn($user_id, '用户申请结算：申请结算失败,原因是代付通道已关闭',
                'no', '代付通道已关闭,请联系管理员处理');
        }

        //查询用户代付账号信息
        $account = M('payapiaccount')->where('id=' . $settle_config['account_id'])->field('status')->find();
        //判断代付账号状态
        if ($account['status'] != 1 || $account['del'] == 1) {
            $this->operateReturn($user_id, '用户申请结算：申请结算失败,原因是代付账号已禁用',
                'no', '代付账号已禁用,请联系管理员处理');
        }
    }



    /**
     * 结算记录
     */
    //结算记录表
    public function settleList()
    {
        //查询所有银行
        $allBanks = M('systembank')->select();
        $this->assign('allBanks', $allBanks);

        //查询所有状态
        $status = C('SETTLESTATUS');
        $this->assign('status', $status);

        $this->display();
    }

    //加载结算记录数据
    public function loadSettleList()
    {
        //搜索
        $where = [];
        $i = 1;
        $user_id = session('user_info.id');
        if ($user_id <> "") {
            $where[$i] = "userid = " . $user_id;
            $i++;
        }
        $bankname = trim(I("post.bankname", ""));
        if ($bankname <> "") {
            $where[$i] = "(bankname like '%" . $bankname . "%')";
            $i++;
        }
        $ordernumber = trim(I("post.ordernumber", ""));
        if ($ordernumber <> "") {
            $where[$i] = "(ordernumber like '%" . $ordernumber . "%')";
            $i++;
        }
        $status = I("post.status", "");
        if ($status <> "") {
            $where[$i] = "status = " . $status;
            $i++;
        }
        $apply_start = I("post.apply_start", "");
        if ($apply_start <> "") {
            $where[$i] = "DATEDIFF('" . $apply_start . "',applytime) <= 0";
            $i++;
        }
        $apply_end = I("post.apply_end", "");
        if ($apply_end <> "") {
            $where[$i] = "DATEDIFF('" . $apply_end . "',applytime) >= 0";
            $i++;
        }
        $deal_start = I("post.deal_start", "");
        if ($deal_start <> "") {
            $where[$i] = "DATEDIFF('" . $deal_start . "',dealtime) <= 0";
            $i++;
        }
        $deal_end = I("post.deal_end", "");
        if ($deal_end <> "") {
            $where[$i] = "DATEDIFF('" . $deal_end . "',dealtime) >= 0";
            $i++;
        }
        //2019-01-25汪桂芳添加
        $bankusername = trim(I("post.bankusername", ""));
        if ($bankusername <> "") {
            $where[$i] = "(bankusername like '%" . $bankusername . "%')";
            $i++;
        }

        $phonenumber = trim(I("post.phonenumber", ""));
        if ($phonenumber <> "") {
            $where[$i] = "(phonenumber like '%" . $phonenumber . "%')";
            $i++;
        }

        $identitynumber = trim(I("post.identitynumber", ""));
        if ($identitynumber <> "") {
            $where[$i] = "(identitynumber like '%" . $identitynumber . "%')";
            $i++;
        }

        $bankcardnumber = trim(I("post.bankcardnumber", ""));
        if ($bankcardnumber <> "") {
            $where[$i] = "(bankcardnumber like '%" . $bankcardnumber . "%')";
            $i++;
        }
        $money_start = I("post.money_start", "");
        if ($money_start <> "") {
            $where[$i] = "ordermoney >= " . $money_start;
            $i++;
        }
        $money_end = I("post.money_end", "");
        if ($money_end <> "") {
            $where[$i] = "ordermoney <= " . $money_end;
            $i++;
        }
        //分页
        $ReturnArr = [
            'code' => 0,
            'msg' => '数据加载成功', //响应结果
            'count' => 0, //总页数
            'data' => [
            ]
        ];
        //总页数
        $count = M('settleinfo')->where($where)->order('applytime desc')->count();
        $page = I('post.page');
        $limit = I('post.limit');
        $datalist = M('settleinfo')->where($where)->page($page, $limit)->order('applytime desc')->select();
        foreach ($datalist as $k => $v) {
            //处理订单金额和冻结金额为两位小数点
            $datalist[$k]['ordermoney'] = substr($v['ordermoney'], 0, strlen($v['ordermoney']) - 2);
//            $datalist[$k]['moneytrade'] = substr($v['moneytrade'],0,strlen($v['moneytrade'])-2);
//            $datalist[$k]['money'] = substr($v['money'],0,strlen($v['money'])-2);
        }

        $ReturnArr['count'] = $count;
        $ReturnArr['data'] = $datalist;
        $this->ajaxReturn($ReturnArr, 'json');
    }

    //导出列表
    public function downloadSettleList()
    {
        //搜索
        $where = [];
        $i = 1;
        $user_id = session('user_info.id');
        if ($user_id <> "") {
            $where[$i] = "userid = " . $user_id;
            $i++;
        }
        $bankname = I("get.bankname", "");
        if ($bankname <> "") {
            $where[$i] = "(bankname like '%" . $bankname . "%')";
            $i++;
        }
        $ordernumber = I("get.ordernumber", "");
        if ($ordernumber <> "") {
            $where[$i] = "(ordernumber like '%" . $ordernumber . "%')";
            $i++;
        }
        $status = I("get.status", "");
        if ($status <> "") {
            $where[$i] = "status = " . $status;
            $i++;
        }
        $type = I("get.type", "");
        if ($type <> "") {
            $where[$i] = "type = " . $type;
            $i++;
        }
        $apply_start = I("get.apply_start", "");
        if ($apply_start <> "") {
            $where[$i] = "DATEDIFF('" . $apply_start . "',applytime) <= 0";
            $i++;
        }
        $apply_end = I("get.apply_end", "");
        if ($apply_end <> "") {
            $where[$i] = "DATEDIFF('" . $apply_end . "',applytime) >= 0";
            $i++;
        }
        $deal_start = I("get.deal_start", "");
        if ($deal_start <> "") {
            $where[$i] = "DATEDIFF('" . $deal_start . "',dealtime) <= 0";
            $i++;
        }
        $deal_end = I("get.deal_end", "");
        if ($deal_end <> "") {
            $where[$i] = "DATEDIFF('" . $deal_end . "',dealtime) >= 0";
            $i++;
        }
        $datalist = M('settleinfo')->where($where)->select();
        $status = C('SETTLESTATUS');
        foreach ($datalist as $k => $v) {
            if ($v['type'] == 1) {
                $datalist[$k]['type'] = 'T + 1';
            } else {
                $datalist[$k]['type'] = 'T + 0';
            }
            $datalist[$k]['status'] = $status[$v['status']];
        }

        $title = '交易记录表';
        $menu_zh = array('订单号', '类型', '订单金额', '订单手续费', '到账金额', '银行名称', '银行卡号', '开户名', '申请时间', '处理时间', '状态');
        $menu_en = array('ordernumber', 'type', 'ordermoney', 'moneytrade', 'money', 'bankname', 'bankcardnumber', 'bankusername', 'applytime', 'dealtime', 'status');
        $config = array('RowHeight' => 25, 'Width' => 20);

        DownLoadExcel($title, $menu_zh, $menu_en, $datalist, $config);

    }

    //通过id获取名称
    public function getNameById($table, $where, $field)
    {
        return M($table)->where('id=' . $where)->getField($field);
    }

    //操作：查看
    public function seeSettleInfo()
    {
        $order_info = M('settleinfo')->where('settleid=' . I('settleid'))->find();

        $status = C('SETTLESTATUS');
        $order_info['username'] = $this->getNameById('user', $order_info['userid'], 'username');
        $order_info['payapiname'] = $this->getNameById('daifu', $order_info['daifuid'], 'zh_payname');
        $order_info['accountname'] = $this->getNameById('payapiaccount', $order_info['accountid'], 'bieming');
        $order_info['status'] = $status[$order_info['status']];
        if ($order_info['deduction_type'] == 1) {
            $order_info['deduction_type'] = '外扣';
        } else {
            $order_info['deduction_type'] = '内扣';
        }
        $this->assign('order_info', $order_info);
        $this->display();
    }
}