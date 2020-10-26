<?php
/**
 * Created by PhpStorm.
 * User: zhangyang
 * Date: 18-10-16
 * Time: 下午3:45
 */

namespace Admin\Controller;

use Admin\Model\PayapiaccountModel;
use Admin\Model\QrcodetemplateModel;
use Admin\Model\UserModel;
use Admin\Model\UserpayapiclassModel;
use Admin\Model\MoneytypeclassModel;   //2019-3-6 任梦龙：到账方案模型
use Admin\Model\PayapiModel;  //2019-03-06汪桂芳添加
use Admin\Model\PayapiclassModel;  //2019-3-26  任梦龙添加
use Admin\Model\PayapishangjiaModel;  //2019-3-26  任梦龙添加
use Admin\Model\UserpayapiaccountModel;  //2019-3-26  任梦龙添加

//2019-3-26 任梦龙：添加操作记录,优化代码
class UserTongdaoSettingsController extends CommonController
{
    public function __construct()
    {
        parent::__construct();
    }

    //2019-3-26 任梦龙：用户修改页面中的通道设置页面
    public function userTongdaoSetting()
    {
        $userid = I("get.userid", "");
        $payapiclasslist = PayapiclassModel::getPayapiClass(['status' => 1, 'del' => 0]);
        $this->assign("payapiclasslist", $payapiclasslist);
        $list = UserpayapiclassModel::getCheckUserclass($userid);
        $this->assign("list", $list);
        $shangjialist = PayapishangjiaModel::getShangjiaList(['status' => 1, 'del' => 0]);
        $this->assign("shangjialist", $shangjialist);
        $this->assign('user_id', $userid); //当前用户id
        $this->display();
    }

    //取消用户的通道分类操作程序
    public function EditUserTongdaoclass()
    {
        $payapiclass_id = I("post.payapiclass_id", "");
        $user_id = I("post.user_id", "");
        if (!($payapiclass_id && $user_id)) {
            $this->ajaxReturn(['status' => 'no', 'msg' => '请勿非法操作']);
        }
        $find = UserpayapiclassModel::getUserpayapiInfo($user_id, $payapiclass_id);
        $user_name = UserModel::getUserName($user_id);
        $class_name = PayapiclassModel::getPayapiClassname($payapiclass_id);
        $msg = '取消用户[' . $user_name . ']的通道分类[' . $class_name . ']:';
        if (!$find) {
            $this->addAdminOperate($msg . '请勿非法操作');
            $this->ajaxReturn(['status' => 'no', 'msg' => '请勿非法操作']);
        } else {
            //删除用户分类和下面的账户
            UserpayapiclassModel::delInfo($find['id']);
            UserpayapiaccountModel::delUseraccount($find['id']);
            $this->addAdminOperate($msg . '操作成功');
            $this->ajaxReturn(['status' => 'ok', 'msg' => '操作成功']);
        }
    }

