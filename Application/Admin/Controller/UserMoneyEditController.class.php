<?php

namespace Admin\Controller;

use Admin\Model\MoneyModel;
use Admin\Model\OrderfreezemoneyModel;
use Admin\Model\PayapiaccountModel;
use Admin\Model\PayapiclassModel;
use Admin\Model\UserModel;
use Admin\Model\PayapiModel;
use Admin\Model\UserpayapiclassModel;
use Admin\Model\UsermoneyMode;
use Admin\Model\MoneychangeModel;

class UserMoneyEditController extends CommonController
{

    //2019-4-11 rml ：可用余额总页面
    public function userMoney()
    {
        $this->display();
    }

    //余额增加页面
    public function userAddMoney()
    {
        $id = I('get.userid');
        $money = M('usermoney')->where(['userid' => $id])->lock(true)->find();
        $old_money = $money ? $money['money'] : '0.00';
        $this->assign('old_money', $old_money);
        $this->assign('id', $id);
        $code_type = $this->getCodeType();
        $this->assign('code_type', $code_type);
        $this->display();
    }

    //余额增加处理程序
    public function userMoneyAdd()
    {
        M()->startTrans();  //启用事务
        $user_id = I('post.userid');
        $remarks = I('post.remarks', '', 'trim');
//        $old_money = I('post.old_money');
        $old_money = M('usermoney')->where(['userid' => $user_id])->lock(true)->getField('money');
        $add_money = I('post.add_money', '', 'trim');
        if ($add_money <= 0) {
            $this->ajaxReturn(['msg' => "金额需大于0", 'status' => 'no']);
        }
        if($add_money > 9999999999){
            $this->ajaxReturn(['msg' => "增加金额不得超过9999999999", 'status' => 'no']);
        }
        if (!is_numeric($add_money)) {
            $this->ajaxReturn(['msg' => "金额需为整数或小数", 'status' => 'no']);
        }
        $verfiy_code = I('post.verfiy_code', '', 'trim');
        $code_type = I('post.code_type', 0, 'intval');
        $this->checkVerifyCode($verfiy_code, $code_type);
        $count = M('usermoney')->lock(true)->where(['userid' => $user_id])->count();
        $new_money = $old_money + $add_money;
        if ($count > 0) {
            //如果有记录，直接修改
            $result = M('usermoney')->lock(true)->where(['userid' => $user_id])->save(['money' => $new_money]);
        } else {
            //没有记录，则添加
            $data = [
                'userid' => $user_id,
                'money' => $new_money,
                'remarks' => $remarks,
            ];
            $result = M('usermoney')->lock(true)->add($data);
        }
        $user_name = UserModel::getUserName($user_id);
        $msg = '为用户[' . $user_name . ']手动增加余额:原金额为' . $old_money . '元,增加金额为' . $add_money . '元,';
        if ($result) {
            //moneychange表添加记录
            $da = [
                'userid' => $user_id,
                'oldmoney' => $old_money,
                'changemoney' => $add_money,
                'nowmoney' => $old_money + $add_money,
                'changetype' => 0,
                'remarks' => $remarks,
                'datetime' => date('Y-m-d H:i:s')
            ];
            M('moneychange')->lock(true)->add($da);
            M()->commit();
            $this->addAdminOperate($msg . '增加成功');
            $this->ajaxReturn(['msg' => "金额增加成功!", 'status' => 'ok']);
        }else{
            M()->rollback();
            $this->addAdminOperate($msg . '增加失败');
            $this->ajaxReturn(['msg' => "金额增加失败!", 'status' => 'no']);
        }

    }

    //余额减少页面
    public function userCutMoney()
    {
        $id = I('get.userid');
        $money = M('usermoney')->where(['userid' => $id])->find();
        $old_money = $money ? $money['money'] : '0.00';
        $code_type = $this->getCodeType();
        $this->assign('code_type', $code_type);
        $this->assign('old_money', $old_money);
        $this->assign('id', $id);
        $this->display();
    }

