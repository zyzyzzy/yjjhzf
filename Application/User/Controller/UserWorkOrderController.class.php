<?php

namespace User\Controller;

use User\Model\UserworkorderModel;
use User\Model\UserworkordercontentModel;
use User\Model\UserworkorderhelpModel;

class UserWorkOrderController extends UserCommonController
{
    public function __construct()
    {
        parent::__construct();
    }

    //用户工单管理列表
    public function userWorkList()
    {
        $user_id = session('user_info.id');
        $this->assign('user_id', $user_id);
        $child_id = session('child_info.id');
        $this->assign('child_id', $child_id);
        $this->display();
    }

    //加载列表
    public function loadUserWorkList()
    {
        $user_id = I('get.user_id');
        $where = [];
        $where[0] = 'user_id = ' . $user_id;
        $where[1] = "user_del=0";
        $i = 2;
        $title = I('post.title','','trim');
        if ($title <> "") {
            $where[$i] = "(title like '%" . $title . "%' )";
            $i++;
        }

        $work_num = I("post.work_num", "",'trim');
        if ($work_num <> "") {
            $where[$i] = "(work_num like '%" . $work_num . "%')";
            $i++;
        }

        $status = I("post.status", "");
        if ($status <> "") {
            $where[$i] = "status = " . $status;
            $i++;
        }

        $start = I("post.start", "");
        if ($start <> "") {
            $where[$i] = "DATEDIFF('" . $start . "',date_time) <= 0";
            $i++;
        }
        $end = I("post.end", "");
        if ($end <> "") {
            $where[$i] = "DATEDIFF('" . $end . "',date_time) >= 0";
            $i++;
        }

        $list = UserworkorderModel::workOrderPage($where);
        $this->ajaxReturn($list, 'JSON');
    }

    //添加工单页面
    public function addUserWork()
    {
        $this->assign('user_id', session('user_info.id'));
        $child_id = empty(session('child_info')) ? 0 : session('child_info.id');
        $this->assign('child_id', $child_id);
        $this->display();
    }

    //提交表单，添加
    public function userWorkAdd()
    {
        $res = AddSave('userworkorder', 'add', '添加工单');
        $this->addUserOperate("添加工单:" . $res['msg']);
        $this->ajaxReturn($res, 'json');
    }

    //查看工单信息
    public function seeWorkOrder()
    {
        $this->assign('user_id', session('user_info.id'));
        $child_id = empty(session('child_info')) ? 0 : session('child_info.id');
        $this->assign('child_id', $child_id);

        $user_work_id = I('get.id');
        $info = UserworkorderModel::getWorkInfo($user_work_id);
        $info['content'] = html_entity_decode($info['content']);
        $this->assign('info', $info);

        //查询工单沟通记录信息
        $list = UserworkordercontentModel::getContent($user_work_id);
        $this->assign('list', $list);

        //将管理员之前的信息都改为已读
        UserworkordercontentModel::changeRead($user_work_id);

        //判断是否可以回复
        $l = count($list);
        $i = 1;
        foreach ($list as $k => $v) {
            if ($i == $l) {
                $key = $k;
            }
            $i++;
        }

        $replay = 1;
        if (!$l || $list[$key]['admin_user'] == 'user') {
            $replay = 0;
        }
        $this->assign('replay', $replay);
        $code_type = $this->getCodeType();
        $this->assign('code_type', $code_type);
        if ($info['status'] == 3) {
            $this->display('seeUserWork');
        } else {
            $this->display();
        }
    }

    //确认回复
    public function confirmReply()
    {
        $reply_content = I('post.reply_content');
        $sensitive_content = I('post.sensitive_content');
        $user_id = I('post.user_id');
        $child_id = I('post.child_id');
        $work_id = I('post.work_id');
        $work_num = UserworkorderModel::getWorkNumById($work_id);
        $msg = "用户回复工单(工单编号为$work_num):";
        if (!$reply_content && !$sensitive_content) {
            $this->addUserOperate($msg . "回复内容为空");
            $this->ajaxReturn(['status' => 'no', 'msg' => '回复内容不能为空']);
        }

        if ($reply_content) {
            $save_data = [
                'workorder_id' => $work_id,
                'admin_user' => 'user',
                'user_id' => $user_id,
                'child_id' => $child_id,
                'content' => $reply_content,
                'datetime' => date("Y-m-d H:i:s")
            ];
            $res = UserworkordercontentModel::addContent($save_data);
        }

        if ($sensitive_content) {
            $save_data = [
                'workorder_id' => $work_id,
                'admin_user' => 'user',
                'user_id' => $user_id,
                'child_id' => $child_id,
                'content' => $sensitive_content,
                'sensitive' => 1,
                'superior_id' => $res,
                'datetime' => date("Y-m-d H:i:s")
            ];
            UserworkordercontentModel::addContent($save_data);
        }

        if ($res) {
            $this->addUserOperate($msg . "回复成功");
            $this->ajaxReturn(['status' => 'ok', 'msg' => '回复成功']);
        } else {
            $this->addUserOperate($msg . "回复失败");
            $this->ajaxReturn(['status' => 'no', 'msg' => '回复失败，请稍后重试']);
        }
    }

