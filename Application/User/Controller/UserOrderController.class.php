<?php

namespace User\Controller;

use User\Model\PayapiclassModel;
use User\Model\PayapiModel;

class UserOrderController extends UserCommonController
{
    public function __construct()
    {
        parent::__construct();
    }

    //交易记录表
    public function orderList()
    {
        //搜索
        $statustype = C('STATUSTYPE');
        $this->assign('statustype', $statustype);

//        $shangjias = M('payapishangjia')->select();
//        $this->assign('shangjias', $shangjias);
//
//        $payapis = M('payapi')->select();
//        $this->assign('payapis', $payapis);
//
//        $accounts = M('payapiaccount')->select();
//        $this->assign('accounts', $accounts);


        //提交时间
        //查询所有提交时间的类型
        $all_open_type = C('ORDEROPENTYPE');
        $this->assign('all_open_type', $all_open_type);
        //查询当前用户的提交时间
        $user_id = session('user_info.id');
//        $user_open_type = M('orderopentype')->where('user_id=' . $user_id . ' and user_type="user"')->getField('order_open_type');
        $user_open_type = M('orderopentype')->where(['user_id'=>$user_id,'user_type'=>'user'])->getField('order_open_type');
        $this->assign('user_open_type', $user_open_type);
        $this->assign('user_id', $user_id);
        //获取提交日期
        if ($user_open_type) {
            $open_start = date("Y-m-d", strtotime('-' . ($user_open_type - 1) . " day")) . " 00:00:00";
            $open_end = date("Y-m-d") . " 23:59:59";
        }

        $this->assign('open_start', $open_start);
        $this->assign('open_end', $open_end);

        $this->display();
    }

    //加载交易记录数据
    public function loadOrderList()
    {
        //搜索
        $where = [];
        $i = 1;
        $userid = session('user_info.id');
        if ($userid <> "") {
            $where[$i] = "userid = " . $userid;
            $i++;
        }
        $userip = trim(I("post.userip", ""));
        if ($userip <> "") {
            $where[$i] = "(userip like '" . $userip . "%')";
            $i++;
        }
        $userordernumber = trim(I("post.userordernumber", ""));
        if ($userordernumber <> "") {
            $where[$i] = "(userordernumber like '" . $userordernumber . "%')";
            $i++;
        }
        $status = I("post.status", "");
        if ($status <> "") {
            $where[$i] = "status = " . $status;
            $i++;
        }
        $type = I("post.ordertype", "");
        if ($type <> "") {
            $where[$i] = "type = " . $type;
            $i++;
        }
        $start = I("post.start", "");
        $end = I("post.end", "");
        if($start && $end){
            $where[$i] = "unix_timestamp( datetime ) between unix_timestamp( '{$start} ') and unix_timestamp( '{$end}' )";
            $i++;
        }

//        if ($start <> "") {
//            $where[$i] = "DATEDIFF('" . $start . "',datetime) <= 0";
//            $i++;
//        }
//
//        if ($end <> "") {
//            $where[$i] = "DATEDIFF('" . $end . "',datetime) >= 0";
//            $i++;
//        }
        $success_start = I("post.success_start", "");
        $success_end = I("post.success_end", "");
        if($success_start && $success_end){
            $where[$i] = "unix_timestamp( successtime ) between unix_timestamp( '{$success_start} ') and unix_timestamp( '{$success_end}' )";
            $i++;
        }

//        if ($success_start <> "") {
//            $where[$i] = "DATEDIFF('" . $success_start . "',successtime) <= 0";
//            $i++;
//        }
//
//        if ($success_end <> "") {
//            $where[$i] = "DATEDIFF('" . $success_end . "',successtime) >= 0";
//            $i++;
//        }
        //2019-01-25汪桂芳添加
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

        //分页
        $ReturnArr = [
            'code' => 0,
            'msg' => '数据加载成功', //响应结果
            'count' => 0, //总页数
            'data' => [
            ]
        ];
        //总页数
        $count = M('orderinfo')->where($where)->count();
        $page = I('post.page');
        $limit = I('post.limit');
        $datalist = M('orderinfo')->where($where)->page($page, $limit)->order('orderid DESC')->select();

        //查询数据
        $statustype = C('STATUSTYPE');
        foreach ($datalist as $k => $v) {
            $datalist[$k]['username'] = $this->getNameById('user', $v['userid'], 'username');
            $datalist[$k]['shangjianame'] = $this->getNameById('payapishangjia', $v['shangjiaid'], 'shangjianame');
            $datalist[$k]['payapiname'] = $this->getNameById('payapi', $v['payapiid'], 'zh_payname');
            $datalist[$k]['accountname'] = $this->getNameById('payapiaccount', $v['payapiaccountid'], 'bieming');
            $payapiclassid = M('payapi')->where('id=' . $v['payapiid'])->getField('payapiclassid');
            if ($payapiclassid) {
                $datalist[$k]['payapiclassname'] = M('payapiclass')->where('id=' . $payapiclassid)->getField('classname');
            }
            $datalist[$k]['statusname'] = $statustype[$v['status']];
            //处理订单金额和冻结金额为两位小数点
            $datalist[$k]['new_ordermoney'] = substr($v['ordermoney'], 0, strlen($v['ordermoney']) - 1);
            $datalist[$k]['new_true_ordermoney'] = substr($v['true_ordermoney'], 0, strlen($v['true_ordermoney']) - 1);
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

        $ReturnArr['count'] = $count;
        $ReturnArr['data'] = $datalist;
        $this->ajaxReturn($ReturnArr, 'json');
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
        $data['user_id'] = I('post.user_id');
        $data['order_open_type'] = I('post.order_open_type');
        $all_type = C('ORDEROPENTYPE');
        $stat = "修改为[" . $all_type[$data["order_open_type"]] . "],";
        $data['user_type'] = 'user';
        $where = [
            'user_id' => I('post.user_id'),
            'user_type' => 'user'
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
            $this->addUserOperate($msg . $stat . '修改成功');
            $this->ajaxReturn(['msg' => "提交时间修改成功!", 'status' => 'ok'], "json");
        }
        $this->addUserOperate($msg . $stat . '修改失败');
        $this->ajaxReturn(['msg' => "提交时间修改失败!", 'status' => 'no'], "json");
    }

    //操作：查看
    public function seeOrderInfo()
    {
        $order = M('order')->where('id=' . I('orderid'))->find();
        $order_info = M('orderinfo')->where('orderid=' . I('orderid'))->find();
        $statustype = C('STATUSTYPE');
        $order_info['username'] = $this->getNameById('user', $order_info['userid'], 'username');
        $payapiclass_id = PayapiModel::getPayapiclassid($order_info['payapiid']);
        if ($payapiclass_id) {
            $order_info['classname'] = $this->getNameById('payapiclass', $payapiclass_id, 'classname');
        }
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
            $change = M('moneychange')->where('transid="' . $order_info['sysordernumber'] . '" and changetype=4')->find();
            $order_info['oldmoney'] = $change['oldmoney'];
            $order_info['changemoney'] = $change['changemoney'];
            $order_info['nowmoney'] = $change['nowmoney'];
        }
        $this->assign('order_info', $order_info);
        $this->display();
    }