    //余额减少处理程序
    public function userMoneyCut()
    {
        M()->startTrans();
        $user_id = I('post.userid');
        $remarks = I('post.remarks', '', 'trim');
//        $old_money = I('post.old_money');
        $old_money = M('usermoney')->where(['userid' => $user_id])->lock(true)->getField('money');
        $cut_money = I('post.cut_money', '', 'trim');
        $count = M('usermoney')->lock(true)->where(['userid' => $user_id])->count();
        if (!$count) {
            $this->ajaxReturn(['msg' => "该用户还未有可用余额,请确认", 'status' => 'no']);
        }
        if ($cut_money <= 0) {
            $this->ajaxReturn(['msg' => "金额需大于0", 'status' => 'no']);
        }
        if (!is_numeric($cut_money)) {
            $this->ajaxReturn(['msg' => "金额需为整数或小数", 'status' => 'no']);
        }
        if ($cut_money > $old_money) {
            $this->ajaxReturn(['msg' => "减少金额超出原金额!", 'status' => 'no']);
        }
        $verfiy_code = I('post.verfiy_code', '', 'trim');
        $code_type = I('post.code_type', 0, 'intval');
        $this->checkVerifyCode($verfiy_code, $code_type);
        $user_name = UserModel::getUserName($user_id);
        $msg = '为用户[' . $user_name . ']手动减少余额:原金额为' . $old_money . '元,减少金额为' . $cut_money . '元,';
        $new_money = $old_money - $cut_money;
        //直接修改
        $result = M('usermoney')->lock(true)->where(['userid' => $user_id])->save(['money' => $new_money]);
        if ($result) {
            //moneychange表添加记录
            $da = [
                'userid' => $user_id,
                'oldmoney' => $old_money,
                'changemoney' => $cut_money,
                'nowmoney' => $new_money,
                'changetype' => 1,
                'remarks' => $remarks,
                'datetime' => date('Y-m-d H:i:s')
            ];
            M('moneychange')->lock(true)->add($da);
            M()->commit();
            $this->addAdminOperate($msg . '减少成功');
            $this->ajaxReturn(['msg' => "金额减少成功!", 'status' => 'ok']);
        }else{
            M()->rollback();
            $this->addAdminOperate($msg . '减少失败');
            $this->ajaxReturn(['msg' => "金额减少失败!", 'status' => 'no']);
        }

    }

    //余额冻结页面
    public function userFreezeMoney()
    {
        $id = I('get.userid');
        $money = M('usermoney')->where(['userid' => $id])->find();
        $old_money = $money ? $money['money'] : '0.00';
        $this->assign('old_money', $old_money);
        $this->assign('id', $id);
        $code_type = $this->getCodeType();
        $this->assign('code_type', $code_type);
        $this->display();
    }