    //通道设置-->选择通道操作程序
    public function EditUserPayapiClass()
    {
        $class_id = I('post.id', '');  //分类id
        $payapi_id = I('post.payapiid', '');  //通道id
        $user_id = I('post.user_id', '');  //用户id
        $user_name = UserModel::getUserName($user_id);
        $payapi_name = PayapiModel::getPayapiName($payapi_id);
        $class = PayapiclassModel::getPayapiClassInfo($class_id);
        $msg = '为用户[' . $user_name . ']在通道分类[' . $class['classname'] . ']下设置通道[' . $payapi_name . ']:';
        if (!($class_id && $payapi_id && $user_id)) {
            $this->ajaxReturn(['status' => 'no', 'msg' => '请勿非法操作']);
        }
        //如果先前已经设置过，则修改通道，没有则增加一条记录
        $find = UserpayapiclassModel::getUserpayapiInfo($user_id, $class_id);
        if ($find) {
            //查询通道分类的费率
            $payapi_class = PayapiclassModel::getPayapiClassInfo($find['payapiclassid']);
            $data = [
                'payapiid' => $payapi_id,
                'order_feilv' => $class['order_feilv'],
                'order_min_feilv' => $class['order_min_feilv']
            ];
            $res = UserpayapiclassModel::saveUserclassInfo($find['id'], $data);
            //删除成功后再来删除原来通道的账号,这一步有问题
//            if($res){
//                UserpayapiaccountModel::delUseraccount($find['id']);
//            }
        } else {
            //查询系统默认的交易费率
            $class = PayapiclassModel::getPayapiClassInfo($class_id);
            if (!$class['order_feilv'] || !$class['order_min_feilv']) {
                $this->addAdminOperate($msg . '设置失败,请先完善此通道分类的费率设置');
                $this->ajaxReturn(['status' => 'no', 'msg' => '设置失败,请先完善此通道分类的费率设置']);
            }
            $data = [
                'userid' => $user_id,
                'payapiclassid' => $class_id,
                'payapiid' => $payapi_id,
                'order_feilv' => $class['order_feilv'],
                'order_min_feilv' => $class['order_min_feilv'],
            ];
            $res = UserpayapiclassModel::addInfo($data);
        }
        if ($res) {
            $this->addAdminOperate($msg . '设置成功');
            $this->ajaxReturn(['status' => 'ok', 'msg' => '设置成功']);
        } else {
            $this->addAdminOperate($msg . '设置失败');
            $this->ajaxReturn(['status' => 'no', 'msg' => '设置失败']);
        }
    }

    //编辑通道账号页面
    public function ShowUserPayapiaccount()
    {
        $userid = I("get.userid", "");
        $this->assign("userid", $userid);
        $payapiclassid = I("get.payapiclassid", "");
        $username = UserModel::getUserName($userid);
        $this->assign("username", $username);
        $find = UserpayapiclassModel::getUserpayapiInfo($userid, $payapiclassid);
        $this->assign("id", $find["id"]);
        $this->assign("payapiid", $find["payapiid"]);
        $payapiname = PayapiModel::getPayapiName($find["payapiid"]);
        $this->assign("payapiname", $payapiname);

        //2019-03-06汪桂芳添加:判断该通道分类是否有扫码模板
        $temp = QrcodetemplateModel::getClassQrcodeList($payapiclassid);
        $this->assign("temp", $temp);
        $this->display();
    }

