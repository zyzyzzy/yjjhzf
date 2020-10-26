<?php
/**
 * 用户自助通道账号交易记录
 */

namespace Admin\Controller;

use Admin\Model\MoneychangeModel;
use Admin\Model\OrdercomplaintModel;
use Admin\Model\OrderModel;
use Admin\Model\MoneyModel;
use Admin\Model\OrderfreezemoneyModel;


class UserOrderController extends CommonController
{
    //2019-4-3 任梦龙：用户自助通道账号交易数据
    //用户自助账号的交易记录页面
    public function userOrderList()
    {
        //搜索
        $statustype = C('STATUSTYPE');
        $this->assign('statustype', $statustype);

//        $shangjias = M('payapishangjia')->field('id,shangjianame')->select();
//        $this->assign('shangjias', $shangjias);

        $payapis = M('payapi')->field('id,zh_payname')->select();
        $this->assign('payapis', $payapis);

        $accounts = M('payapiaccount')->field('id,bieming')->select();
        $this->assign('accounts', $accounts);

        //查找用户列表
        $user_list = M('user')->field('id,username')->select();
        $this->assign('user_list', $user_list);

        //提交时间
        //查询所有提交时间的类型
        $all_open_type = C('ORDEROPENTYPE');
        $this->assign('all_open_type', $all_open_type);
        //查询当前用户的提交时间
        $admin_id = session('admin_info.id');
        $admin_id = $admin_id > 0 ? $admin_id : 0;
        $admin_open_type = M('orderopentype')->where('user_id=' . $admin_id . ' and user_type="admin"')->getField('order_open_type');
        $this->assign('admin_open_type', $admin_open_type);
        $this->assign('admin_id', $admin_id);
        //获取提交日期
        if ($admin_open_type) {
            $open_start = date("Y-m-d", strtotime('-' . ($admin_open_type - 1) . " day")) . " 00:00:00";
            $open_end = date("Y-m-d") . " 23:59:59";
        }

        $this->assign('open_start', $open_start);
        $this->assign('open_end', $open_end);
        $this->display();
    }