    //余额冻结处理程序
    public function userMoneyFreeze()
    {
        M()->startTrans();
        $user_id = I('post.userid');
        $remarks = I('post.remarks', '', 'trim');
//        $old_money = I('post.old_money');
        $freeze_money = I('post.freeze_money', '', 'trim');
        $find = M('usermoney')->lock(true)->where(['userid' => $user_id])->find();
        if (!$find) {
            $this->ajaxReturn(['msg' => "该用户还未有可用余额,请确认", 'status' => 'no']);
        }
        if ($freeze_money <= 0) {
            $this->ajaxReturn(['msg' => "金额需大于0", 'status' => 'no']);
        }
        if (!is_numeric($freeze_money)) {
            $this->ajaxReturn(['msg' => "金额需为整数或小数", 'status' => 'no']);
        }
        $old_money = M('usermoney')->where(['userid' => $user_id])->lock(true)->getField('money');
        if ($freeze_money > $old_money) {
            $this->ajaxReturn(['msg' => "冻结金额超出原金额!", 'status' => 'no']);
        }
        $verfiy_code = I('post.verfiy_code', '', 'trim');
        $code_type = I('post.code_type', 0, 'intval');
        $this->checkVerifyCode($verfiy_code, $code_type);
        $user_name = UserModel::getUserName($user_id);
        $msg = '为用户[' . $user_name . ']手动冻结余额:原金额为' . $old_money . '元,冻结金额为' . $freeze_money . '元,';
        $new_money = $old_money - $freeze_money;
        $new_freeze = $find['freezemoney'] + $freeze_money;
        //直接修改
        $result = M('usermoney')->lock(true)->where(['userid' => $user_id])->save(['money' => $new_money, 'freezemoney' => $new_freeze]);
        if ($result) {
            //moneychange表添加记录
            $da = [
                'userid' => $user_id,
                'oldmoney' => $old_money,
                'changemoney' => $freeze_money,
                'nowmoney' => $old_money - $freeze_money,
                'changetype' => 2,
                'remarks' => $remarks,
                'datetime' => date('Y-m-d H:i:s')
            ];
            M('moneychange')->lock(true)->add($da);
            //orderfreezemoney表添加记录
            $data['freeze_money'] = $freeze_money;
            $data['date_time'] = date('Y-m-d H:i:s');
            $data['user_id'] = $user_id;
            $data['freeze_type'] = 1;//手动冻结
            $data['order_status'] = 2;//手动冻结
            $data['freezeordernumber'] = Createfreezeordernumber(36);
            M('orderfreezemoney')->add($data);
            M()->commit();
            $this->addAdminOperate($msg . '冻结成功');
            $this->ajaxReturn(['msg' => "金额冻结成功!", 'status' => 'ok']);
        }else{
            M()->rollback();
            $this->addAdminOperate($msg . '冻结失败');
            $this->ajaxReturn(['msg' => "金额冻结失败!", 'status' => 'no']);
        }

    }

    //冻结金额明细页面
    public function userFreezeMoneyList()
    {
        $id = I('userid');
        $this->assign('id', $id);
        //查询用户冻结总金额
        $ordermoney = M('usermoney')->where('userid=' . $id)->find();
        $this->assign('ordermoney', $ordermoney);
        $this->display();
    }

    //加载冻结金额记录
    public function loadUserFreezeMoneyList()
    {
        $id = I('get.userid');
        //查询用户所有手动冻结未解冻的记录和交易成功但未解冻的数据
        $where['user_id'] = $id;
        $where['unfreeze'] = 0;
        $where['order_status'] = ['egt', 1];
        $count = M('orderfreezemoney')->where($where)->count();
        $datalist = M('orderfreezemoney')->where($where)->order('id desc')->page(I("post.page", "1"), I("post.limit", "10"))->select();
        foreach ($datalist as $k => $v) {
            if ($v['freeze_type'] == 0) {
                //查询用户订单号及系统订单号
                $ordermoney_id = $v['ordermoney_id'];
                $datalist[$k]['sysordernumber'] = M('ordermoney')->where('id=' . $ordermoney_id)->getField('sysordernumber');
                $datalist[$k]['userordernumber'] = M('order')->where('sysordernumber="' . $datalist[$k]['sysordernumber'] . '"')->getField('userordernumber');
            }
        }
        //分页
        $ReturnArr = [
            'code' => 0,
            'msg' => '数据加载成功', //响应结果
        ];
        //总页数
        $ReturnArr['count'] = $count;
        $ReturnArr['data'] = $datalist;
        $this->ajaxReturn($ReturnArr, 'json');
    }

    //手动解冻
    public function manualUnfreeze()
    {
        $id = I('id');
        $freezemoney = M('orderfreezemoney')->where('id=' . $id)->find();
        $user_name = UserModel::getUserName($freezemoney['user_id']);
        $msg = "手动解冻用户[" . $user_name . "]的冻结金额:";
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
            'remarks' => "冻结金额手动解冻",
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
            $this->addAdminOperate($msg . '解冻成功');
            $return['stat'] = 'ok';
        } else {
            $this->addAdminOperate($msg . '解冻失败');
            $return['stat'] = 'no';
        }
        //如果是交易冻结的订单,还需要请求独立系统接口删除任务;手动冻结的如果请求过也需要删除任务
        if ($freezemoney['send'] == 1) {
            //请求接口
            $freezeorder = M('orderfreezemoney')->where('id=' . I('post.id'))->field('freezeordernumber,sysordernumber')->find();
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
//        print_r($return);die;
        $this->ajaxReturn($return, 'json');
    }