    //导出列表
    public function downloadOrderList()
    {
        //搜索
        $where = [];
        $i = 1;
        $userid = session('user_info.id');
        if ($userid <> "") {
            $where[$i] = "userid = " . $userid;
            $i++;
        }
        $userip = trim(I("get.userip", ""));
        if ($userip <> "") {
            $where[$i] = "(userip like '%" . $userip . "%')";
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
            $datalist[$k]['username'] = $this->getNameById('user', $v['userid'], 'username');
            $payapiclassid = PayapiModel::getPayapiclassid($v['payapiid']);
            if ($payapiclassid) {
                $datalist[$k]['payapiclassname'] = PayapiclassModel::getPayapiclassid($payapiclassid);
            }
            $datalist[$k]['status'] = $statustype[$v['status']];
        }

        $title = '交易记录表';
        $menu_zh = array('商户号', '用户订单号', '提交地址IP', '通道分类', '订单金额', '到账金额', '冻结金额', '订单时间', '成功时间', '类型', '状态');
        $menu_en = array('memberid', 'userordernumber', 'userip', 'payapiclassname', 'ordermoney', 'money', 'freezemoney', 'datetime', 'successtime', 'type', 'status');
        $config = array('RowHeight' => 25, 'Width' => 20);
        $this->addUserOperate('用户[' . session('user_info.username') . ']导出了交易记录列表');
        DownLoadExcel($title, $menu_zh, $menu_en, $datalist, $config);

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

    //2019-4-2 任梦龙：添加
    /**********************************************/
    //用户自助账号交易记录表
    public function userOrderList()
    {
        //搜索
        $statustype = C('STATUSTYPE');
        $this->assign('statustype', $statustype);

        $shangjias = M('payapishangjia')->select();
        $this->assign('shangjias', $shangjias);

        $payapis = M('payapi')->field('id,zh_payname')->select();
        $this->assign('payapis', $payapis);

        $accounts = M('payapiaccount')->field('id,bieming')->select();
        $this->assign('accounts', $accounts);


        //提交时间
        //查询所有提交时间的类型
        $all_open_type = C('ORDEROPENTYPE');
        $this->assign('all_open_type', $all_open_type);
        //查询当前用户的提交时间
        $user_id = session('user_info.id');
        $user_open_type = M('orderopentype')->where('user_id=' . $user_id . ' and user_type="user"')->getField('order_open_type');
        $this->assign('user_open_type', $user_open_type);
        $this->assign('user_id', $user_id);
        //获取提交日期
        if ($user_open_type) {
            $open_start = date("Y-m-d", strtotime('-' . ($user_open_type - 1) . " day")) . " 00:00:00";
            $open_end = date("Y-m-d") . " 23:59:59";
        }

        $this->assign('open_start', $open_start);
        $this->assign('open_end', $open_end);

        $this->display();
    }

    //加载用户自身的自助账号交易记录数据
    public function loadUserOrderList()
    {
        $where = [];
        $where[0] = "users_id=" . session('user_info.id');
        $i = 1;
        $payapiid = I("post.payapiid", "");
        if ($payapiid <> "") {
            $where[$i] = "payapiid = " . $payapiid;
            $i++;
        }

        $payapiaccountid = I("post.payapiaccountid", "");
        if ($payapiaccountid <> "") {
            $where[$i] = "payapiaccountid = " . $payapiaccountid;
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
        $status = I("post.status", "");
        if ($status <> "") {
            $where[$i] = "status = " . $status;
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
            $datalist[$k]['payapiname'] = $this->getNameById('payapi', $v['payapiid'], 'zh_payname');
            $datalist[$k]['accountname'] = $this->getNameById('payapiaccount', $v['payapiaccountid'], 'bieming');
//            $datalist[$k]['username'] = $this->getNameById('user', $v['userid'], 'username');
//            $datalist[$k]['shangjianame'] = $this->getNameById('payapishangjia', $v['shangjiaid'], 'shangjianame');
//            $payapiclassid = M('payapi')->where('id=' . $v['payapiid'])->getField('payapiclassid');
//            if ($payapiclassid) {
//                $datalist[$k]['payapiclassname'] = M('payapiclass')->where('id=' . $payapiclassid)->getField('classname');
//            }
            $datalist[$k]['statusname'] = $statustype[$v['status']];
            //处理订单金额和冻结金额为两位小数点
            $datalist[$k]['new_ordermoney'] = substr($v['ordermoney'], 0, strlen($v['ordermoney']) - 1);
            $datalist[$k]['new_true_ordermoney'] = substr($v['true_ordermoney'], 0, strlen($v['true_ordermoney']) - 1);
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

    //导出自助账号交易数据
    public function downloadUserOrder()
    {
        $where = [];
        $where[0] = "users_id=" . session('user_info.id');
        $i = 1;
        $payapiid = I("get.payapiid", "");
        if ($payapiid <> "") {
            $where[$i] = "payapiid = " . $payapiid;
            $i++;
        }

        $payapiaccountid = I("get.payapiaccountid", "");
        if ($payapiaccountid <> "") {
            $where[$i] = "payapiaccountid = " . $payapiaccountid;
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
        $status = I("get.status", "");
        if ($status <> "") {
            $where[$i] = "status = " . $status;
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
//            $datalist[$k]['username'] = $this->getNameById('user', $v['userid'], 'username');
            $payapiclassid = PayapiModel::getPayapiclassid($v['payapiid']);
            if ($payapiclassid) {
                $datalist[$k]['payapiclassname'] = PayapiclassModel::getPayapiclassid($payapiclassid);
            }
            $datalist[$k]['payapiname'] = $this->getNameById('payapi', $v['payapiid'], 'zh_payname');
            $datalist[$k]['accountname'] = $this->getNameById('payapiaccount', $v['payapiaccountid'], 'bieming');
            $datalist[$k]['status'] = $statustype[$v['status']];
        }

        $title = '自助账号交易记录表';
        $menu_zh = array('商户号', '用户订单号', '提交地址IP', '提交金额', '实际金额', '到账金额', '冻结金额', '通道名称', '通道账号', '订单时间', '成功时间', '状态', '类型');
        $menu_en = array('memberid', 'userordernumber', 'userip', 'ordermoney', 'true_ordermoney', 'money', 'freezemoney', 'payapiname', 'accountname', 'datetime', 'successtime','status', 'type');
        $config = array('RowHeight' => 25, 'Width' => 20);
        $this->addUserOperate('用户[' . session('user_info.username') . ']导出了自助账号交易记录列表');
        DownLoadExcel($title, $menu_zh, $menu_en, $datalist, $config);
    }

    //2019-4-9 任梦龙：查看
    public function seeUserOrderInfo()
    {
        $order = M('order')->where('id=' . I('orderid'))->find();
        $order_info = M('orderinfo')->where('orderid=' . I('orderid'))->find();
        $statustype = C('STATUSTYPE');
        $order_info['username'] = $this->getNameById('user', $order_info['userid'], 'username');
        $payapiclass_id = PayapiModel::getPayapiclassid($order_info['payapiid']);
        if ($payapiclass_id) {
            $order_info['classname'] = $this->getNameById('payapiclass', $payapiclass_id, 'classname');
        }
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
            $change = M('moneychange')->where('transid="' . $order_info['sysordernumber'] . '" and changetype=4')->find();
            $order_info['oldmoney'] = $change['oldmoney'];
            $order_info['changemoney'] = $change['changemoney'];
            $order_info['nowmoney'] = $change['nowmoney'];
        }
        $this->assign('order_info', $order_info);
        $this->display();
    }
    /**********************************************/
}