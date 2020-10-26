<?php
/**
 * 结算管理
 */

namespace Admin\Controller;

use Admin\Model\DaifuModel;
use Admin\Model\SettledateModel;
use Admin\Model\SettleModel;
use Admin\Model\SettlemoneyModel;
use Think\Controller;
use Admin\Model\WebsiteModel;
use Admin\Model\AdminuserModel;
use Admin\Model\MoneychangeModel;
//use User\Model\SettleconfigModel;
use Admin\Model\SettleconfigModel;

class SettleController extends CommonController

{
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
        $memberid = trim(I("post.memberid", ""));
        if ($memberid <> "") {
            $where[$i] = "(memberid like '%" . $memberid . "%')";
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
//        if ($apply_start <> "") {
//            $where[$i] = "DATEDIFF('" . $apply_start . "',applytime) <= 0";
//            $i++;
//        }
//
//        if ($apply_end <> "") {
//            $where[$i] = "DATEDIFF('" . $apply_end . "',applytime) >= 0";
//            $i++;
//        }
        if ($apply_start && $apply_end) {
            $where[$i] = "unix_timestamp( applytime ) between unix_timestamp( '{$apply_start} ') and unix_timestamp( '{$apply_end}' )";
            $i++;
        }
        $deal_start = I("post.deal_start", "");
        $deal_end = I("post.deal_end", "");
//        if ($deal_start <> "") {
//            $where[$i] = "DATEDIFF('" . $deal_start . "',dealtime) <= 0";
//            $i++;
//        }
//
//        if ($deal_end <> "") {
//            $where[$i] = "DATEDIFF('" . $deal_end . "',dealtime) >= 0";
//            $i++;
//        }
        if ($deal_start && $deal_end) {
            $where[$i] = "unix_timestamp( dealtime ) between unix_timestamp( '{$deal_start} ') and unix_timestamp( '{$deal_end}' )";
            $i++;
        }
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
        //判断当前管理员有没有查看用户隐私数据的权限，有则显示全部，没有则显示部分
        $admin_id = session('admin_info.id');
        $privacy_id = AdminuserModel::findPrivacyId($admin_id);
        $user_pri = explode(',', $privacy_id);
        //银行卡号处理，读取前6位和后4，中间*号代替
        foreach ($datalist as $k => $v) {
            //如果用户名不在权限内
            if (!in_array(1, $user_pri)) {
                $datalist[$k]['bankusername'] = mb_substr($v['bankusername'], 0, 1, 'utf-8') . '**';
            }
            //如果手机号不在权限内
            if (!in_array(2, $user_pri)) {
                $datalist[$k]['phonenumber'] = substr($v['phonenumber'], 0, 3) . '****' . substr($v['phonenumber'], -4);
            }
            //如果身份证不在权限内
            if (!in_array(3, $user_pri)) {
                $datalist[$k]['identitynumber'] = substr($v['identitynumber'], 0, 4) . '****' . substr($v['identitynumber'], -4);
            }
            //如果银行卡号不在权限内
            if (!in_array(4, $user_pri)) {
                $datalist[$k]['bankcardnumber'] = substr($v['bankcardnumber'], 0, 6) . '****' . substr($v['bankcardnumber'], -4);
            }
            //处理订单金额和冻结金额为两位小数点
            $datalist[$k]['ordermoney'] = substr($v['ordermoney'], 0, strlen($v['ordermoney']) - 2);
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
        $memberid = trim(I("get.memberid", ""));
        if ($memberid <> "") {
            $where[$i] = "(memberid like '%" . $memberid . "%')";
            $i++;
        }
        $bankname = trim(I("get.bankname", ""));
        if ($bankname <> "") {
            $where[$i] = "(bankname like '%" . $bankname . "%')";
            $i++;
        }
        $ordernumber = trim(I("get.ordernumber", ""));
        if ($ordernumber <> "") {
            $where[$i] = "(ordernumber like '%" . $ordernumber . "%')";
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
        $bankusername = trim(I("get.bankusername", ""));
        if ($bankusername <> "") {
            $where[$i] = "(bankusername like '%" . $bankusername . "%')";
            $i++;
        }

        $phonenumber = trim(I("get.phonenumber", ""));
        if ($phonenumber <> "") {
            $where[$i] = "(phonenumber like '%" . $phonenumber . "%')";
            $i++;
        }

        $identitynumber = trim(I("get.identitynumber", ""));
        if ($identitynumber <> "") {
            $where[$i] = "(identitynumber like '%" . $identitynumber . "%')";
            $i++;
        }

        $bankcardnumber = trim(I("get.bankcardnumber", ""));
        if ($bankcardnumber <> "") {
            $where[$i] = "(bankcardnumber like '%" . $bankcardnumber . "%')";
            $i++;
        }
        $money_start = I("get.money_start", "");
        if ($money_start <> "") {
            $where[$i] = "ordermoney >= " . $money_start;
            $i++;
        }
        $money_end = I("get.money_end", "");
        if ($money_end <> "") {
            $where[$i] = "ordermoney <= " . $money_end;
            $i++;
        }

        $datalist = M('settleinfo')->where($where)->select();
        $all_status = C('SETTLESTATUS');
        $all_refundstatus = C('REFUNDSETTLESTATUS');
        $admin_id = session('admin_info.id');
        $privacy_id = AdminuserModel::findPrivacyId($admin_id);
        $user_pri = explode(',', $privacy_id);
        foreach ($datalist as $k => $v) {
            $datalist[$k]['status'] = $all_status[$v['status']];
            $datalist[$k]['refundstatus'] = $all_refundstatus[$v['refundstatus']];
            if ($v['deduction_type'] == 1) {
                $datalist[$k]['deduction_type'] = '外扣';
            } else {
                $datalist[$k]['deduction_type'] = '内扣';
            }
            //如果开户名不在权限内
            if (!in_array(1, $user_pri)) {
                $datalist[$k]['bankusername'] = mb_substr($v['bankusername'], 0, 1, 'utf-8') . '**';
            }
            //如果手机号不在权限内
            if (!in_array(2, $user_pri)) {
                $datalist[$k]['phonenumber'] = substr($v['phonenumber'], 0, 3) . '****' . substr($v['phonenumber'], -4);
            }
            //如果身份证不在权限内
            if (!in_array(3, $user_pri)) {
                $datalist[$k]['identitynumber'] = substr($v['identitynumber'], 0, 4) . '****' . substr($v['identitynumber'], -4);
            }
            //如果银行卡号不在权限内
            if (!in_array(4, $user_pri)) {
                $datalist[$k]['bankcardnumber'] = substr($v['bankcardnumber'], 0, 6) . '****' . substr($v['bankcardnumber'], -4);
            }

        }
        $title = '结算记录表';
        $menu_zh = array('商户号', '订单号', '结算金额', '订单手续费', '到账金额', '扣款方式', '银行名称', '银行卡号',
            '开户名', '手机号', '申请时间', '处理时间', '结算状态', '退款状态');
        $menu_en = array('memberid', 'ordernumber', 'ordermoney', 'moneytrade', 'money', 'deduction_type', 'bankname', 'bankcardnumber',
            'bankusername', 'phonenumber', 'applytime', 'dealtime', 'status', 'refundstatus');
        $config = array('RowHeight' => 25, 'Width' => 20);
        $this->addAdminOperate("管理员[" . session('admin_info.user_name') . "]导出了结算记录列表");
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
        $order_info['shangjianame'] = $this->getNameById('payapishangjia', $order_info['shangjiaid'], 'shangjianame');
        $order_info['payapiname'] = $this->getNameById('daifu', $order_info['daifuid'], 'zh_payname');
        $order_info['accountname'] = $this->getNameById('payapiaccount', $order_info['accountid'], 'bieming');
        $order_info['status_name'] = $status[$order_info['status']];
        $order_info['refundstatus_name'] = $refund_status[$order_info['refundstatus']];
        if ($order_info['deduction_type'] == 1) {
            $order_info['deduction_type'] = '外扣';
        } else {
            $order_info['deduction_type'] = '内扣';
        }
        $admin_id = 12;
        $privacy_id = AdminuserModel::findPrivacyId($admin_id);
        $user_pri = explode(',', $privacy_id);
        if (!in_array(1, $user_pri)) {
            $order_info['bankusername'] = mb_substr($order_info['bankusername'], 0, 1, 'utf-8') . '**';
        }
        //如果手机号不在权限内
        if (!in_array(2, $user_pri)) {
            $order_info['phonenumber'] = substr($order_info['phonenumber'], 0, 3) . '****' . substr($order_info['phonenumber'], -4);
        }
        //如果身份证不在权限内
        if (!in_array(3, $user_pri)) {
            $order_info['identitynumber'] = substr($order_info['identitynumber'], 0, 4) . '****' . substr($order_info['identitynumber'], -4);
        }
        //如果银行卡号不在权限内
        if (!in_array(4, $user_pri)) {
            $order_info['bankcardnumber'] = substr($order_info['bankcardnumber'], 0, 6) . '****' . substr($order_info['bankcardnumber'], -4);
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

    //操作：处理
    public function editSettleStatus()
    {
        $id = I('get.settleid');
        $this->assign('id', $id);

        //查询订单原始状态
        $status = M('settle')->where('id=' . $id)->getField('status');
        $this->assign('status', $status);

        //查询所有状态
        $allStatus = C('SETTLESTATUS');
        $this->assign('allStatus', $allStatus);

        $this->display();
    }

    //修改订单状态处理程序
    public function settleStatusEdit()
    {
        $id = I('id');
        //$ordernumber = SettleModel::getSysordernumber($id);
        $settle = SettleModel::getInfo($id);
        $settlemoney = SettlemoneyModel::getInfo($settle['ordernumber']);
        $daifu = DaifuModel::getInfo($settlemoney['daifuid']);
        $msg = "修改结算订单[" . $settle['ordernumber'] . "]状态:";
        $status = I('post.status', 0, 'intval');
        if (!$status) {
            $this->ajaxReturn(['status' => 'no', 'msg' => "请选择处理状态", 'id' => $id], 'json', JSON_UNESCAPED_UNICODE);
        }
        $oldstatus = M('settle')->where('id=' . $id)->getField('status');
        if ($status == $oldstatus) {
            $this->ajaxReturn(['status' => 'no', 'msg' => "状态未修改", 'id' => $id], 'json', JSON_UNESCAPED_UNICODE);
        }
        //
        $checksettle = checksettle($settle['userid']);
        if (!$checksettle) {
            $this->ajaxReturn(['status' => 'no', 'msg' => "结算功能已关闭,请先开启结算!", 'id' => $id], 'json', JSON_UNESCAPED_UNICODE);
        }
        $settleconfig = SettleconfigModel::getUserSettleconfig($settle['userid']);
        //订单原状态为未处理
        if ($settle['status'] == 0) {
            if ($status == 1) {
                $stat = "修改为处理中";
                $result = A("Daifu/" . $daifu['en_payname'], 'Controller');
                //根据具体代付接口处理逻辑,处理代付订单
                $res = M('settle')->where('id=' . $settle['id'])->setField([
                    'status' => 1,
                    'dealtime' => date('Y-m-d H:i:s')
                ]);
            } elseif ($status == 2) {
                $stat = "修改为已打款";
                $res = M('settle')->where('id=' . $settle['id'])->setField([
                    'status' => 2,
                    'dealtime' => date('Y-m-d H:i:s')
                ]);
            }
        }

        if ($settle['status'] == 1) {
            if ($status == 2) {
                $stat = "修改为已打款";
                $res = M('settle')->where('id=' . $settle['id'])->setField([
                    'status' => 2,
                    'dealtime' => date('Y-m-d H:i:s')
                ]);
            }
        }

        if ($res) {
            $this->addAdminOperate($msg . $stat . ',修改成功');
            $this->ajaxReturn(['status' => 'ok', 'msg' => "修改成功", 'id' => $id]);
        } else {
            $this->addAdminOperate($msg . $stat . ',修改失败');
            $this->ajaxReturn(['status' => 'no', 'msg' => "修改失败"]);
        }
    }

    //退款页面
    public function seeSettleRefund()
    {
        $order_info = M('settleinfo')->where('settleid=' . I('settleid'))->find();
        $status = C('SETTLESTATUS');
        $refund_status = C('REFUNDSETTLESTATUS');
        $order_info['username'] = $this->getNameById('user', $order_info['userid'], 'username');
        $order_info['status'] = $status[$order_info['status']];
        $order_info['refundstatus'] = $refund_status[$order_info['refundstatus']];
        if ($order_info['deduction_type'] == 1) {
            $order_info['deduction_type'] = '外扣';
        } else {
            $order_info['deduction_type'] = '内扣';
        }
        $admin_id = session('admin_info.id');
        $privacy_id = AdminuserModel::findPrivacyId($admin_id);
        $user_pri = explode(',', $privacy_id);
        if (!in_array(1, $user_pri)) {
            $order_info['bankusername'] = mb_substr($order_info['bankusername'], 0, 1, 'utf-8') . '**';
        }
        //如果手机号不在权限内
        if (!in_array(2, $user_pri)) {
            $order_info['phonenumber'] = substr($order_info['phonenumber'], 0, 3) . '****' . substr($order_info['phonenumber'], -4);
        }
        //如果身份证不在权限内
        if (!in_array(3, $user_pri)) {
            $order_info['identitynumber'] = substr($order_info['identitynumber'], 0, 4) . '****' . substr($order_info['identitynumber'], -4);
        }
        //如果银行卡号不在权限内
        if (!in_array(4, $user_pri)) {
            $order_info['bankcardnumber'] = substr($order_info['bankcardnumber'], 0, 6) . '****' . substr($order_info['bankcardnumber'], -4);
        }
        $this->assign('order_info', $order_info);
        //2019-4-18 rml：添加验证码
        $code_type = $this->getCodeType();
        $this->assign('code_type', $code_type);
        $this->display();
    }

    //退款处理程序
    //2019-4-18 rml：将管理密码修改为验证码
    public function refundConfirm()
    {
        $settle_id = I('settle_id');
        $ordernumber = SettleModel::getSysordernumber($settle_id);
        $msg = "结算订单[" . $ordernumber . "]退款:";
        $refundstatus = I('refundstatus');
        if (!$refundstatus) {
            $this->addAdminOperate($msg . ',管理员为选择是否同意退款就直接提交');
            $this->ajaxReturn(['status' => 'no', 'msg' => '请选择是否退款！']);
        }

        $verfiy_code = I('post.verfiy_code', '', 'trim');
        $code_type = I('post.code_type', 0, 'intval');
        $this->checkVerifyCode($verfiy_code, $code_type, $msg);
        $refundmoney = I('refundmoney');
        //同意退款
        if ($refundstatus == 2) {
            $stat = "管理员同意退款";
            if (!$refundmoney) {
                $this->addAdminOperate($msg . $stat . ',未输入退款金额');
                $this->ajaxReturn(['status' => 'no', 'msg' => "请输入退款金额！"]);
            }
            $order_info = M('settleinfo')->where('settleid=' . $settle_id)->find();
            //开启事务  //修改结算订单状态
            M()->startTrans();
            $settle_data = [
                'status' => 3,//已退款
                'refundstatus' => 2,
                'refundmoney' => $refundmoney,
                'refundremarks' => I('refundremarks'),
            ];
            $res_settle = M("settle")->where("id=" . $settle_id)->save($settle_data);

            //修改用户金额
            $changemoney = $refundmoney;
            $oldmoney = M("usermoney")->where("userid=" . $order_info["userid"])->getField("money"); //用户余额
            $nowmoney = $changemoney + $oldmoney;  //新增后的金额
            $res_money = M("usermoney")->where("userid=" . $order_info["userid"])->setField("money", $nowmoney);

            //添加资金变动记录
            $money_change = [
                'userid' => $order_info['userid'],   //用户id
                'oldmoney' => $oldmoney,   //原始金额
                'changemoney' => $changemoney,   //改变金额
                'nowmoney' => $nowmoney,   //改变后的金额
                'datetime' => date('Y-m-d H:i:s'),   //添加时间
                'transid' => $order_info['ordernumber'],   //订单号
                'changetype' => 8,   //金额变动类型
                'remarks' => '结算订单退款',   //备注
            ];

            $res_change = M('moneychange')->add($money_change);
            if ($res_settle && $res_money && $res_change) {
                $res = true;
                M()->commit();    //事务提交
            } else {
                $res = false;
                M()->rollback();  //回滚
            }
        }

        //拒绝退款
        if ($refundstatus == 3) {
            $stat = "管理员拒绝退款";
            $res = M('settle')->where('id=' . $settle_id)->setField('refundstatus', $refundstatus);
        }
        if ($res) {
            $this->addAdminOperate($msg . $stat . '退款金额为[' . $refundmoney . '],退款状态修改成功');
            $this->ajaxReturn(['status' => 'ok', 'msg' => "退款状态修改成功"]);
        } else {
            $this->addAdminOperate($msg . $stat . ',退款状态修改失败');
            $this->ajaxReturn(['status' => 'no', 'msg' => "退款状态修改失败"]);
        }

    }

    /**
     * 结算设置
     */
    //结算设置总页面
    public function settleSetup()
    {
        $this->display();
    }

    //基本设置页面
    public function settleConfig()
    {
        //读取结算设置数据
        //2019-4-18 rml：修改，查询记录，系统的，默认配置两个条件
        $config = SettleconfigModel::getSettleConfig(['user_id' => 0, 'user_type' => 0]);
        $this->assign('config', $config);

        //读取所有代付通道
        $daifu = DaifuModel::getDaifuidList(['status' => 1, 'del' => 0]);
        $this->assign('daifu', $daifu);

        if ($config['daifu_id']) {
            $account = $this->getDaifuAccount($config['daifu_id']);
            $this->assign('account', $account);
        }
        $code_type = $this->getCodeType();
        $this->assign('code_type', $code_type);
        $this->display();
    }

    //代付通道选择事件,查出相应的账号
    public function getAccount()
    {
        $daifu_id = I('daifu_id');
        $account = $this->getDaifuAccount($daifu_id);
        $this->ajaxReturn($account, 'json');
    }

    //2019-5-6 rml:提取公共部分:获取代付通道下的系统账号
    public function getDaifuAccount($daifu_id)
    {
        $shangjia_id = DaifuModel::getPayapiShangjiaId($daifu_id);
        //查询商家所有可用账号
        $where = [
            'payapishangjiaid' => $shangjia_id,
            'status' => 1,
            'del' => 0,
            'user_id' => 0,  //代表系统生成的账号
        ];
        $account = M('payapiaccount')->where($where)->field('id,bieming')->select();
        return $account;
    }

    //基本设置修改
    //2019-5-6  rml：添加金额限制判断
    public function settleConfigEdit()
    {
        $msg = "修改系统结算设置:";
        $data = I('post.');
        //如果是第一次添加,需要判断是否为空的情况
        if (!$data['day_start'] || !$data['day_end']) {
            $this->addAdminOperate($msg . '结算时间不得为空');
            $this->ajaxReturn(['status' => 'no', 'msg' => '结算时间不得为空']);
        }
        if ($data['day_start'] > $data['day_end']) {
            $this->addAdminOperate($msg . '选择的开始时间大于结束时间');
            $this->ajaxReturn(['status' => 'no', 'msg' => '开始时间大于结束时间']);
        }
        if ($data['min_money'] > $data['max_money']) {
            $this->addAdminOperate($msg . '输入的单笔最小金额超过单笔最大金额');
            $this->ajaxReturn(['status' => 'no', 'msg' => '单笔最小金额不能超过单笔最大金额']);
        }
        if ($data['max_money'] > $data['day_maxmoney']) {
            $this->addAdminOperate($msg . '输入的单笔最大金额超过当日最大金额');
            $this->ajaxReturn(['status' => 'no', 'msg' => '单笔最大金额不能超过当日最大金额']);
        }

        //当日提款最大金额不得超过9999999999.0000
        if ($data['day_maxmoney'] > 9999999999) {
            $this->addAdminOperate($msg . '当日提款最大金额不得超过9999999999');
            $this->ajaxReturn(['status' => 'no', 'msg' => '当日提款最大金额不得超过9999999999']);
        }

        if (!$data['day_maxnum']) {
            $this->addAdminOperate($msg . '当日提款最大次数不得为空');
            $this->ajaxReturn(['status' => 'no', 'msg' => '当日提款最大次数不得为空']);
        }

        //当日提款最大次数不得超过9999
        if ($data['day_maxnum'] > 9999) {
            $this->addAdminOperate($msg . '当日提款最大次数不得超过9999');
            $this->ajaxReturn(['status' => 'no', 'msg' => '当日提款最大次数不得超过9999']);
        }

        //默认结算运营费率不得大于1
        if ($data['settle_feilv'] >= 1) {
            $this->addAdminOperate($msg . '默认结算运营费率不得大于1');
            $this->ajaxReturn(['status' => 'no', 'msg' => '默认结算运营费率不得大于1']);
        }

        //单笔最小手续费不得超过9999999999
        if ($data['settle_min_feilv'] > 9999999999) {
            $this->addAdminOperate($msg . '单笔最小手续费不得超过9999999999');
            $this->ajaxReturn(['status' => 'no', 'msg' => '单笔最小手续费不得超过9999999999']);
        }

        $verfiy_code = I('post.verfiy_code', '', 'trim');
        $code_type = I('post.code_type', 0, 'intval');
        $this->checkVerifyCode($verfiy_code, $code_type, $msg);
        $config = SettleconfigModel::getSettleConfig(['user_id' => 0, 'user_type' => 0]);
        $data['min_money'] = $data['min_money'] != '' ? $data['min_money'] : 0.0000;
        $data['max_money'] = $data['max_money'] != '' ? $data['max_money'] : 9999999999;
        $data['day_maxmoney'] = $data['day_maxmoney'] != '' ? $data['day_maxmoney'] : 9999999999;
        $data['settle_feilv'] = $data['settle_feilv'] != '' ? $data['settle_feilv'] : 0.0000;
        $data['min_fee'] = $data['min_fee'] != '' ? $data['min_fee'] : 0;
        if ($config) {
            //修改
            $res = SettleconfigModel::editSettleConfig(['id' => $config['id']], $data);
        } else {
//            print_r($data);die;
            //添加
            $data['user_id'] = 0;
            $data['user_type'] = 0;
            $res = SettleconfigModel::addSettleConfig($data);
        }
        if ($res) {
            $this->addAdminOperate($msg . '修改成功');
            $this->ajaxReturn(['status' => 'ok', 'msg' => '修改成功']);
        } else {
            $this->addAdminOperate($msg . '修改失败');
            $this->ajaxReturn(['status' => 'no', 'msg' => '修改失败,请稍后重试']);
        }

    }

    //日期设置页面
    public function settleDate()
    {
        //读取排除的日期
        $remove_date = SettledateModel::getSettleDate(1);
        $this->assign('remove_date', $remove_date);

        //读取节假日
        $holiday_date = SettledateModel::getSettleDate(2);
        $this->assign('holiday_date', $holiday_date);
        $this->display();
    }

    //日期设置修改:type=1:排除 2=添加
    //排除的日期和添加的日期不得存在同一个
    public function settleDateEdit()
    {
        $msg = "结算日期设置修改:";
        $type = I('get.type');
        if ($type == 1) {
            $stat = "排除节假日";
            $date = I('post.day_remove', '');
            if (!$date) {
                $this->ajaxReturn(['status' => 'no', 'msg' => '请选择日期']);
            }
            //判断是否存在添加的节假日中
            $count = SettledateModel::getCount($date, 2);
            if ($count) {
                $this->ajaxReturn(['status' => 'no', 'msg' => '该日期已存在于添加节假日中,不得作为排除日期']);
            }
            $data = [
                'user_id' => 0,
                'type' => 1,
                'date' => $date,
            ];

        } else {
            $stat = "添加节假日";
            $date = I('post.day_holiday');
            $remarks = I('post.remarks', '', 'trim');
            if (!$date) {
                $this->ajaxReturn(['status' => 'no', 'msg' => '请选择日期']);
            }
            //判断是否存在于排除日期中
            $count = SettledateModel::getCount($date, 1);
            if ($count) {
                $this->ajaxReturn(['status' => 'no', 'msg' => '该日期已存在于排除节假日中,不得作为添加日期']);
            }

            if ($remarks) {
                if (mb_strlen($remarks) > 6) {
                    $this->ajaxReturn(['status' => 'no', 'msg' => '节假日说明不能超过6个字符']);
                }
            }
            $data = [
                'user_id' => 0,
                'type' => 2,
                'date' => $date,
                'remarks' => $remarks,
            ];
        }
        $res = SettledateModel::addInfo($data);
        if ($res) {
            $this->addAdminOperate($msg . $stat . '添加成功');
            $this->ajaxReturn(['status' => 'ok', 'msg' => '添加成功']);
        } else {
            $this->addAdminOperate($msg . $stat . '添加失败');
            $this->ajaxReturn(['status' => 'no', 'msg' => '添加失败']);
        }
    }

    //日期的删除
    public function settleDateDelete()
    {
        $id = I('get.id');
        $msg = "结算日期的删除:";
        $type = SettledateModel::getDateType($id);
        $date = SettledateModel::getDate($id);
        if ($type == 1) {
            $stat = "删除之前排除的节假日[" . $date . "]";
        } else {
            $stat = "删除之前添加的节假日[" . $date . "]";
        }
        $res = M('settledate')->where('id=' . $id)->delete();
        if ($res) {
            $this->addAdminOperate($msg . $stat . ',删除成功');
            $this->ajaxReturn(['status' => 'ok', 'msg' => '删除成功']);
        } else {
            $this->addAdminOperate($msg . $stat . ',删除失败');
            $this->ajaxReturn(['status' => 'no', 'msg' => '删除失败']);
        }
    }

    public function query()
    {
        $id = I('id');
        $settle = SettleModel::getInfo($id);
        $settlemoney = SettlemoneyModel::getInfo($settle['ordernumber']);
        $daifu = DaifuModel::getInfo($settlemoney['daifuid']);
        $obj = A("Daifu/" . $daifu['en_payname'], 'Controller');

        if (method_exists($obj, 'query')) {
            //如果代付查询的方法存在  则调用代付查询的方法,验证订单,具体逻辑根据不同接口来
            $res = $obj->query($settle);
            /*
             *具体也去业务处理逻辑根据查询接口来处理
             */
            $this->ajaxReturn([
                'status' => "success",
                'msg' => $res['msg']
            ], 'json', JSON_UNESCAPED_UNICODE);
        } else {
            $this->ajaxReturn([
                'status' => "error",
                'msg' => '无查询API,验证失败!'
            ], 'json', JSON_UNESCAPED_UNICODE);
        }
    }
}