    //延期
    public function delayUnfreezeConfirm()
    {
        //延期表中添加记录
        $id = I('post.id');
        $expect_time = OrderfreezemoneyModel::getExpectTime($id);
        $data['freezemoney_id'] = $id;
        $data['adminuser_id'] = session('admin_info.id');
        $data['adminuser_ip'] = getIp();
        $data['before_time'] = $expect_time;
        $data['after_time'] = I('post.delay');
        $data['date_time'] = date('Y-m-d H:i:s');
        $data['delay_type'] = I('post.delay_type');
        $res = M('delayunfreeze')->add($data);
        $freezeordernumber = OrderfreezemoneyModel::getOrdernumber($id);
        $msg = "修改冻结订单[" . $freezeordernumber . "]的解冻时间:原始解冻时间为" . $expect_time . ',修改为' . $data['after_time'] . ',';
        if ($res) {
            $this->addAdminOperate($msg . '修改成功');
            //修改订单冻结金额表的预计时间
            M('orderfreezemoney')->where('id=' . I('post.id'))->save(['expect_time' => I('post.delay'), 'task' => 1]);

            //请求定时任务接口,修改解冻时间
            $freezeorder = M('orderfreezemoney')->where('id=' . I('post.id'))->field('freezeordernumber,sysordernumber')->find();
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
                $this->ajaxReturn(['msg' => "自动解冻时间修改成功!", 'status' => 'ok'], "json");
            } else {
                $this->ajaxReturn(['msg' => "自动解冻时间修改成功,定时任务请求失败:" . $task_return['msg'], 'status' => 'ok'], "json");
            }
        }
        $this->addAdminOperate($msg . '修改失败');
        $this->ajaxReturn(['msg' => "自动解冻时间修改失败!", 'status' => 'no'], "json");
    }

    //查看解冻过程页面
    public function seeUnfreezeInfo()
    {
        $this->display();
    }

    //加载解冻过程列表
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
        $page = I('post.page');
        $limit = I('post.limit');
        $ReturnArr['count'] = $count;
        $ReturnArr['data'] = $dataList;
        $this->ajaxReturn($ReturnArr, 'json');
    }

    //补发定时任务请求
    public function sendTask()
    {
        //请求定时任务接口,修改解冻时间
        $freezeorder = M('orderfreezemoney')->where('id=' . I('post.id'))->field('freezeordernumber,sysordernumber,expect_time')->find();
        $msg = "冻结订单[" . $freezeorder['freezeordernumber'] . "]的请求任务补发:";
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
//        echo $task_return;die;
        $task_return = json_decode($task_return, true);
        if ($task_return['status'] == '0000') {
            M('orderfreezemoney')->where('id=' . I('post.id'))->setField('send', 1);
            $this->addAdminOperate($msg . '请求成功');
            $this->ajaxReturn(['msg' => "定时任务请求成功!", 'status' => 'ok'], "json");
        } else {
            $this->addAdminOperate($msg . '请求失败,' . $task_return['msg']);
            $this->ajaxReturn(['msg' => "定时任务请求失败:" . $task_return['msg'], 'status' => 'no'], "json");
        }
    }

    //添加金额变动记录
    public function addMoneyChange($old, $changemoney, $nowmoney, $changetype)
    {
        $data = [
            'userid' => $old['userid'],
            'payapiid' => $old['payapiid'],
            'accountid' => $old['payapiaccountid'],
            'oldmoney' => $old['money'],
            'changemoney' => $changemoney,
            'nowmoney' => $nowmoney,
            'changetype' => $changetype,
            'remarks' => I('remarks'),
            'datetime' => date('Y-m-d H:i:s')
        ];
        MoneyModel::addMoneyChange($data);
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