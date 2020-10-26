<?php
/**
 * 用户--结算设置
 */

namespace User\Controller;

use Think\Controller;
use User\Model\SettleconfigModel;
use User\Model\SecretkeyModel;
use User\Model\DaifuModel;
use User\Model\SettledateModel;
use User\Model\SettleModel;
use User\Model\UserbankcardModel;
use User\Model\UseroperateModel;
use Admin\Model\MoneychangeModel;

//2019-4-2  任梦龙：修改
class UserSettleController extends UserCommonController
{
    //单笔结算参与签名的字符串
    private $oneSettleFields = [
        "userordernumber",          //结算订单号
        "memberid",         //商户号
        "bankname",         //银行名称
        "bankzhiname",          //银行支行
        "bankcode",         //银行编码
        "bankcardnumber",           //银行卡号
        "bankusername",         //银行卡户名
        "identitynumber",           //身份证号
        "phonenumber",          //手机号
        "province",         //省
        "city",         //市
        "ordermoney",
    ];
    //批量结算参与签名的字符串
    private $manySettleFields = [
        "memberid",         //商户号
        "time",
        "content"
    ];

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
        $user_id = session('user_info.id');
        $config = SettleconfigModel::getUserSettleConfig($user_id);
        if ($config) {
            $type = 1;
//            $config = $this->getUserSettleConfig($user_id);
            $config['status'] = $config['status'] == 1 ? '开启' : '关闭';
            $config['auto_type'] = $config['auto_type'] == 1 ? '自动提款' : '手动提款';
            $config['deduction_type'] = $config['deduction_type'] == 1 ? '外扣' : '内扣';
            //2019-4-2 任梦龙：判断代付通道没有的情况下
            if ($config['daifu_id']) {
                $config['daifu'] = M('daifu')->where('id=' . $config['daifu_id'])->getField('zh_payname');
                $config['account'] = M('payapiaccount')->where('id=' . $config['account_id'])->getField('bieming');
            } else {
                $config['daifu'] = '无';
                $config['account'] = '无';
            }
            $config['settle_feilv'] = $config['settle_feilv'] > 0 ? (($config['settle_feilv']) * 100) . '%' : 0;
            $this->assign('config', $config);
        } else {
            $type = 2;
        }
        $this->assign('type', $type);
        $this->display();
    }

    /**
     * 结算申请
     */
    //选择结算类型页面
    public function settleApply()
    {
        $user_id = session('user_info.id');
        $this->assign('user_id', $user_id);
        //2019-5-8 rml：进入总页面获取结算配置,将变量传入，如果没有则不会显示,这么避免每次打开子页面时还要判断
        $settle_config = SettleconfigModel::getUserSettleConfig($user_id);
        $type = $settle_config ? 1 : 2;
        $this->assign('type', $type);
        $this->display();
    }

    //单笔结算页面
    public function applyInfo()
    {
        $user_id = session('user_info.id');
        $this->assign('user_id', $user_id);

        //查询用户结算配置
        //2019-5-7 rml:首先判断用户的，再去判断系统的
        $settle_config = SettleconfigModel::getUserSettleConfig($user_id);
        //判断存在结算设置: $type=1:存在 2=不存在。不存在时页面报错
        if ($settle_config) {
            $type = 1;
            $settle_config['settle_feilv_info'] = ($settle_config['settle_feilv'] * 100) . '%';
            $this->assign('settle_config', $settle_config);

            //查询今日已结算金额,得到今日还可以结算的余额
            $date = date('Y-m-d');
            $where['userid'] = $user_id;
            $where['applytime'] = ['between', [$date . ' 00:00:00', $date . ' 23:59:59']];
            //2019-5-7 rml：问题：还需要判断订单的状态，不可能所有的可用余额都计算进去
//            $sum_order_money = M('settle')->where($where)->sum('ordermoney');
            $sum_order_money = SettleModel::getSumOrderMoney($where);
            $can_settle_money = $settle_config['day_maxmoney'] - $sum_order_money;  //可以在页面做一个判断，如果可用余额不够，则不能点击申请按钮
            $this->assign('can_settle_money', $can_settle_money);

            //查询今日已结算次数,得到今日还可以结算的次数
//            $sum_order_count = M('settle')->where($where)->count();
            $sum_order_count = SettleModel::getOrderCount($where);
            $can_settle_count = $settle_config['day_maxnum'] - $sum_order_count;
            $this->assign('can_settle_count', $can_settle_count);

            //查询用户代付通道信息
            //没有代付通道时的判断
            if ($settle_config['daifu_id']) {
                $daifu_name = DaifuModel::getDaifuName($settle_config['daifu_id']);
            } else {
                $daifu_name = '无';
            }
            $this->assign('daifu_name', $daifu_name);
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
            $code_type = $this->getCodeType();
            $this->assign('code_type', $code_type);
        } else {
            $type = 2;
        }
        $this->assign('type', $type);
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

        //计算手续费:如果计算出的手续费低于单笔的，则用单笔最低手续费
        $fee = $ordermoney * $settle_feilv;
        if ($fee < $settle_min_feilv) {
            $fee = $settle_min_feilv;
        }

        //判断余额是否足够
        //内扣：手续费从提款金额中扣除，余下的为到账金额
        //外扣：手续费从用户总金额中扣除，提款金额即为到账金额
        if ($deduction_type == 1) {
            //外扣,保证结算金额+手续费<可用余额,到账金额为结算金额
            if (($fee + $ordermoney) > $money) {
                $this->ajaxReturn(['status' => 'no', 'msg' => '结算金额加手续费超过可用余额,结算金额为: ' . $ordermoney . ' 元时,手续费为: ' . $fee . ' 元']);
            }
            $arrival_money = $ordermoney;
        } else {
            //内扣,结算金额可以跟可用余额相等,到账金额为结算金额-手续费
            //如果结算金额小于手续费,则不应该结算
            if ($ordermoney < $fee) {
                $this->ajaxReturn(['status' => 'no', 'msg' => '结算金额小于手续费,结算金额为: ' . $ordermoney . ' 元时,手续费为: ' . $fee . ' 元']);
            }
            $arrival_money = $ordermoney - $fee;
        }
        $this->ajaxReturn(['status' => 'ok', 'fee' => $fee, 'arrival_money' => $arrival_money]);
    }

    //单笔结算提交程序
    //2019-5-8 rml：优化
    public function applyConfirm()
    {
        if (!session('user_info.id')) {
            $this->ajaxReturn(['status' => 'no', 'msg' => '用户不存在']);
        }
        $user_id = session('user_info.id');
        $ordermoney = I('post.ordermoney', '','trim');//申请结算金额
        $bankcard_id = I('post.bankcard_id', 0);//银行卡id
        $msg = "用户申请单笔结算：";

        //判断结算的功能开关是否开启
        $website = M('website')->field('all_valve,api_valve,settle_valve')->order('id DESC')->limit(1)->find();
        if ($website['all_valve'] != 0 || $website['api_valve'] != 0 || $website['settle_valve'] != 1) {
            $this->addUserOperate($msg . '结算功能被关闭,请联系管理员');
            $this->ajaxReturn(['status' => 'no', 'msg' => '结算功能被关闭,请联系管理员']);
        }

        //2019-08-23 张杨 添加了结算状态判断
        $user_settle_status = M('settleconfig')->where(['user_id'=>$user_id])->field('user_type,status,day_start,day_end')->find();
        if($user_settle_status["user_type"] == 1){
            if($user_settle_status['status'] == 0){
                $this->addUserOperate($msg . '结算功能被关闭,请联系管理员');
                $this->ajaxReturn(['status' => 'no', 'msg' => '结算功能被关闭,请联系管理员']);
            }else{
                $checkIsBetweenTime = $this->checkIsBetweenTime($user_settle_status['day_start'], $user_settle_status['day_end']);
                if (!$checkIsBetweenTime) {
                    $this->addUserOperate($msg . '非结算时间,请联系管理员');
                    $this->ajaxReturn(['status' => 'no', 'msg' => '非结算时间,请联系管理员']);
                }
            }
        }else{
            $settle = M('settleconfig')->where(['user_id'=>0])->field('status,day_start,day_end')->find();
            if($settle['status'] == 0){
                $this->addUserOperate($msg . '结算功能被关闭,请联系管理员');
                $this->ajaxReturn(['status' => 'no', 'msg' => '结算功能被关闭,请联系管理员']);
            }else{
                $checkIsBetweenTime = $this->checkIsBetweenTime($settle['day_start'], $settle['day_end']);
                if (!$checkIsBetweenTime) {
                    $this->addUserOperate($msg . '非结算时间,请联系管理员');
                    $this->ajaxReturn(['status' => 'no', 'msg' => '非结算时间,请联系管理员']);
                }
            }
        }

        //判断结算金额是否为空
        if ($ordermoney <= 0 || !is_numeric($ordermoney)) {
            $this->addUserOperate($msg . '输入结算金额为空或者格式不对');
            $this->ajaxReturn(['status' => 'no', 'msg' => '输入结算金额为空或者格式不对']);
        }

        //判断有没有结算银行卡
        if (!$bankcard_id) {
            $this->addUserOperate($msg . '没有可用的银行卡');
            $this->ajaxReturn(['status' => 'no', 'msg' => '您还没有可用的银行卡,请先去账号管理中完善结算银行信息']);
        }

        //判断代付通道和账号
        $daifu_id = I('post.daifu_id', 0);
        if (!$daifu_id) {
            $this->addUserOperate($msg . '没有可用的代付通道');
            $this->ajaxReturn(['status' => 'no', 'msg' => '您还没有可用的代付通道,请先联系管理员处理']);
        }
        $account_id = I('post.account_id', 0);
        if (!$account_id) {
            $this->addUserOperate($msg . '没有可用的通道账号');
            $this->ajaxReturn(['status' => 'no', 'msg' => '您还没有可用的通道账号,请先联系管理员处理']);
        }

        //判断验证码
        $verfiy_code = I('post.verfiy_code', '', 'trim');
        $code_type = I('post.code_type', 0, 'intval');
        $this->checkVerifyCode($verfiy_code, $code_type, $msg);

//        //查询用户所有启用的银行卡
//        $bankcard = UserbankcardModel::getUserBanks($user_id);
//        if (!$bankcard) {
//            $this->addUserOperate($msg . '申请结算失败,原因是没有可用的银行卡');
//            $this->ajaxReturn(['status' => 'no', 'msg' => '您还没有可用的银行卡,请先去账号管理中完善结算银行信息']);
//        }

        //查询用户选择的结算银行卡信息
//        $settle_bankcard = M('userbankcard')->where('id=' . $bankcard_id)->find();
        $settle_bankcard = UserbankcardModel::findUserBank($bankcard_id);

//        2019-08-23 张杨 判断一个银行卡信息是否存在
        if(!$settle_bankcard){
            $this->addUserOperate($msg . '银行卡信息不存在');
            $this->ajaxReturn(['status' => 'no', 'msg' => '银行卡信息不存在,请先联系管理员处理']);
        }

        //判断当前银行卡状态:rml：结算银行卡状态在选择时已经判断过
//        if ($settle_bankcard['status'] != 1) {
//            $this->addUserOperate($msg . '申请结算的银行卡不可用');
//            $this->ajaxReturn(['status' => 'no', 'msg' => '申请结算的银行卡不可用,请重新选择银行卡']);
//        }

        //2019-4-2 任梦龙：判断是否存在代付通道和账号
//        $config = $this->getUserSettleConfig($user_id);
//        if (!$config['daifu_id'] || !$config['account_id']) {
//            $this->addUserOperate($msg . '用户没有可用的代付通道或账号');
//            $this->ajaxReturn(['status' => 'no', 'msg' => '没有可用的代付通道或账号，请联系管理员处理']);
//        }

        $data = [
            'version' => 'v1.0.0',  //版本号
            'memberid' =>SecretkeyModel::getMemberid($user_id),  //商户号
            'orderid' => SettleModel::getUserOrdernumber($user_id), //订单号
            'amount' => $ordermoney*100,   //结算金额
            'bankname' => $settle_bankcard['bankname'],  //银行名称
            'bankcard' => $settle_bankcard['banknumber'], //银行卡号
            'bankaccountname' => $settle_bankcard['bankmyname'], //银行开户人姓名
            'identitynumber' => $settle_bankcard['shenfenzheng'], //身份证号码
            'phonenumber' => $settle_bankcard['tel'], //手机号
            'orderdatetime' => date('Y-m-d H:i:s'), //订单时间

            'bankzhiname' => $settle_bankcard['bankzhiname'], //银行支行名称
          //  'bankcode' => false, // 银行编码
            'province' => $settle_bankcard['banksheng'], //银行开户行省
            'city' => $settle_bankcard['bankshi'], //银行开户行市
            'banklhh' => $settle_bankcard['banklianhanghao'],  //银行联行号
            'accouttype' => 'PRIVATE', //收款账号类型，对公 PUBLIC, 对私 PRIVATE
           // 'remarks' => false, //扩展字段

           // 'notifyurl' => true, //回调地址
            'signmethod' => 'md5',  //加密类类型  MD5 或  RSA
           // 'sign' => true
        ];
      //  dump($data);die();
        //exit($this->getSettlePayUrl($data['version']));
        ksort($data);
        $signstr = '';
        foreach ($data as $key => $val){
            if($val == "" || $val == null){
                continue;
            }
            $signstr .= $key."=".$val."&";
        }
        $signstr = trim($signstr, '&');
        $md5key = SecretkeyModel::getUserMd5Key($user_id);
        $data['sign'] = strtoupper(md5($signstr.$md5key));
        //echo($signstr.$md5key);

        $return = https_request($this->getSettlePayUrl($data['version']),$data);
        //exit($return);
        $return = json_decode($return,true);
        $return_sign = $return['sign'];
        unset($return['sign']);
        ksort($return);
        $signstr = '';
        foreach ($return as $key => $val){
            if($val == "" || $val == null){
                continue;
            }
            $signstr .= $key."=".$val."&";
        }
        $signstr = trim($signstr, '&');
       // file_put_contents('hhhhhhhhhhhhhhhhh.txt', $signstr.$md5key, FILE_APPEND);
        $sign_sign =  strtoupper(md5($signstr.$md5key));
        if($sign_sign == $return_sign){
            $this->addUserOperate($msg . $return['msg']);
            $this->ajaxReturn(['status' => ($return['status']==4 || $return['status'] == 'error')?'no':'ok', 'msg' => $return['msg']]);
        }else{
            $msg_error = '回调签名错误 ，具体结果请查询结算记录';
            $this->addUserOperate($msg . $msg_error);
            $this->ajaxReturn(['status' =>'no', 'msg' => $msg_error]);
        }


    }

    private function getSettlePayUrl($varsion)
    {

        $find = M("website")->order('id DESC')->field("pay_domain,pay_http")->find();

        return ($find["pay_http"] == 2 ? "https" : "http") . "://" . $find["pay_domain"] . U('Settlement/Index/index','version='.$varsion);

    }

    private function checkIsBetweenTime($start, $end)
    {
        $curTime = strtotime(date('H:i:s'));
        $assignTime1 = strtotime($start);//获得指定分钟时间戳，00:00
        $assignTime2 = strtotime($end);//获得指定分钟时间戳，01:00
        $result = false;
        if ($curTime > $assignTime1 && $curTime < $assignTime2) {
            $result = true;
        }
        return $result;
    }


    public function getSignStr($fields, $data)
    {
        sort($fields);
        $str = '';
        foreach ($fields as $field) {
            $str .= $field . '=' . $data[$field] . '&';
        }
        return trim($str, '&');
    }

    public function getSign($user_id, $signStr)
    {
        $md5key = SecretkeyModel::getUserMd5Key($user_id);
        $sign = md5($signStr . "&key=" . $md5key);
        return $sign;
    }

    //批量结算页面
    public function manySettleApply()
    {
        $user_id = session('user_info.id');
        $this->assign('user_id', $user_id);

        //查询用户结算配置
        //2019-5-8 rml:首先判断用户的，再去判断系统的
        //判断存在结算设置: $type=1:存在 2=不存在。不存在时页面报错
        $settle_config = SettleconfigModel::getUserSettleConfig($user_id);
//        print_r($settle_config);die;
        if ($settle_config) {
            $type = 1;
            $settle_config['settle_feilv_info'] = ($settle_config['settle_feilv'] * 100) . '%';
            $this->assign('settle_config', $settle_config);
            //查询用户代付通道信息 有则获取代付通道名称,否则为无
            if ($settle_config['daifu_id']) {
                $daifu_name = DaifuModel::getDaifuName($settle_config['daifu_id']);
            } else {
                $daifu_name = '无';
            }
            $this->assign('daifu_name', $daifu_name);
            //查询用户的可用余额
            $money = M('usermoney')->where('userid=' . $user_id)->getField('money');
            $money = substr($money, 0, strlen($money) - 2);
            $money = $money ? $money : 0;
            $this->assign('money', $money);
            $code_type = $this->getCodeType();
            $this->assign('code_type', $code_type);
        } else {
            $type = 2;
        }
        $this->assign('type', $type);
        $this->display();
    }

    //批量结算提交程序
    //2019-4-17 rml：添加验证码，修改操作日志的方法
    //2019-5-8 rml：添加逻辑判断
    public function manyApplyConfirm()
    {
        $msg = '用户申请批量结算:';
        if (!session('user_info.id')) {
            $this->ajaxReturn(['status' => 'no', 'msg' => '用户不存在']);
        }
        $user_id = session('user_info.id');

        //判断结算的功能开关是否开启
        $website = M('website')->field('all_valve,api_valve,settle_valve')->order('id DESC')->limit(1)->find();
        if ($website['all_valve'] != 0 || $website['api_valve'] != 0 || $website['settle_valve'] != 1) {
            $this->addUserOperate($msg . '结算功能被关闭,请联系管理员');
            $this->ajaxReturn(['status' => 'no', 'msg' => '结算功能被关闭,请联系管理员']);
        }

        //判断上传文件时的验证码
        $upload_code = I('post.upload_code','','trim');
        $code_type = I('post.code_type', 0, 'intval');
        $this->checkVerifyCode($upload_code, $code_type, $msg);

        //获取商户号
        $memberid = SecretkeyModel::getMemberid($user_id);
        if (!$memberid) {
            $this->addUserOperate($msg . '商户号为空');
            $this->ajaxReturn(['status' => 'no', 'msg' => '您的商户号为空,请联系管理员处理']);
        }

        //判断代付通道和账号
        $daifu_id = I('post.daifu_id', 0);
        if (!$daifu_id) {
            $this->addUserOperate($msg . '没有可用的代付通道');
            $this->ajaxReturn(['status' => 'no', 'msg' => '您还没有可用的代付通道,请先联系管理员处理']);
        }
        $account_id = I('post.account_id', 0);
        if (!$account_id) {
            $this->addUserOperate($msg . '没有可用的通道账号');
            $this->ajaxReturn(['status' => 'no', 'msg' => '您还没有可用的通道账号,请先联系管理员处理']);
        }

        if (!file_exists(C('MANYSETTLE_PATH'))) {
            mkdir(C('MANYSETTLE_PATH'), 0777, true);
        }

        //上传文件
        $date_time = date('YmdHis');
        $save_name = $date_time . $user_id . rand(1000, 9999);
        $upload = new \Think\Upload(); // 实例化上传类
        $upload->maxSize = 2097152; // 设置附件上传大小
        $upload->exts = array('xls'); // 设置附件上传类型
        $upload->rootPath = C('MANYSETTLE_PATH'); // 设置附件上传目录
        $upload->saveName = $save_name;   //文件名
        $upload->subName = 'user-' . $user_id;   //子目录创建方式，以用户id命名
        // 上传文件
        $info = $upload->uploadOne($_FILES['file']);
        if (!$info) { // 上传错误提示错误信息
            $this->addUserOperate($msg . '上传文件时：' . $upload->getError());
            $this->ajaxReturn(['status' => 'no', 'msg' => '上传文件时：' . $upload->getError()]);
        } else { // 上传成功
            $all_path = C('MANYSETTLE_PATH') . $info['savepath'] . $info['savename'];
            //开始插入数据表
            $excel_data = $this->addExcelInfo($all_path);
            //为每一个订单生成结算订单号
            foreach ($excel_data as $key=>$val){
                $excel_data[$key]['userordernumber'] = SettleModel::getUserOrdernumber($user_id);
            }
//            print_r($excel_data);die;
            //拼接参数,请求接口
            $data = [
                'memberid' => $memberid,
                'time' => date('Y-m-d H:i:s'),
                'content' => base64_encode(json_encode($excel_data)),
                'type' => 'MD5',
            ];
            //获取签名字符串
            $signStr = $this->getSignStr($this->manySettleFields, $data);
            $data['sign'] = $this->getSign($user_id, $signStr);
            //提交地址
            $url = C('MANY_SETTLE_URL');
            $return = curlPost($url, $data);
            $return = json_decode($return, true);
            if ($return['status'] == '00') {
                //成功
                //看其中是否有失败,通过用户订单号来提醒有哪些失败
                $total = count($return['data']);//申请总笔数
                $success = 0;//成功笔数
                $fail = 0;//失败笔数
                foreach ($return['data'] as $k => $v) {
                    if ($v['status'] != '00') {
                        $fail++;
                        $this->addUserOperate("用户申请批量结算过程中,用户订单号为" . $v['userordernumber'] . "的订单结算申请失败:" . $v['msg']);
                    } else {
                        $success++;
                    }
                }
                if ($fail == $total) {
                    //全部失败
                    $this->addUserOperate($msg . '结算共' . $total . '笔,全部申请失败');
                    $this->ajaxReturn(['status' => 'no', 'msg' => '批量结算共' . $total . '笔,全部申请失败,详情请去操作记录中查看']);
                }
                if ($fail > 0 && $fail < $total) {
                    //部分失败
                    $this->addUserOperate($msg . '结算订单共' . $total . '笔,成功申请' . $success . '笔,失败' . $fail . '笔');
                    $this->ajaxReturn(['status' => 'no', 'msg' => '批量结算共' . $total . '笔,成功申请' . $success . '笔,失败' . $fail . '笔,失败的订单请去操作记录中查看']);
                }
                //全部成功
                $this->addUserOperate($msg . '结算订单共' . $total . '笔,全部申请成功');
                $this->ajaxReturn(['status' => 'ok', 'msg' => '批量结算共' . $total . '笔,全部申请成功']);
            } else {
                //失败
                $this->addUserOperate($msg . $return['msg']);
                $this->ajaxReturn(['status' => 'no', 'msg' => $return['msg']]);
            }
        }
    }

    public function addExcelInfo($filePath)
    {
        if (empty($filePath) or !file_exists($filePath)) {
            $this->addUserOperate('用户申请批量结算:上传文件不存在');
            $this->ajaxReturn(['status' => 'no', 'msg' => '上传文件不存在']);
        }
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
        $data = [];
        $i = 0;
        for ($rowIndex = 2; $rowIndex <= $allRow; $rowIndex++) {

            $addr = "A" . $rowIndex;
            $cell = $currentSheet->getCell($addr)->getValue();
            if ($cell instanceof PHPExcel_RichText) {     //富文本转换字符串
                $cell = $cell->__toString();
            }
            $data[$i]['bankname'] = $cell;      //银行名称

            $addr = "B" . $rowIndex;
            $cell = $currentSheet->getCell($addr)->getValue();
            if ($cell instanceof PHPExcel_RichText) {     //富文本转换字符串
                $cell = $cell->__toString();
            }
            $data[$i]['bankzhiname'] = $cell;       //银行支行

//            $addr = "C" . $rowIndex;
//            $cell = $currentSheet->getCell($addr)->getValue();
//            if ($cell instanceof PHPExcel_RichText) {     //富文本转换字符串
//                $cell = $cell->__toString();
//            }
//            $data[$i]['banklianhanghao'] = $cell;       //联行号

            $addr = "D" . $rowIndex;
            $cell = $currentSheet->getCell($addr)->getValue();
            if ($cell instanceof PHPExcel_RichText) {     //富文本转换字符串
                $cell = $cell->__toString();
            }
            $data[$i]['bankcode'] = $cell;      //银行编码

            $addr = "E" . $rowIndex;
            $cell = $currentSheet->getCell($addr)->getValue();
            if ($cell instanceof PHPExcel_RichText) {     //富文本转换字符串
                $cell = $cell->__toString();
            }
            $data[$i]['bankusername'] = $cell;        //银行卡开户名

            $addr = "F" . $rowIndex;
            $cell = $currentSheet->getCell($addr)->getValue();
            if ($cell instanceof PHPExcel_RichText) {     //富文本转换字符串
                $cell = $cell->__toString();
            }
            $data[$i]['bankcardnumber'] = $cell;        //银行卡号

            $addr = "G" . $rowIndex;
            $cell = $currentSheet->getCell($addr)->getValue();
            if ($cell instanceof PHPExcel_RichText) {     //富文本转换字符串
                $cell = $cell->__toString();
            }
            $data[$i]['province'] = $cell;         //开户行所在省

            $addr = "H" . $rowIndex;
            $cell = $currentSheet->getCell($addr)->getValue();
            if ($cell instanceof PHPExcel_RichText) {     //富文本转换字符串
                $cell = $cell->__toString();
            }
            $data[$i]['city'] = $cell;       //开户行所在市

            $addr = "I" . $rowIndex;
            $cell = $currentSheet->getCell($addr)->getValue();
            if ($cell instanceof PHPExcel_RichText) {     //富文本转换字符串
                $cell = $cell->__toString();
            }
            $data[$i]['identitynumber'] = $cell;      //身份证号

            $addr = "J" . $rowIndex;
            $cell = $currentSheet->getCell($addr)->getValue();
            if ($cell instanceof PHPExcel_RichText) {     //富文本转换字符串
                $cell = $cell->__toString();
            }
            $data[$i]['phonenumber'] = $cell;          //手机号

            $addr = "K" . $rowIndex;
            $cell = $currentSheet->getCell($addr)->getValue();
            if ($cell instanceof PHPExcel_RichText) {     //富文本转换字符串
                $cell = $cell->__toString();
            }
            $data[$i]['ordermoney'] = $cell;        //订单金额
            $i++;
        }
        return $data;
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

        //查询所有退款状态
        $refundstatus = C('REFUNDSETTLESTATUS');
        $this->assign('refundstatus', $refundstatus);

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
        $userordernumber = trim(I("post.userordernumber", ""));
        if ($userordernumber <> "") {
            $where[$i] = "(userordernumber like '%" . $userordernumber . "%')";
            $i++;
        }
        $status = I("post.status", "");
        if ($status <> "") {
            $where[$i] = "status = " . $status;
            $i++;
        }
        $refundstatus = I("post.refundstatus", "");
        if ($refundstatus <> "") {
            $where[$i] = "refundstatus = " . $refundstatus;
            $i++;
        }
        $apply_start = I("post.apply_start", "");
        $apply_end = I("post.apply_end", "");
        if($apply_start && $apply_end){
            $where[$i] = "unix_timestamp( applytime ) between unix_timestamp( '{$apply_start} ') and unix_timestamp( '{$apply_end}' )";
            $i++;
        }

//        if ($apply_start <> "") {
//            $where[$i] = "DATEDIFF('" . $apply_start . "',applytime) <= 0";
//            $i++;
//        }
//
//        if ($apply_end <> "") {
//            $where[$i] = "DATEDIFF('" . $apply_end . "',applytime) >= 0";
//            $i++;
//        }
        $deal_start = I("post.deal_start", "");
        $deal_end = I("post.deal_end", "");
        if($deal_start && $deal_end){
            $where[$i] = "unix_timestamp( dealtime ) between unix_timestamp( '{$deal_start} ') and unix_timestamp( '{$deal_end}' )";
            $i++;
        }

//        if ($deal_start <> "") {
//            $where[$i] = "DATEDIFF('" . $deal_start . "',dealtime) <= 0";
//            $i++;
//        }
//
//        if ($deal_end <> "") {
//            $where[$i] = "DATEDIFF('" . $deal_end . "',dealtime) >= 0";
//            $i++;
//        }
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
        $userordernumber = trim(I("get.userordernumber", ""));
        if ($userordernumber <> "") {
            $where[$i] = "(userordernumber like '%" . $userordernumber . "%')";
            $i++;
        }
        $status = I("get.status", "");
        if ($status <> "") {
            $where[$i] = "status = " . $status;
            $i++;
        }
        $refundstatus = I("get.refundstatus", "");
        if ($refundstatus <> "") {
            $where[$i] = "refundstatus = " . $refundstatus;
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
        $all_status = C('SETTLESTATUS');
        $all_refundstatus = C('REFUNDSETTLESTATUS');
        foreach ($datalist as $k => $v) {
            if ($v['deduction_type'] == 1) {
                $datalist[$k]['deduction_type'] = '外扣';
            } else {
                $datalist[$k]['deduction_type'] = '内扣';
            }
            $datalist[$k]['status'] = $all_status[$v['status']];
            $datalist[$k]['refundstatus'] = $all_refundstatus[$v['refundstatus']];
        }

        $title = '交易记录表';
        $menu_zh = array('订单号', '结算金额', '手续费', '到账金额', '扣款方式', '银行名称', '银行卡号', '开户名', '手机号', '申请时间', '处理时间', '结算状态', '退款状态');
        $menu_en = array('ordernumber', 'ordermoney', 'moneytrade', 'money', 'deduction_type', 'bankname', 'bankcardnumber', 'bankusername', 'phonenumber', 'applytime', 'dealtime', 'status', 'refundstatus');
        $config = array('RowHeight' => 25, 'Width' => 20);
        $this->addUserOperate("用户导出了结算记录");
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
        $refund_status = C('REFUNDSETTLESTATUS');
        $order_info['username'] = $this->getNameById('user', $order_info['userid'], 'username');
        $order_info['status_name'] = $status[$order_info['status']];
        $order_info['refundstatus_name'] = $refund_status[$order_info['refundstatus']];
        if ($order_info['deduction_type'] == 1) {
            $order_info['deduction_type'] = '外扣';
        } else {
            $order_info['deduction_type'] = '内扣';
        }

        //如果已打款查询打款的资金变动记录
        if ($order_info['status'] >= 2) {
            $settle_change = MoneychangeModel::getInfoByChangetype($order_info['ordernumber'], 5);
            if ($settle_change) {
                $order_info['settle_change']['oldmoney'] = $settle_change['oldmoney'];
                $order_info['settle_change']['changemoney'] = $settle_change['changemoney'];
                $order_info['settle_change']['nowmoney'] = $settle_change['nowmoney'];
            }
        }
        //如果已退款查询退款的资金变动记录
        if ($order_info['refundstatus'] == 2) {
            $refund_change = MoneychangeModel::getInfoByChangetype($order_info['ordernumber'], 8);
            $order_info['refund_change']['oldmoney'] = $refund_change['oldmoney'];
            $order_info['refund_change']['changemoney'] = $refund_change['changemoney'];
            $order_info['refund_change']['nowmoney'] = $refund_change['nowmoney'];
        }
        $this->assign('order_info', $order_info);
        $this->display();
    }

    //退款申请
    public function refundApply()
    {
        $settle_id = I('settle_id');
        $order_info = M('settleinfo')->where('settleid=' . $settle_id)->find();
        $msg = "结算订单[用户订单号为" . $order_info['userordernumber'] . "]退款申请:";
        if ($order_info['status'] >= 3 || $order_info['refundstatus'] > 0) {
            $this->addUserOperate($msg . '结算订单状态有误');
            $this->ajaxReturn(['status' => 'no', 'msg' => '结算订单状态有误,请检查后再处理']);
        }
        $res = M('settle')->where('id=' . $settle_id)->setField('refundstatus', 1);
        if ($res) {
            $this->addUserOperate($msg . '退款申请成功');
            $this->ajaxReturn(['status' => 'ok', 'msg' => '结算订单退款申请成功']);
        } else {
            $this->addUserOperate($msg . '退款申请失败');
            $this->ajaxReturn(['status' => 'no', 'msg' => '结算订单退款申请失败']);
        }
    }
}