    //加载列表
    //当前用户：自助通道账号的交易列表
    public function loadUserOrderList()
    {
        $where = [];
        $where['users_id'] = ['gt', 0];
        $i = 1;
        $users_id = I("post.users_id", "");
        if ($users_id <> "") {
            $where[$i] = 'users_id=' . $users_id;
            $i++;
        }
        $memberid = I("post.memberid", "", 'trim');
        if ($memberid <> "") {
            $where[$i] = "(memberid like '" . $memberid . "%')";
            $i++;
        }
        $userip = I("post.userip", "", 'trim');
        if ($userip <> "") {
            $where[$i] = "(userip like '" . $userip . "%')";
            $i++;
        }
        $userordernumber = I("post.userordernumber", "", 'trim');
        if ($userordernumber <> "") {
            $where[$i] = "(userordernumber like '" . $userordernumber . "%')";
            $i++;
        }
//        $sysordernumber = I("post.sysordernumber", "",'trim');
//        if ($sysordernumber <> "") {
//            $where[$i] = "(sysordernumber like '" . $sysordernumber . "%')";
//            $i++;
//        }
//        $shangjiaid = I("post.shangjiaid", "");
//        if ($shangjiaid <> "") {
//            $where[$i] = "shangjiaid = " . $shangjiaid;
//            $i++;
//        }
        $payapiid = I("post.payapiid", "");
        if ($payapiid <> "") {
            $where[$i] = "payapiid = " . $payapiid;
            $i++;
        }
//        $payapiaccountid = I("post.accountid", "");
//        if ($payapiaccountid <> "") {
//            $where[$i] = "payapiaccountid = " . $payapiaccountid;
//            $i++;
//        }
        $status = I("post.status", "");
        if ($status <> "") {
            $where[$i] = "status = " . $status;
            $i++;
        }
        $complaint = I("post.complaint", "");
        if ($complaint <> "") {
            $where[$i] = "complaint = " . $complaint;
            $i++;
        }
        $type = I("post.ordertype", "");
        if ($type <> "") {
            $where[$i] = "type = " . $type;
            $i++;
        }
        $start = I("post.start", "");
        if ($start <> "") {
            $where[$i] = "DATEDIFF('" . $start . "',datetime) <= 0";
            $i++;
        }
        $end = I("post.end", "");
        if ($end <> "") {
            $where[$i] = "DATEDIFF('" . $end . "',datetime) >= 0";
            $i++;
        }
        $success_start = I("post.success_start", "");
        if ($success_start <> "") {
            $where[$i] = "DATEDIFF('" . $success_start . "',successtime) <= 0";
            $i++;
        }
        $success_end = I("post.success_end", "");
        if ($success_end <> "") {
            $where[$i] = "DATEDIFF('" . $success_end . "',successtime) >= 0";
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

        //统计
        $where1 = $where;
        $where1['status'] = ['gt', 0];
        $where1['type'] = 0;
        $sum_ordermoney = M('orderinfo')->where($where1)->sum('true_ordermoney');//订单总金额
        $sum_costmoney = M('orderinfo')->where($where1)->sum('moneycost');//成本金额
        $sum_trademoney = M('orderinfo')->where($where1)->sum('moneytrade');//手续费
        $sum_money = M('orderinfo')->where($where1)->sum('money');//到账金额
        $sum_freezemoney = M('orderinfo')->where($where1)->sum('freezemoney');//冻结金额
        $count_success = M('orderinfo')->where($where1)->count();//成功笔数
        $where2 = $where;
        $where2['status'] = 0;
        $where2['type'] = 0;
        $count_fail = M('orderinfo')->where($where2)->count();//失败笔数
        $where3 = $where;
        $where3['type'] = 1;
        $count_test = M('orderinfo')->where($where3)->count();//测试笔数
        $success_rate = sprintf("%.2f", ($count_success / ($count_success + $count_fail)) * 100) . "%";

        //总页数
        $count = M('orderinfo')->where($where)->count();
        $page = I('post.page');
        $limit = I('post.limit');
        $datalist = M('orderinfo')->where($where)->page($page, $limit)->order('orderid DESC')->select();
        //查询数据
        $statustype = C('STATUSTYPE');
        foreach ($datalist as $k => $v) {
            $datalist[$k]['username'] = $this->getNameById('user', $v['users_id'], 'username');
            $datalist[$k]['payapiname'] = $this->getNameById('payapi', $v['payapiid'], 'zh_payname');
            $datalist[$k]['accountname'] = $this->getNameById('payapiaccount', $v['payapiaccountid'], 'bieming');
//            $datalist[$k]['shangjianame'] = $this->getNameById('payapishangjia',$v['shangjiaid'],'shangjianame');
//            $datalist[$k]['accountname'] = $this->getNameById('payapiaccount',$v['payapiaccountid'],'bieming');
//            $payapiclassid = M('payapi')->where('id=' . $v['payapiid'])->getField('payapiclassid');
//            if ($payapiclassid) {
//                $datalist[$k]['payapiclassname'] = M('payapiclass')->where('id=' . $payapiclassid)->getField('classname');
//            }
            $datalist[$k]['statusname'] = $statustype[$v['status']];
            //处理订单金额和冻结金额为两位小数点
            $datalist[$k]['new_ordermoney'] = substr($v['ordermoney'], 0, strlen($v['ordermoney']) - 2);
            $datalist[$k]['new_true_ordermoney'] = substr($v['true_ordermoney'], 0, strlen($v['true_ordermoney']) - 2);
            $datalist[$k]['new_moneytrade'] = substr($v['moneytrade'], 0, strlen($v['moneytrade']) - 2);
            $datalist[$k]['new_money'] = $v['money'];
            $datalist[$k]['new_freezemoney'] = $v['freezemoney'];
        }
        if (!empty($datalist)) {
            $datalist[0]['sum_ordermoney'] = $sum_ordermoney ? $sum_ordermoney : 0;
            $datalist[0]['sum_costmoney'] = $sum_costmoney ? $sum_costmoney : 0;
            $datalist[0]['sum_trademoney'] = $sum_trademoney ? $sum_trademoney : 0;
            $datalist[0]['sum_money'] = $sum_money ? $sum_money : 0;
            $datalist[0]['sum_freezemoney'] = $sum_freezemoney ? $sum_freezemoney : 0;
            $datalist[0]['count_success'] = $count_success;
            $datalist[0]['count_fail'] = $count_fail;
            $datalist[0]['count_test'] = $count_test;
            $datalist[0]['success_rate'] = $success_rate > 0 ? $success_rate : 0;
        }

        //分页
        $ReturnArr = [
            'code' => 0,
            'msg' => '数据加载成功', //响应结果
            'count' => $count, //总页数
            'data' => $datalist
        ];
        $this->ajaxReturn($ReturnArr, 'json');
    }

    //导出自助通道账号交易数据列表
    public function downloadUserOrder()
    {
        $where = [];
        $where['users_id'] = ['gt', 0];
        $i = 1;
        $users_id = I("get.users_id", "");
        if ($users_id <> "") {
            $where[$i] = 'users_id=' . $users_id;
            $i++;
        }
        $memberid = I("get.memberid", "", 'trim');
        if ($memberid <> "") {
            $where[$i] = "(memberid like '" . $memberid . "%')";
            $i++;
        }
        $userip = I("get.userip", "", 'trim');
        if ($userip <> "") {
            $where[$i] = "(userip like '" . $userip . "%')";
            $i++;
        }
        $userordernumber = I("get.userordernumber", "", 'trim');
        if ($userordernumber <> "") {
            $where[$i] = "(userordernumber like '" . $userordernumber . "%')";
            $i++;
        }
//        $sysordernumber = I("post.sysordernumber", "",'trim');
//        if ($sysordernumber <> "") {
//            $where[$i] = "(sysordernumber like '" . $sysordernumber . "%')";
//            $i++;
//        }
//        $shangjiaid = I("post.shangjiaid", "");
//        if ($shangjiaid <> "") {
//            $where[$i] = "shangjiaid = " . $shangjiaid;
//            $i++;
//        }
        $payapiid = I("get.payapiid", "");
        if ($payapiid <> "") {
            $where[$i] = "payapiid = " . $payapiid;
            $i++;
        }
//        $payapiaccountid = I("post.accountid", "");
//        if ($payapiaccountid <> "") {
//            $where[$i] = "payapiaccountid = " . $payapiaccountid;
//            $i++;
//        }
        $status = I("get.status", "");
        if ($status <> "") {
            $where[$i] = "status = " . $status;
            $i++;
        }
        $complaint = I("get.complaint", "");
        if ($complaint <> "") {
            $where[$i] = "complaint = " . $complaint;
            $i++;
        }
        $type = I("get.ordertype", "");
        if ($type <> "") {
            $where[$i] = "type = " . $type;
            $i++;
        }
        $start = I("get.start", "");
        if ($start <> "") {
            $where[$i] = "DATEDIFF('" . $start . "',datetime) <= 0";
            $i++;
        }
        $end = I("get.end", "");
        if ($end <> "") {
            $where[$i] = "DATEDIFF('" . $end . "',datetime) >= 0";
            $i++;
        }
        $success_start = I("get.success_start", "");
        if ($success_start <> "") {
            $where[$i] = "DATEDIFF('" . $success_start . "',successtime) <= 0";
            $i++;
        }
        $success_end = I("get.success_end", "");
        if ($success_end <> "") {
            $where[$i] = "DATEDIFF('" . $success_end . "',successtime) >= 0";
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

        //查询数据
        $datalist = M('orderinfo')->where($where)->select();
        $statustype = C('STATUSTYPE');
        foreach ($datalist as $k => $v) {
            if ($v['type'] == 1) {
                $datalist[$k]['type'] = '测试';
            } else {
                $datalist[$k]['type'] = '交易';
            }
            if ($v['complaint'] == 1) {
                $datalist[$k]['complaint'] = '已投诉';
            } elseif ($v['complaint'] == 2) {
                $datalist[$k]['complaint'] = '已投诉';
            } else {
                $datalist[$k]['complaint'] = '正常';
            }
            $datalist[$k]['username'] = $this->getNameById('user', $v['users_id'], 'username');
            $datalist[$k]['payapiname'] = $this->getNameById('payapi', $v['payapiid'], 'zh_payname');
            $datalist[$k]['accountname'] = $this->getNameById('payapiaccount', $v['payapiaccountid'], 'bieming');
//            $payapiclassid = M('payapi')->where('id=' . $v['payapiid'])->getField('payapiclassid');
//            if ($payapiclassid) {
//                $datalist[$k]['payapiclassname'] = M('payapiclass')->where('id=' . $payapiclassid)->getField('classname');
//            }
            $datalist[$k]['status'] = $statustype[$v['status']];
        }
        $title = '交易记录表';
        $menu_zh = array('用户名', '商户号', '用户订单号', '提交金额', '实际金额', '冻结金额', '到账金额', '通道名称', '通道账号', '订单时间', '成功时间', '状态', '投诉状态', '类型');
        $menu_en = array('username', 'memberid', 'userordernumber', 'ordermoney', 'true_ordermoney', 'freezemoney', 'money', 'payapiname', 'accountname', 'datetime', 'successtime', 'status', 'complaint', 'type');
        $config = array('RowHeight' => 25, 'Width' => 20);
        $this->addAdminOperate('管理员[' . session('admin_info.user_name') . ']导出了自助通道账号交易记录列表');
        DownLoadExcel($title, $menu_zh, $menu_en, $datalist, $config);
    }

    //通过id获取名称
    public function getNameById($table, $where, $field)
    {
        return M($table)->where('id=' . $where)->getField($field);
    }

    //修改提交时间
    public function changeOpenType()
    {
        $msg = "修改默认提交时间:";
        $data['user_id'] = I('post.admin_id');
        $data['order_open_type'] = I('post.order_open_type');
        $all_type = C('ORDEROPENTYPE');
        $stat = "修改为[" . $all_type[$data["order_open_type"]] . "],";
        $data['user_type'] = 'admin';
        $where = [
            'user_id' => I('post.admin_id'),
            'user_type' => 'admin'
        ];
        $old = M('orderopentype')->where($where)->find();
        if ($old) {
            //判断是否修改
            if ($old['order_open_type'] != I('post.order_open_type')) {
                $res = M('orderopentype')->where($where)->save($data);
            } else {
                $res = true;
            }
        } else {
            //添加
            $res = M('orderopentype')->add($data);
        }
        if ($res) {
            $this->addAdminOperate($msg . $stat . '修改成功');
            $this->ajaxReturn(['msg' => "提交时间修改成功!", 'status' => 'ok'], "json");
        }
        $this->addAdminOperate($msg . $stat . '修改失败');
        $this->ajaxReturn(['msg' => "提交时间修改失败!", 'status' => 'no'], "json");
    }

    //测试：修改订单状态
    public function changeType()
    {
        $type = I('get.type');
        $orderid = I('get.orderid');
        $sysordernumber = OrderModel::getSysordernumber($orderid);
        $msg = "修改订单[" . $sysordernumber . "]类型:";
        if ($type == 1) {
            $stat = "将订单类型改为交易";
            $data['type'] = 0;
        } else {
            $stat = "将订单类型改为测试";
            $data['type'] = 1;
        }
        $res = M('order')->where('id=' . $orderid)->save($data);
        if ($res) {
            $this->addAdminOperate($msg . $stat . ',修改成功');
            $this->ajaxReturn(['status' => 'ok', 'msg' => '修改成功']);
        } else {
            $this->addAdminOperate($msg . $stat . ',修改失败');
            $this->ajaxReturn(['status' => 'no', 'msg' => '修改失败']);
        }
    }

    //操作：查看
    public function seeOrderInfo()
    {
        $order = M('order')->where('id=' . I('orderid'))->find();
        $order_info = M('orderinfo')->where('orderid=' . I('orderid'))->find();
        $statustype = C('STATUSTYPE');
        $order_info['username'] = $this->getNameById('user', $order_info['userid'], 'username');
        $order_info['shangjianame'] = $this->getNameById('payapishangjia', $order_info['shangjiaid'], 'shangjianame');
        $order_info['payapiname'] = $this->getNameById('payapi', $order_info['payapiid'], 'zh_payname');
        $order_info['accountname'] = $this->getNameById('payapiaccount', $order_info['payapiaccountid'], 'bieming');
        $order_info['status_name'] = $statustype[$order_info['status']];
        $order_info['callbackurl'] = $order['callbackurl'];
        $order_info['notifyurl'] = $order['notifyurl'];
        if ($order_info['type'] == 1) {
            $order_info['type'] = '测试';
        } else {
            $order_info['type'] = '交易';
        }
        if ($order_info['complaint'] == 1) {
            $order_info['complaint'] = '已投诉';
        } elseif ($order_info['complaint'] == 2) {
            $order_info['complaint'] = '已撤诉';
        } else {
            $order_info['complaint'] = '正常';
        }
        //添加资金变动记录
        if ($order_info['status'] > 0) {
            $change = MoneychangeModel::getInfoBySysordernumber($order_info['sysordernumber']);
            $order_info['oldmoney'] = $change['oldmoney'];
            $order_info['changemoney'] = $change['changemoney'];
            $order_info['nowmoney'] = $change['nowmoney'];
        }
        $this->assign('order_info', $order_info);
        $this->display();
    }

    //投诉状态的修改页面
    public function changeComplaint()
    {
        $orderid = I('get.orderid');
        $this->assign('orderid', $orderid);

        //查询订单的投诉状态
        $complaint = M('order')->where('id=' . $orderid)->getField('complaint');
        $this->assign('complaint', $complaint);

        $this->display();
    }

    //加载投诉状态的过程表格数据
    public function loadComplaint()
    {
        $order_id = I('get.orderid');
        //总页数
        $count = OrdercomplaintModel::countComplaint($order_id);
        $datalist = OrdercomplaintModel::getComplaint($order_id);
        //分页
        $ReturnArr = [
            'code' => 0,
            'msg' => '数据加载成功', //响应结果
            'count' => $count, //总页数
            'data' => $datalist
        ];
        $this->ajaxReturn($ReturnArr, 'json');
    }

    //投诉状态修改的处理程序
    public function complaintChange()
    {
        $orderid = I('orderid');
        $sysordernumber = OrderModel::getSysordernumber($orderid);
        $msg = "修改订单[" . $sysordernumber . "]的投诉状态:";
        $complaint = I('complaint');
        if ($complaint == 0) {
            $stat = "修改为正常";
        } elseif ($complaint == 1) {
            $stat = "修改为已投诉";
        } else {
            $stat = "修改为已撤诉";
        }
        $remarks = I('remarks');
        $order = M('order')->where('id=' . $orderid)->find();
        //查询订单的原始投诉状态
        $old_complaint = $order['complaint'];
        //修改状态
        $res = M('order')->where('id=' . $orderid)->save(['complaint' => $complaint]);
        //查询之前是否投诉过
        $is_complaint = OrdercomplaintModel::isComplaint($order['id']);
        if (!$is_complaint && $complaint == 1) {
            $user_id = $order['userid'];
            $freeze_money = $order['true_ordermoney'];
            //查询用户余额
            $user_money = M('usermoney')->lock(true)->where('userid=' . $user_id)->find();
            if ($user_money['money'] < $freeze_money) {
                $freeze_money = $user_money['money'];
            }
            $old_freeze_money = $user_money['freezemoney'];
            $result = M('usermoney')->lock(true)->where('userid=' . $user_id)->save(
                ['money' => $user_money['money'] - $freeze_money, 'freezemoney' => $old_freeze_money + $freeze_money]);

            if ($result) {
                //moneychange表添加记录
                $da = [
                    'userid' => $user_id,
                    'oldmoney' => $user_money['money'],
                    'changemoney' => $freeze_money,
                    'nowmoney' => $user_money['money'] - $freeze_money,
                    'changetype' => 2,
                    'remarks' => '用户订单号为' . $order['userordernumber'] . '的订单被投诉,订单金额冻结',
                    'datetime' => date('Y-m-d H:i:s')
                ];
                M('moneychange')->lock(true)->add($da);

                //orderfreezemoney表添加记录
                $data['freeze_money'] = $freeze_money;
                $data['date_time'] = date('Y-m-d H:i:s');
                $data['user_id'] = $user_id;
                $data['freeze_type'] = 2;//投诉冻结
                $data['order_status'] = 1;//手动冻结
                $data['freezeordernumber'] = Createfreezeordernumber(36);
                M('orderfreezemoney')->add($data);
            }
        }
        if ($res) {
            //添加修改记录
            $data = [
                'order_id' => $orderid,
                'admin_id' => session('admin_info.id'),
                'change_status' => $complaint,
                'old_status' => $old_complaint,
                'date_time' => date('Y-m-d H:i:s'),
                'remarks' => $remarks,
            ];
            OrdercomplaintModel::addComplaint($data);
            $this->addAdminOperate($msg . $stat . ',修改成功');
            $this->ajaxReturn(['status' => 'ok', 'msg' => '修改成功']);
        } else {
            $this->addAdminOperate($msg . $stat . ',修改失败');
            $this->ajaxReturn(['status' => 'no', 'msg' => '修改失败']);
        }
    }

    //查看冻结金额明细记录
    public function seeOrderFreezeMoney()
    {
        $orderid = I('orderid');
        //查询订单金额表的id和冻结金额
        $sysordernumber = M('order')->where('id=' . $orderid)->getField('sysordernumber');
        $ordermoney = M('ordermoney')->where('sysordernumber="' . $sysordernumber . '"')->find();
        $this->assign('ordermoney', $ordermoney);
        $this->display();
    }

    //加载冻结金额明细记录
    public function loadFreezeMoneyList()
    {
        $datalist = M('orderfreezemoney')->where('ordermoney_id=' . I('get.ordermoneyid'))->select();
        foreach ($datalist as $k => $v) {
            $moneytype = M('moneytype')->where('id=' . $v['moneytype_id'])->find();
            $datalist[$k]['moneytypename'] = $moneytype['moneytypename'];
            $datalist[$k]['dzsj_day'] = $moneytype['dzsj_day'];
            $datalist[$k]['jiejiar'] = $moneytype['jiejiar'];
            $datalist[$k]['dzbl'] = $moneytype['dzbl'];
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
        $count = count($datalist);
        $ReturnArr['count'] = $count;
        $ReturnArr['data'] = $datalist;
        $this->ajaxReturn($ReturnArr, 'json');
    }

    //手动解冻
    public function manualUnfreeze()
    {
        $id = I('id');
        $freezemoney = OrderfreezemoneyModel::getInfo($id);
        $msg = "冻结订单号为[" . $freezemoney['freezeordernumber'] . "]的冻结订单手动解冻:";
        //添加金额变动记录
        $user_money = MoneyModel::getUserMoneyInfo($freezemoney['user_id']);
        $changemoney = $freezemoney['freeze_money'];
        $nowmoney = $user_money['money'] + $freezemoney['freeze_money'];
        $data = [
            'userid' => $freezemoney['user_id'],
            'oldmoney' => $user_money['money'],
            'changemoney' => $changemoney,
            'nowmoney' => $nowmoney,
            'changetype' => 3,
            'remarks' => "交易冻结金额手动解冻",
            'datetime' => date('Y-m-d H:i:s')
        ];
        MoneyModel::addMoneyChange($data);

        //修改用户金额
        MoneyModel::changeUserMoney($freezemoney['user_id'], ['money' => ($user_money['money'] + $freezemoney['freeze_money'])]);
        MoneyModel::changeUserMoney($freezemoney['user_id'], ['freezemoney' => ($user_money['freezemoney'] - $freezemoney['freeze_money'])]);

        //手动解冻记录表中添加记录
        $data1['freezemoney_id'] = $id;
        $data1['adminuser_id'] = session('admin_info.id');
        $data1['adminuser_ip'] = getIp();
        $data1['date_time'] = date('Y-m-d H:i:s');
        M('manualunfreeze')->add($data1);

        //修改orderfreezemoney表记录
        $up['actual_time'] = date('Y-m-d H:i:s');
        $up['unfreeze'] = 1;
        $up['unfreeze_type'] = 1;
        $res1 = M('orderfreezemoney')->where('id=' . $id)->save($up);
        if ($res1) {
            $this->addAdminOperate($msg . '手动解冻成功');
            $return['stat'] = 'ok';
        } else {
            $this->addAdminOperate($msg . '手动解冻失败');
            $return['stat'] = 'no';
        }

        //如果是交易冻结的订单,还需要请求独立系统接口删除任务
        if ($freezemoney['send' == 1]) {
            //请求接口
            $freezeorder = OrderfreezemoneyModel::getOrdernumber(I('id'));
            //拼接请求参数
            $send_data = [
                'version' => C('AMNPAY_VERSION'),  //版本号
                'merid' => C('AMNPAY_MERID'),  //独立系统分配的商户编号,从配置文件读出
                'merorderno' => $freezeorder['freezeordernumber'],  //冻结订单号
            ];
            //签名
            $userKey = C('AMNPAY_SECRETKEY'); //与独立系统对接的密钥,从配置文件读取
            $signStr = $this->getDeleteTaskSignStr($send_data); //签名字符串
            openssl_sign($signStr, $sign, $userKey);  //rsa加密
            $send_data['sign'] = base64_encode($sign);
            //curl请求定时任务接口
            $post_url = C('DELETE_TASK_UNFREEZE_URL');//删除定时任务请求接口
            $task_return = curlPost($post_url, $send_data);
            $task_return = json_decode($task_return, true);
            if ($task_return['status'] == '0000') {
                $return['msg'] = '定时任务删除成功';
            } else {
                $return['msg'] = '定时任务删除失败:' . $task_return['msg'];
            }
        }

        $this->ajaxReturn($return);
    }

    //延期
    public function delayUnfreezeConfirm()
    {
        //延期表中添加记录
        $expect_time = OrderfreezemoneyModel::getExpectTime(I('id'));
        $freezeordernumber = OrderfreezemoneyModel::getFreezeordernumber(I('id'));
        $msg = "冻结订单号为[" . $freezeordernumber . "]的冻结订单延期:";
        $data['freezemoney_id'] = I('post.id');
        $data['adminuser_id'] = session('admin_info.id');
        $data['adminuser_ip'] = getIp();
        $data['before_time'] = $expect_time;
        $data['after_time'] = I('post.delay');
        $data['date_time'] = date('Y-m-d H:i:s');
        $res = M('delayunfreeze')->add($data);
        if ($res) {
            //修改订单冻结金额表的预计时间
            M('orderfreezemoney')->where('id=' . I('post.id'))->save(['expect_time' => I('post.delay'), 'task' => 1]);

            //请求定时任务接口,修改解冻时间
            $freezeorder = OrderfreezemoneyModel::getOrdernumber(I('id'));
            //拼接请求参数
            $send_data = [
                'version' => C('AMNPAY_VERSION'),  //版本号
                'merid' => C('AMNPAY_MERID'),  //独立系统分配的商户编号,从配置文件读出
                'merorderno' => $freezeorder['freezeordernumber'],  //冻结订单号
                'notifyurl' => 'http://www.baidu.com',  //异步回调地址,后期会改成同步返回
                'createtime' => date('Y-m-d H:i:s'),  //系统请求接口创建任务的时间
                'request_url' => C('AUTO_UNFREEZE_URL'),  //请求路径,从配置文件中读出
                'request_time' => I('post.delay'),  //接口请求系统开始解冻的时间
            ];
            //签名
            $userKey = C('AMNPAY_SECRETKEY'); //与独立系统对接的密钥,从配置文件读取
            $signStr = $this->getOneTaskSignStr($send_data); //签名字符串
            openssl_sign($signStr, $sign, $userKey);  //rsa加密
            $send_data['sign'] = base64_encode($sign);
            //curl请求定时任务接口
            $post_url = C('ONE_TASK_UNFREEZE_URL');//单条定时任务请求接口
            $task_return = curlPost($post_url, $send_data);
            $task_return = json_decode($task_return, true);
            if ($task_return['status'] == '0000') {
                M('orderfreezemoney')->where('id=' . I('post.id'))->setField('send', 1);
                $this->addAdminOperate($msg . '延期成功');
                $this->ajaxReturn(['msg' => "自动解冻时间修改成功!", 'status' => 'ok'], "json");
            } else {
                $this->addAdminOperate($msg . '延期成功,但定时任务请求失败,' . $task_return['msg']);
                $this->ajaxReturn(['msg' => "自动解冻时间修改成功,定时任务请求失败:" . $task_return['msg'], 'status' => 'ok'], "json");
            }
        } else {
            $this->addAdminOperate($msg . '延期失败');
            $this->ajaxReturn(['msg' => "自动解冻时间修改失败!", 'status' => 'no'], "json");
        }
    }

    //补发定时任务请求
    public function sendTask()
    {
        //请求定时任务接口,修改解冻时间
        $freezeorder = OrderfreezemoneyModel::getInfo(I('id'));
        $msg = "冻结订单号为[" . $freezeorder['freezeordernumber'] . "]的冻结订单任务补发:";
        //拼接请求参数
        $send_data = [
            'version' => C('AMNPAY_VERSION'),  //版本号
            'merid' => C('AMNPAY_MERID'),  //独立系统分配的商户编号,从配置文件读出
            'merorderno' => $freezeorder['freezeordernumber'],  //冻结订单号
            'notifyurl' => 'http://www.baidu.com',  //异步回调地址,后期会改成同步返回
            'createtime' => date('Y-m-d H:i:s'),  //系统请求接口创建任务的时间
            'request_url' => C('AUTO_UNFREEZE_URL'),  //请求路径,从配置文件中读出
            'request_time' => $freezeorder['expect_time'],  //接口请求系统开始解冻的时间
        ];
        //签名
        $userKey = C('AMNPAY_SECRETKEY'); //与独立系统对接的密钥,从配置文件读取
        $signStr = $this->getOneTaskSignStr($send_data); //签名字符串
        openssl_sign($signStr, $sign, $userKey);  //rsa加密
        $send_data['sign'] = base64_encode($sign);
        //curl请求定时任务接口
        $post_url = C('ONE_TASK_UNFREEZE_URL');//单条定时任务请求接口
        $task_return = curlPost($post_url, $send_data);
        $task_return = json_decode($task_return, true);
        if ($task_return['status'] == '0000') {
            M('orderfreezemoney')->where('id=' . I('post.id'))->setField('send', 1);
            $this->addAdminOperate($msg . '定时任务补发成功');
            $this->ajaxReturn(['msg' => "定时任务请求成功!", 'status' => 'ok'], "json");
        } else {
            $this->addAdminOperate($msg . '定时任务补发失败,' . $task_return['msg']);
            $this->ajaxReturn(['msg' => "定时任务请求失败:" . $task_return['msg'], 'status' => 'no'], "json");
        }
    }

    //查看解冻时间变化页面
    public function seeUnfreezeInfo()
    {
        $this->display();
    }

    //查看解冻时间变化过程
    public function loadUnfreezeInfo()
    {
        $freezemoney_id = I('get.id');
        $dataList = [];
        //查询手动解冻记录
        $manualunfreeze = M('manualunfreeze')->where('freezemoney_id=' . $freezemoney_id)->find();
        if ($manualunfreeze) {
            $manualunfreeze['type'] = 'manual';
            $dataList[] = $manualunfreeze;
        }

        //查询自动解冻记录
        $autounfreeze = M('autounfreeze')->where('freezemoney_id=' . $freezemoney_id)->find();
        if ($autounfreeze) {
            $autounfreeze['type'] = 'auto';
            $dataList[] = $autounfreeze;
        }

        //查询延期记录
        $delay = M('delayunfreeze')->where('freezemoney_id=' . $freezemoney_id)->order('id desc')->select();
        foreach ($delay as $key => $val) {
            $dataList[] = $val;
        }

        if ($dataList) {
            foreach ($dataList as $k => $v) {
                if ($v['adminuser_id'] > 0) {
                    $dataList[$k]['adminuser_name'] = M('adminuser')->where('id=' . $v['adminuser_id'])->getField('user_name');
                }
            }
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
        $count = count($dataList);
        $ReturnArr['count'] = $count;
        $ReturnArr['data'] = $dataList;
        $this->ajaxReturn($ReturnArr, 'json');
    }

    //订单验证
    public function orderVerify()
    {
        $order_id = I('orderid');
        $order_info = M('order')->where("id=" . $order_id)->find();
        $msg = "订单[" . $order_info['sysordernumber'] . "]请求验证:";
        //查询通道编码
        $pay_name = M('payapi')->where('id=' . $order_info['payapiid'])->getField('en_payname');
        $data = [
            'sysordernumber' => $order_info['sysordernumber'],
            'pay_name' => $pay_name,
            'true_ordermoney' => $order_info['true_ordermoney'],
            'verify' => 1, //验证
            'supplement' => 0  //补单
        ];
        $key = C('ORDER_VERIFY_MD5');
        $data['sign'] = $this->orderVerifySign($data, $key);
        $res = call_user_func(array(A("Pay/Pay"), "orderVerify"), $data);
        if ($res['status'] == 'ok') {
            $this->addAdminOperate($msg . '订单验证请求成功');
            $this->ajaxReturn(['stat' => 'ok', 'msg' => '订单验证请求成功:' . $res['msg']]);
        } else {
            $this->addAdminOperate($msg . '订单验证请求失败' . $res['msg']);
            $this->ajaxReturn(['stat' => 'no', 'msg' => '订单验证请求失败:' . $res['msg']]);
        }
    }

    //订单补单
    public function orderSupplement()
    {
        $order_id = I('orderid');
        $order_info = M('order')->where("id=" . $order_id)->find();
        $msg = "订单[" . $order_info['sysordernumber'] . "]请求补单:";
        //查询通道编码
        $pay_name = M('payapi')->where('id=' . $order_info['payapiid'])->getField('en_payname');
        $data = [
            'sysordernumber' => $order_info['sysordernumber'],
            'pay_name' => $pay_name,
            'true_ordermoney' => $order_info['true_ordermoney'],
            'verify' => 0, //验证
            'supplement' => 1  //补单
        ];
        $key = C('ORDER_VERIFY_MD5');
        $data['sign'] = $this->orderVerifySign($data, $key);
        $res = call_user_func(array(A("Pay/Pay"), "orderVerify"), $data);
        if ($res['status'] == 'ok') {
            $this->addAdminOperate($msg . '订单补单请求成功');
            $this->ajaxReturn(['stat' => 'ok', 'msg' => '订单补单请求成功:' . $res['msg']]);
        } else {
            $this->addAdminOperate($msg . '订单补单请求失败' . $res['msg']);
            $this->ajaxReturn(['stat' => 'no', 'msg' => '订单补单请求失败:' . $res['msg']]);
        }
    }

    //验证和补单的签名
    public function orderVerifySign($data, $key)
    {
        $str = '';
        foreach ($data as $k => $v) {
            if ($k != 'sign') {
                $str .= $k . '=' . $v . '&';
            }
        }
        return strtoupper(md5($str . 'key=' . $key));
    }

    //请求单笔定时任务获取签名
    public function getOneTaskSignStr($data)
    {
        $keys = [
            "version",
            "merid",
            "merorderno",
            "notifyurl",
            "createtime",
            "request_url",
            'request_time',
        ];
        sort($keys);
        $str = '';
        foreach ($keys as $k => $parameter) {
            $str .= $parameter . "=" . $data[$parameter] . "&";
        }
        return trim($str, '&');
    }

    //请求删除定时任务获取签名
    public function getDeleteTaskSignStr($data)
    {
        $keys = [
            "version",
            "merid",
            "merorderno",
        ];
        sort($keys);
        $str = '';
        foreach ($keys as $k => $parameter) {
            $str .= $parameter . "=" . $data[$parameter] . "&";
        }
        return trim($str, '&');
    }


}