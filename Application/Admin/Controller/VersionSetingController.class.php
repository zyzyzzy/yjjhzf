<?php
/**
 * 自定义支付接口设置控制器：版本设置
 */

namespace Admin\Controller;

use Admin\Model\UserModel;
use Admin\Model\VersionModel;

class VersionSetingController extends CommonController
{
    //列表页面
    public function versionList()
    {
        $this->display();
    }

    //加载列表
    public function loadVersionList()
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
        $this->ajaxReturn(PageDataLoad('version', $where));
    }

    //添加
    public function versionAdd()
    {
        $this->display();
    }

    //确认添加
    public function addVersion()
    {
        $msg = '添加接口版本:' . I('post.numberstr', '', 'trim') . ',';
        $return = AddSave('version', 'add', '添加');
        $this->addAdminOperate($msg . $return['msg']);
        $this->ajaxReturn($return, 'json');
    }

    //修改
    public function versionEdit()
    {
        $id = I('get.id');
        $find = VersionModel::getVersionInfo($id);
        $this->assign('find', $find);
        $this->display();
    }

    //确认修改
    public function editVersion()
    {
        $msg = '修改接口版本:' . I('post.numberstr', '', 'trim') . ',';
        $return = AddSave('version', 'save', '修改');
        $this->addAdminOperate($msg . $return['msg']);
        $this->ajaxReturn($return, 'json');
    }

    //删除
    public function versionDel()
    {
        $id = I('post.id');
        $numberstr = VersionModel::getNumberstr($id);
        $msg = '删除接口版本:' . $numberstr;
        //判断版本是否被使用
        $user_version = UserModel::checkVersionUser($id);
        if($user_version){
            $res = VersionModel::delVersionInfo($id);
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
        $numberstr = VersionModel::getNumberstr($id);
        $msg = '修改接口版本[' . $numberstr . ']的状态:';
        $status = I('post.status');
        if ($status == 1) {
            $msgs = $msg . '修改为启用';
        } else {
            $msgs = $msg . '修改为禁用';
        }
        $res = VersionModel::editStatus($id, $status);
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
        $numberstr = VersionModel::getNumberstr($id);
        $msg = '修改接口版本[' . $numberstr . ']的通用开关:';
        $all_use = I('post.all_use');
        if ($all_use == 1) {
            $msgs = $msg . '修改为开启';
        } else {
            $msgs = $msg . '修改为关闭';
        }
        $res = VersionModel::editAlluse($id, $all_use);
        if ($res) {
            $this->addAdminOperate($msgs . ',修改成功');
            $this->ajaxReturn(['status' => 'ok', 'msg' => '修改成功']);
        }
        $this->addAdminOperate($msgs . ',修改失败');
        $this->ajaxReturn(['status' => 'no', 'msg' => '修改失败']);
    }
}