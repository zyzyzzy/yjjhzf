<?php
/**
 * 自定义结算接口版本设置控制器
 */

namespace Admin\Controller;

use Admin\Model\SettleversionModel;
use Admin\Model\UserModel;

class SettleVersionController extends CommonController
{
    //列表页面
    public function settleVersion()
    {
        $this->display();
    }

    //加载列表
    public function loadSettleVersion()
    {
        $where = [];
        $i = 0;
        $where[$i] = "del=0";
        $i++;
        $numberstr = I('post.numberstr', '', 'trim');
        if ($numberstr <> '') {
            $where[$i] = "numberstr = '" . $numberstr . "'";
            $i++;
        }
        $bieming = I('post.bieming', '', 'trim');
        if ($bieming <> '') {
            $where[$i] = "bieming = '" . $bieming . "'";
            $i++;
        }
        $actionname = I('post.actionname', '', 'trim');
        if ($actionname <> '') {
            $where[$i] = "actionname = '" . $actionname . "'";
            $i++;
        }
        $status = I('post.status', '', 'intval');
        if ($status) {
            $where[$i] = "status=" . $status;
            $i++;
        }
        $all_use = I('post.all_use', '', 'intval');
        if ($all_use) {
            $where[$i] = "all_use=" . $all_use;
            $i++;
        }
        $this->ajaxReturn(PageDataLoad('settleversion', $where));
    }

    //添加页面
    public function versionAdd()
    {
        $this->display();
    }

    //确认添加
    public function addVersion()
    {
        $msg = '添加结算接口版本:' . I('post.numberstr', '', 'trim') . ',';
        $return = AddSave('settleversion', 'add', '添加');
        $this->addAdminOperate($msg . $return['msg']);
        $this->ajaxReturn($return, 'json');
    }

    //修改页面
    public function versionEdit()
    {
        $id = I('get.id');
        $find = SettleversionModel::getVersionInfo($id);
        $this->assign('find', $find);
        $this->display();
    }

    //确认修改
    public function editVersion()
    {
        $msg = '修改结算接口版本:' . I('post.numberstr', '', 'trim') . ',';
        $return = AddSave('settleversion', 'save', '修改');
        $this->addAdminOperate($msg . $return['msg']);
        $this->ajaxReturn($return, 'json');
    }

    //删除
    public function versionDel()
    {
        $id = I('post.id');
        $numberstr = SettleversionModel::getNumberstr($id);
        $msg = '删除结算接口版本:' . $numberstr;
        //判断版本是否被使用
        $user_version = UserModel::checkSettleVersionUser($id);
        if($user_version) {
            $res = SettleversionModel::delVersionInfo($id);
            if ($res) {
                $this->addAdminOperate($msg . ',删除成功');
                $this->ajaxReturn(['status' => 'ok', 'msg' => '删除成功']);
            }
            $this->addAdminOperate($msg . ',删除失败');
            $this->ajaxReturn(['status' => 'no', 'msg' => '删除失败']);
        }else{
            $this->addAdminOperate($msg . ',该版本正在被用户使用中,不能删除');
            $this->ajaxReturn(['status' => 'no', 'msg' => '该版本正在被用户使用中,不能删除']);
        }
    }

    //修改状态开关
    public function updateStatus()
    {
        $id = I('post.id');
        $numberstr = SettleversionModel::getNumberstr($id);
        $msg = '修改结算接口版本[' . $numberstr . ']的状态:';
        $status = I('post.status');
        if ($status == 1) {
            $msgs = $msg . '修改为启用';
        } else {
            $msgs = $msg . '修改为禁用';
        }
        $res = SettleversionModel::editStatus($id, $status);
        if ($res) {
            $this->addAdminOperate($msgs . ',修改成功');
            $this->ajaxReturn(['status' => 'ok', 'msg' => '修改成功']);
        }
        $this->addAdminOperate($msgs . ',修改失败');
        $this->ajaxReturn(['status' => 'no', 'msg' => '修改失败']);
    }

    //修改通用开关
    public function updateAlluse()
    {
        $id = I('post.id');
        $numberstr = SettleversionModel::getNumberstr($id);
        $msg = '修改结算接口版本[' . $numberstr . ']的通用开关:';
        $all_use = I('post.all_use');
        if ($all_use == 1) {
            $msgs = $msg . '修改为开启';
        } else {
            $msgs = $msg . '修改为关闭';
        }
        $res = SettleversionModel::editAlluse($id, $all_use);
        if ($res) {
            $this->addAdminOperate($msgs . ',修改成功');
            $this->ajaxReturn(['status' => 'ok', 'msg' => '修改成功']);
        }
        $this->addAdminOperate($msgs . ',修改失败');
        $this->ajaxReturn(['status' => 'no', 'msg' => '修改失败']);
    }
}