    //加载用户的通道账号页面
    public function LoadUserPayapiAccount()
    {
        $userpayapiclassid = I("get.id", "");
        $list = M('userpayapiaccount')->where("userpayapiclassid = " . $userpayapiclassid)->select();
        //找到所有账号
        $where = [];
        $where[0] = "id in (select accountid from pay_userpayapiaccount where userpayapiclassid = " . $userpayapiclassid . ")";
        $payapiaccountList = M('payapiaccount')->where($where)->select();
        //找到通道
        $payapiid = M('userpayapiclass')->where("id = " . $userpayapiclassid)->getField('payapiid');
        //找到用户
        $userid = M('userpayapiclass')->where("id = " . $userpayapiclassid)->getField('userid');

        foreach ($payapiaccountList as $k => $v) {
            //添加用户通道账号关系的id
            foreach ($list as $key => $val) {
                if ($v['id'] == $val['accountid']) {
                    $payapiaccountList[$k]['userpayapiaccountid'] = $val['id'];
                    $payapiaccountList[$k]['accountid'] = $val['accountid'];
                }
            }
            //查找通道限额
            $where1 = array(
                'payapiid' => $payapiid,
                'payapiaccountid' => $v['id'],
                'userid' => 0
            );
            $payapiaccountList[$k]['payapiid'] = $payapiid;
            $payapiaccountList[$k]['userid'] = $userid;
            //找到商家
            $payapiaccountList[$k]['shangjianame'] = M('payapishangjia')->where("id=" . $v['payapishangjiaid'])->getField('shangjianame');
            $tongdaoxiane = M('tongdaozhanghao')->where($where1)->getField('money');
            if ($tongdaoxiane) {
                $payapiaccountList[$k]['tongdaoxiane'] = $tongdaoxiane;
            } else {
                $payapiaccountList[$k]['tongdaoxiane'] = '0.00';
            }

            //查找用户限额
            $where2 = array(
                'payapiid' => $payapiid,
                'payapiaccountid' => $v['id'],
                'userid' => $userid
            );
            $userxiane = M('tongdaozhanghao')->where($where2)->find();
            $payapiaccountList[$k]['userxianeid'] = $userxiane['id'];
            if ($userxiane['money']) {
                $payapiaccountList[$k]['userxiane'] = $userxiane['money'];
            } else {
                $payapiaccountList[$k]['userxiane'] = '0.00';
            }

            /*//查找用户费率   2018-12-29汪桂芳修改
            $where3 = array(
                'payapiaccountid' => $v['id'],
                'userid' => $userid
            );
            $feilv = M('feilv')->where($where3)->find();
            if ($feilv) {
                $payapiaccountList[$k]['feilv'] = $feilv['feilv'];
            }*/

            //查找账号成本费率   2018-12-29汪桂芳添加
            $payapiaccountList[$k]['order_cbfeilv'] = PayapiaccountModel::getAccountField($v['id'], 'cbfeilv');
            $payapiaccountList[$k]['settle_cbfeilv'] = PayapiaccountModel::getAccountField($v['id'], 'settle_cbfeilv');
        }
        //分页
        $ReturnArr = [
            'code' => 0,
            'msg' => '数据加载成功', //响应结果
            'count' => 0, //总页数
            'data' => [
            ]
        ];
        $page = I("post.page");
        $limit = I("post.limit");
        $i = 0;
        foreach ($payapiaccountList as $k => $v) {
            if ($i < $limit && ($i + ($page - 1) * $limit) < count($payapiaccountList)) {
                $newpayapiaccountList[] = $payapiaccountList[$i + ($page - 1) * $limit];
            } else {
                break;
            }
            $i++;
        }
        $ReturnArr['count'] = count($payapiaccountList);
        $ReturnArr['data'] = $newpayapiaccountList;
        $this->ajaxReturn($ReturnArr, 'JSON');
    }

    //修改用户的账户限额页面
    public function EditUserPayapiaccountMoney()
    {
        $userpayapiaccountid = I('get.userpayapiaccountid');
        $userxianeid = I('get.userxianeid');
//        echo $userpayapiaccountid;die;
        //查询账户id
        $info = UserpayapiclassModel::getUserPayapiAccountInfo($userpayapiaccountid);
        $where = array(
            'payapiaccountid' => $info['accountid'],
            'payapiid' => $info['payapiid'],
            'userid' => $info['userid'],
        );
        $money = M("tongdaozhanghao")->where($where)->getField("money");
        if (!$money) {
            $money = '0.00';
        }
        //计算该通道账号还剩额度
        $where1 = array(
            'payapiid' => $info['payapiid'],
            'payapiaccountid' => $info['accountid'],
        );

        $allinfo = M('tongdaozhanghao')->where($where1)->select();
        //得到该通道账号的总限额和其它用户已用额度
        $other = 0;
        foreach ($allinfo as $k => $v) {
            if ($v['userid'] == 0) {
                $all = $v['money'];
            } elseif ($v['userid'] != $info['userid'] && $v['userid'] != 0) {
                $other += $v['money'];
            }
        }
        //先判断该通道是否有限额
        if ($all > 0) {
            //计算出该用户还可以设置的限额
            $leave = $all - $other;
            $this->assign("money", $money);
            $this->assign("leave", $leave);
            $this->assign("userpayapiaccountid", $userpayapiaccountid);
            $this->assign("userxianeid", $userxianeid);
            $this->display();
        } else {
            $return["status"] = "no";
            $return["msg"] = "请先去通道账号中给此通道设置限额";
            $this->ajaxReturn($return);
        }

    }