    //谷歌谷歌验证
    public function verifyGoogle()
    {
        $id = I('id');
        $verfiy_code = I('post.verfiy_code', '', 'trim');
        $code_type = I('post.code_type', 0, 'intval');
        $workorder_id = UserworkordercontentModel::getWorkOrderid($id);
        $work_num = UserworkorderModel::getWorkNumber($workorder_id);
        $msg = "查看用户工单[工单编号为$work_num]的隐私信息:";

        $this->checkVerifyCode($verfiy_code, $code_type, $msg);
        //查询用户隐私信息
        $content = M('userworkordercontent')->where('id=' . $id)->getField('content');
        $content = html_entity_decode($content);
        $data = [
            'id' => $id,
            'content' => $content,
            'status' => 'ok',
            'msg' => '验证成功',
        ];
        $this->addUserOperate($msg . '谷歌验证成功');
        $this->ajaxReturn($data, 'json');
    }

    //确认问题已解决
    public function changeStatus()
    {
        $work_id = I('post.work_id');
        $status = I('post.status');
        $work_num = UserworkorderModel::getWorkNumById($work_id);
        $msg = "修改工单(工单编号为$work_num)状态为已解决:";
        $res = UserworkorderModel::changeUserWork($work_id, $status);
        if ($res) {
            $this->addUserOperate($msg . "修改成功");
            $this->ajaxReturn(['status' => 'ok', 'msg' => '修改成功']);
        } else {
            $this->addUserOperate($msg . "修改失败");
            $this->ajaxReturn(['status' => 'no', 'msg' => '修改失败，请稍后重试']);
        }
    }

    //删除用户工单记录
    public function delWorkOrder()
    {
        $id = I('post.id');
        $work_num = UserworkorderModel::getWorkNumById($id);
        $msg = "删除工单(工单编号为$work_num):";
        $res = UserworkorderModel::delUserWork($id);
        if ($res) {
            $this->addUserOperate($msg . "删除成功");
            $this->ajaxReturn(['status' => 'ok', 'msg' => '删除成功']);
        } else {
            $this->addUserOperate($msg . "删除失败");
            $this->ajaxReturn(['status' => 'no', 'msg' => '删除失败，请稍后重试']);
        }
    }

    //批量删除用户工单记录
    public function delAll()
    {
        $id_str = I('post.id_str', '');
        $msg = "批量删除工单:";
        if ($id_str == '') {
            $this->addUserOperate($msg . "未选择工单");
            $this->ajaxReturn(['status' => 'no', 'msg' => '请选择工单']);
        }
        $r = UserworkorderModel::delAllWork($id_str);
        if ($r) {
            $this->addUserOperate($msg . "删除成功");
            $this->ajaxReturn(['status' => 'ok', 'msg' => '删除成功']);
        } else {
            $this->addUserOperate($msg . "删除失败");
            $this->ajaxReturn(['status' => 'no', 'msg' => '删除失败']);
        }
    }


    //帮助文档列表页面
    public function workHelpList()
    {
        $this->display();
    }

    //加载帮助文档列表
    public function loadWorkHelpList()
    {
        //读取表前缀
        $where = [];
        $where[0] = 'del=0';
        $i = 1;
        $title = trim(I("post.title", ""));
        if ($title <> "") {
            $where[$i] = "(title like '%" . $title . "%' or content like '%" . $title . "%')";
            $i++;
        }
        $start = I("post.start", "");
        if ($start <> "") {
            $where[$i] = "DATEDIFF('" . $start . "',date_time) <= 0";
            $i++;
        }
        $end = I("post.end", "");
        if ($end <> "") {
            $where[$i] = "DATEDIFF('" . $end . "',date_time) >= 0";
            $i++;
        }
        $return_arr = UserworkorderhelpModel::workOrderPage($where);
        $this->ajaxReturn($return_arr, 'json');
    }

    //查看帮助文档内容
    public function seeHelpDocument()
    {
        //查询帮助文档信息
        $workhelp_id = I('get.id');
        $info = UserworkorderhelpModel::getInfoById($workhelp_id);
        $info['content'] = html_entity_decode($info['content']);
        $info['help_content'] = html_entity_decode($info['help_content']);
        $this->assign('info', $info);
        if ($info['type'] == 1) {
            //查询工单沟通记录信息
            $list = UserworkordercontentModel::getContent($info['workorder_id']);
            $this->assign('list', $list);
        }

        $this->display();
    }

    //2019-4-9 任梦龙：批量删除帮助文档
    public function delAllHelp()
    {
        $idstr = I('post.idstr', '');
        if (!$idstr) {
            $this->ajaxReturn(['status' => 'no', 'msg' => '请选择记录']);
        }
        $msg = '批量删除帮助文档:';
        $res = UserworkorderhelpModel::delAll($idstr);
        if ($res) {
            $this->addUserOperate($msg . '删除成功');
            $this->ajaxReturn(['status' => 'ok', 'msg' => '删除成功']);
        }
        $this->addUserOperate($msg . '删除失败');
        $this->ajaxReturn(['status' => 'no', 'msg' => '删除失败']);
    }

}