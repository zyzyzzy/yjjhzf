<?php
/**
 * 系统后台--用户的提成设置
 */

namespace Admin\Controller;

use Admin\Model\UsercommissionsetModel;
use Admin\Model\UserModel;

class UserCommissionController extends CommonController
{
    //提成设置页面
    public function commissionSet()
    {
        $userid = I('userid');
        $this->assign('user_id', $userid);

        //查询用户是否为代理商
        $user = UserModel::getInfo($userid);
        $this->assign('type', $user['usertype']);
        $system = C('USER_COMMISSION');
        $b_system = ($system * 100) . '%';
        $this->assign('system', $system);
        $this->assign('b_system', $b_system);
        if ($user['usertype'] == 2) {
            //读取用户原有的费率上限数据
            $commission = UsercommissionsetModel::getCommission($userid);
            if($commission){
                $max_profit = $commission['max_profit'];
            }else {
                //读取系统默认的利润
                $max_profit = $system;
            }
            $this->assign('max_profit', $max_profit);
        }
        $code_type = $this->getCodeType();
        $this->assign('code_type', $code_type);
        $this->display();
    }

    //提成设置出来程序
    //2019-5-6 rml：修改逻辑
    public function editCommission()
    {
        $post = I('post.');
        $verfiy_code = I('post.verfiy_code', '', 'trim');
        $code_type = I('post.code_type', 0, 'intval');
        $this->checkVerifyCode($verfiy_code, $code_type);
        $user_name = UserModel::getUserName($post['user_id']);
        $msg = '修改用户[' . $user_name . '.]的利润上限:';
        $commission = UsercommissionsetModel::getCommission($post['user_id']);
        $data = [
            'admin_id' => session('admin_info.id'),
            'user_id' => $post['user_id'],
            'admin_ip' => getIp(),
            'max_profit' => $post['max_profit'],
            'date_time' => date('Y-m-d H:i:s')
        ];
        if ($commission) {
            $res = UsercommissionsetModel::saveInfo($post['user_id'], $data);
        } else {
            $res = UsercommissionsetModel::addInfo($data);
        }
        if ($res) {
            $this->addAdminOperate($msg . '设置成功');
            $this->ajaxReturn(['status' => 'ok', 'msg' => '设置成功']);
        } else {
            $this->addAdminOperate($msg . '设置失败');
            $this->ajaxReturn(['status' => 'no', 'msg' => '设置失败']);
        }
    }

    //提成记录列表
    //获取用户类型,只有代理商才有提成记录
    public function commissionList()
    {
        $userid = I('userid');
        $user = UserModel::getInfo($userid);
        $this->assign('type', $user['usertype']);
        $this->assign('userid', $userid);
        $this->display();
    }

    //加载提成记录列表
    public function loadCommissionList()
    {
        //搜索
        $where = [];
        $i = 1;
        $userid = I("get.userid", "");
        if ($userid <> "") {
            $where[$i] = "userid = " . $userid;
            $i++;
        }
        $changetype = 7;
        if ($changetype <> "") {
            $where[$i] = "changetype = '" . $changetype . "'";
            $i++;
        }
        $transid = I("post.transid", "");
        if ($transid <> "") {
            $where[$i] = "(transid like '%" . $transid . "%')";
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

        //分页
        $ReturnArr = [
            'code' => 0,
            'msg' => '数据加载成功', //响应结果
            'count' => 0, //总页数
            'data' => [
            ]
        ];
        //总页数
        $count = M('moneychange')->where($where)->count();
        $page = I('post.page');
        $limit = I('post.limit');
        $datalist = M('moneychange')->where($where)->page($page, $limit)->order('datetime desc')->select();

        $ReturnArr['count'] = $count;
        $ReturnArr['data'] = $datalist;
        $this->ajaxReturn($ReturnArr, 'json');
    }
}