    //修改用户的账户限额处理程序
    //2019-4-28 任梦龙：判断金额格式
    public function UserPayapiaccountMoneyEdit()
    {
        $post = I('post.');
        //修改该账户的交易总额
        $userpayapiaccountid = $post['userpayapiaccountid'];
        $userxianeid = $post['userxianeid'];
        $leave = $post['leave'];
        if ($post['money'] == '' || $post['money'] == null) {
            $this->ajaxReturn(['status' => 'no', 'msg' => '设置金额不能为空']);
        }
        //判断金额格式
        if (!is_numeric($post['money'])) {
            $this->ajaxReturn(['status' => 'no', 'msg' => '设置金额格式不对']);
        }
        if ($post['money'] > $leave) {
            $this->ajaxReturn(['status' => 'no', 'msg' => '设置金额不能大于可用限额']);
        }
        $info = UserpayapiclassModel::getUserPayapiAccountInfo($userpayapiaccountid);
        if ($userxianeid > 0) {
            $tongdaozhanghao_info = M('tongdaozhanghao')->where('id=' . $userxianeid)->find();
            $old = $tongdaozhanghao_info['money'];
            if ($old != $post['money']) {
                $data['money'] = $post['money'];
                $res = M('tongdaozhanghao')->where('id=' . $userxianeid)->save($data);
            } else {
                $res = true;
            }
        } else {
            //给用户添加限额记录
            $datas = [
                'payapiid' => $info['payapiid'],
                'payapiaccountid' => $info['accountid'],
                'userid' => $info['userid'],
                'money' => $post['money']
            ];
            $res = M('tongdaozhanghao')->add($datas);
        }
        $user_name = UserModel::getUserName($info['userid']);
        $class_name = PayapiclassModel::getPayapiClassname($info['payapiclassid']);
        $msg = '设置用户[' . $user_name . ']在通道分类[' . $class_name . ']中的用户每日交易总额:';
        if ($res) {
            $this->addAdminOperate($msg . '设置成功');
            $this->ajaxReturn(['status' => 'ok', 'msg' => '账户交易金额设置成功']);
        } else {
            $this->addAdminOperate($msg . '设置失败');
            $this->ajaxReturn(['status' => 'no', 'msg' => '账户交易金额失败,请稍后重试']);
        }
    }

    //给用户的账号设置费率的页面  2018-12-29汪桂芳修改
    //2019-3-26 任梦龙：修改
    public function EditUserAccountFeilv()
    {
        $user_id = I('get.userid');
        $userpayapiclassid = I('get.userpayapiclassid');
        //读取用户通道的交易费率，没有的话读取该通道分类的交易费率
        $user_payapiclass = UserpayapiclassModel::getUserPayapiclassInfo($userpayapiclassid);
        $payapi_class = PayapiclassModel::getPayapiClassInfo($user_payapiclass['payapiclassid']);
        if ($user_payapiclass['order_feilv'] != '') {
            $order_feilv = $user_payapiclass['order_feilv'];
        } else {
            $order_feilv = $payapi_class['order_feilv'];
        }

        if ($user_payapiclass['order_min_feilv'] != '') {
            $order_min_feilv = $user_payapiclass['order_min_feilv'];
        } else {
            $order_min_feilv = $payapi_class['order_min_feilv'];
        }

        //2019-02-28汪桂芳添加:查询可设置的费率范围
        //判断用户是否有上级,有上级的话最低是上级的,没有上级的话最低是通道分类的
        $superior_userid = M('user')->where('id=' . $user_id)->getField('superiorid');
        if ($superior_userid) {
            //查询上级在此通道分类的费率
            $min_feilv = UserpayapiclassModel::getUserPayapiclassFeilv($superior_userid, $user_payapiclass['payapiclassid']);
            if (!$min_feilv) {
                $min_feilv = PayapiclassModel::getPayapiclassFeilv($user_payapiclass['payapiclassid']);
            }
            //查询上级的提成上限
            $superior_profit = M('usercommissionset')->where('user_id=' . $superior_userid)->getField('max_profit');
            if (!$superior_profit) {
                $superior_profit = C('USER_COMMISSION');
            }
            $max_feilv = $min_feilv + $superior_profit;
        } else {
            $min_feilv = PayapiclassModel::getPayapiclassFeilv($user_payapiclass['payapiclassid']);
            $superior_profit = C('USER_COMMISSION');
            $max_feilv = $min_feilv + $superior_profit;
        }
        $this->assign('min_feilv', $min_feilv);
        $this->assign('max_feilv', $max_feilv);
        $this->assign('user_id', $user_id);
        $this->assign('userpayapiclassid', $userpayapiclassid);
        $this->assign('order_feilv', $order_feilv);
        $this->assign('order_min_feilv', $order_min_feilv);
        $this->display();
    }

    //2019-3-26 任梦龙：将方法写入模型.添加操作记录
    //给用户的账号设置费率的处理程序
    // 2018-12-27 任梦龙修改账号的字段名称 accountid -->payapiaccountid
    //2018-12-28 任梦龙 更改EditUserAccountFeilv.html 页面上 accountid -->payapiaccountid
    //2018-12-29 汪桂芳修改 只设置用户的交易费率和最低手续费
    //2019-02-28汪桂芳修改:添加下限和上限的判断
    public function UserAccountFeilvEdit()
    {
        $userid = I('userid');
        $userpayapiclassid = I('userpayapiclassid');
        $min_feilv = I('min_feilv');
        $max_feilv = I('max_feilv');
        $order_feilv = I('post.order_feilv', '', 'trim');
        $order_min_feilv = I('post.order_min_feilv', '', 'trim');
        //2019-7-30 rml:用户的费率不受分类费率的影响
//        if ($order_feilv < $min_feilv) {
//            $this->ajaxReturn(['status' => 'no', 'msg' => '设置的交易费率不可低于' . $min_feilv]);
//        }
//        if ($order_feilv > $max_feilv) {
//            $this->ajaxReturn(['status' => 'no', 'msg' => '设置的交易费率不可高于' . $max_feilv]);
//        }
        $user_name = UserModel::getUserName($userid);
        $class_name = PayapiclassModel::getPayapiClassname($userpayapiclassid['payapiclassid']);
        $msg = '修改用户[' . $user_name . ']在通道分类[' . $class_name . ']中的费率:';
        $data = [
            'order_feilv' => $order_feilv,
            'order_min_feilv' => $order_min_feilv
        ];
        //修改数据表记录
        $res = M('userpayapiclass')->where(['id' => $userpayapiclassid])->save($data);
        if ($res) {
            $this->addAdminOperate($msg . '设置成功');
            $this->ajaxReturn(['status' => 'ok', 'msg' => '用户费率设置成功']);
        } else {
            $this->addAdminOperate($msg . '设置失败');
            $this->ajaxReturn(['status' => 'no', 'msg' => '用户费率设置失败']);
        }
    }

    //设置用户到账方案页面
    //2019-5-5 rml:优化
    public function editUserMoneyClass()
    {
        $userid = I('get.userid');
        $payapiaccountid = I('get.accountid');
        $money_class_list = MoneytypeclassModel::getMoneyClassList(); //到账方案列表
        //如果用户到账方案表中存在记录，记录id有值，没有则为空
        $find = M('usermoneyclass')->where('userid = ' . $userid . ' and payapiaccountid = ' . $payapiaccountid)->find();
        $moneyclass_id = $find ? $find['moneytypeclass_id'] : '';
        $this->assign('money_class_list', $money_class_list);
        $this->assign("userid", $userid);
        $this->assign("payapiaccountid", $payapiaccountid);
        $this->assign("moneyclass_id", $moneyclass_id);
        $this->display();
    }

    //确认修改用户到账方案
    //2019-5-5 rml:优化
    public function userMoneyClassEdit()
    {
        $data = I('post.');
        if (!$data['moneytypeclass_id']) {
            $this->ajaxReturn(['status' => 'no', 'msg' => '到账方案不能为空']);
        }
        $user_name = UserModel::getUserName($data['userid']);
        $account_name = PayapiaccountModel::getAccountName($data['payapiaccountid']);
        $moneytypeclass = M('moneytypeclass')->where('id=' . $data['moneytypeclass_id'])->getField('moneyclassname');
        $msg = '为用户[' . $user_name . ']的通道账号[' . $account_name . ']设置到账方案[' . $moneytypeclass . ']:';

        //判断是否已经存在记录，是则修改，否则添加
        $user_moneyclass = M('usermoneyclass');
        $find = $user_moneyclass->where(['userid' => $data['userid'], 'payapiaccountid' => $data['payapiaccountid']])->find();
        if ($find) {
            $res = $user_moneyclass->where(['id' => $find['id']])->save($data);
        } else {
            $res = $user_moneyclass->add($data);
        }
        if ($res) {
            $this->addAdminOperate($msg . '设置成功');
            $this->ajaxReturn(['status' => 'ok', 'msg' => '设置成功']);
        } else {
            $this->addAdminOperate($msg . '设置失败');
            $this->ajaxReturn(['status' => 'no', 'msg' => '设置失败']);
        }


    }


    public function HuoQuPayapiMoney()
    {
        $id = I("request.id", "");
        $payapiid = I("request.payapiid", "");
        $money = M("tongdaozhanghao")->where("payapiid=" . $payapiid . " and payapiaccountid = " . $id)->getField("money");
        exit($money);
    }

    //删除用户的通道账号,删除时，对应的用户账号的限额信息,用户账号的到账方案一并删除
    public function DelUserPayapiaccount()
    {
        $account_id = I("post.id", "");   //账号id
        $userpayapiclass_id = I("get.userpayapiclassid", "");  //用户通道分类id(userpayapiclass表)
        if (!($account_id && $userpayapiclass_id)) {
            $this->ajaxReturn(['status' => 'no', 'msg' => '请勿非法操作']);
        }
        //由$userpayapiclass_id得到用户和通道
        $user_payapiclass = M('userpayapiclass')->find($userpayapiclass_id);
        $user_name = UserModel::getUserName($user_payapiclass['userid']);
        $account_name = PayapiaccountModel::getAccountName($account_id);
        $class_name = PayapiclassModel::getPayapiClassname($user_payapiclass['payapiclassid']);
        $msg = '删除用户[' . $user_name . ']在通道分类[' . $class_name . ']中的的通道账号[' . $account_name . ']:';
        //删除用户账号数据
        $res = M("userpayapiaccount")->where("accountid=" . $account_id . " and userpayapiclassid=" . $userpayapiclass_id)->delete();
        if ($res) {
            $where_quota = [
                'payapiid' => $user_payapiclass['payapiid'],
                'payapiaccountid' => $account_id,
                'userid' => $user_payapiclass['userid']   //2018-12-29汪桂芳修改
            ];
            //如果用户设置了限额，则删除用户账号限额信息
            $user_quota = M('tongdaozhanghao')->where($where_quota)->count();
            if ($user_quota > 0) {
                M('tongdaozhanghao')->where($where_quota)->delete();
            }
            //如果用户设置了到账方案，则删除
            $where_set = [
                'payapiaccountid' => $account_id,
                'userid' => $user_payapiclass['userid']
            ];
            $user_moneyclass = M('usermoneyclass')->where($where_set)->count();
            if ($user_moneyclass > 0) {
                M('usermoneyclass')->where($where_set)->delete();
            }
            $this->addAdminOperate($msg . '删除成功');
            $this->ajaxReturn(['status' => 'ok', 'msg' => '删除成功']);
        } else {
            $this->addAdminOperate($msg . '删除失败');
            $this->ajaxReturn(['status' => 'no', 'msg' => '删除失败']);
        }
    }

    //添加用户的通道账号页面
    public function AddUserPayapiaccount()
    {
        $payapiid = I("get.payapiid", "");
        $userpayapiclassid = I("get.userpayapiclassid");
        $this->assign("payapiid", $payapiid);
        $this->assign("userpayapiclassid", $userpayapiclassid);
        $this->display();
    }

    //加载通道账号列表
    //2019-3-26 任梦龙：修改表前缀
    public function LoadPayapiAccount()
    {
        $pre = C('DB_PREFIX');
        $payapiid = I("get.payapiid", "");
        $userpayapiclassid = I("get.userpayapiclassid");
        $where = [];
        $where[0] = "id in (select payapiaccountid from " . $pre . "tongdaozhanghao where payapiid = " . $payapiid . ")";
        $where[1] = "id not in (select accountid from " . $pre . "userpayapiaccount where userpayapiclassid = " . $userpayapiclassid . " )";
        $this->ajaxReturn(PageDataLoad('payapiaccount', $where), 'JSON');
    }

    //添加选中的通道账号(即添加用户的通道账号)
    public function UserPayapiaccountAdd()
    {
        $idstr = I("post.idstr", "");
        $userpayapiclassid = I("get.userpayapiclassid", "");
        $arr = explode(",", $idstr);
        $user_payapi_class = UserpayapiclassModel::getUserPayapiclassInfo($userpayapiclassid);
        $user_name = UserModel::getUserName($user_payapi_class['userid']);
        $class_name = PayapiclassModel::getPayapiClassname($user_payapi_class['payapiclassid']);
        $msg = '为用户[' . $user_name . ']在通道分类[' . $class_name . ']中添加用户的通道账号:';
        foreach ($arr as $key => $val) {
            UserpayapiaccountModel::addUseraccount(['accountid' => $val, 'userpayapiclassid' => $userpayapiclassid]);
            //用户通道账号限额表添加数据
            $data = [
                'payapiid' => $user_payapi_class['payapiid'],
                'userid' => $user_payapi_class['userid'],
                'payapiaccountid' => $val,
                'money' => 0,
            ];
            M("tongdaozhanghao")->add($data);
        }
        $this->addAdminOperate($msg . '添加成功');
        exit('ok');
    }

    //2019-03-06汪桂芳添加:用户的扫码设置页面
    public function editUserQrcode()
    {
        $user_id = I('get.userid');
        $payapi_id = I('get.payapiid');

        //读取用户通道的信息
        $where = [
            'userid' => $user_id,
            'payapiid' => $payapi_id,
        ];
        $user_payapi = M('userpayapiclass')->where($where)->find();
        $this->assign('user_payapi', $user_payapi);

        //查询所有模板
        $payapicalss_id = PayapiModel::getPayapiclassid($payapi_id);
        $allImgs = QrcodetemplateModel::getClassQrcodeList($payapicalss_id);
        $this->assign('allImgs', $allImgs);

        $this->display();
    }

    //2019-03-06汪桂芳:选择扫码类型处理程序
    public function userQrcodeEdit()
    {
        $type = I('get.type');
        $userpayapiclass_id = I('get.userpayapiclass_id');
        $user_payapi = M('userpayapiclass')->where('id=' . $userpayapiclass_id)->find();
        //应用系统默认设置
        $res = true;
        if ($type != $user_payapi['qrcode']) {
            $res = M('userpayapiclass')->where('id=' . $userpayapiclass_id)->setField('qrcode', $type);
        }
        if ($res) {
            $this->ajaxReturn(['status' => 'ok', 'msg' => '修改成功',]);
        } else {
            $this->ajaxReturn(['status' => 'no', 'msg' => '修改失败',]);
        }
    }

    //2019-03-06汪桂芳添加:选择扫码模板处理程序
    public function confirmTeplate()
    {
        $userpayapiclass_id = I('post.userpayapiclass_id');
        $templateid = I('post.templateid');
        //2019-2-18 任梦龙：方法写入模型
        //查询该账号的模板
        $qrcodeid = M('userpayapiclass')->where('id=' . $userpayapiclass_id)->getField('qrcode_template_id');
        $res = true;
        if ($qrcodeid != $templateid) {
            $res = M('userpayapiclass')->where('id=' . $userpayapiclass_id)->setField('qrcode_template_id', $templateid);
        }
        if ($res) {
            $this->ajaxReturn(['status' => 'ok', 'msg' => '扫码模板选择成功',]);
        } else {
            $this->ajaxReturn(['status' => 'no', 'msg' => '扫码模板选择失败',]);
        }
    }

}