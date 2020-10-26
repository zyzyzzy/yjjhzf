<?php
/**
 * 系统后台工单管理
 */

namespace Admin\Controller;

use Admin\Model\UserworkordercontentModel;
use Admin\Model\UserworkorderhelpModel;
use Think\Controller;
use Admin\Model\UserworkorderModel;
use Admin\Model\GooglecodeModel;
use Admin\Model\AdminuserModel;
use Admin\Model\AdmininfoModel;

class AdminWorkOrderController extends CommonController
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 工单记录
     */
    //用户列表页面
    public function adminWorkList()
    {
        $this->display();
    }

    //加载用户工单列表
    public function loadUserWorkList()
    {
        //读取表前缀
        $pre = C('DB_PREFIX');
        $where = [];
        $i = 0;
        $user_name = I("post.user_name", "");
        if ($user_name <> "") {
            $where[$i] = "(" . $pre . "user.username like '%" . $user_name . "%')";
            $i++;
        }

        $work_num = I("post.work_num", "");
        if ($work_num <> "") {
            $where[$i] = "(work_num like '%" . $work_num . "%')";
            $i++;
        }

        $title = I("post.title", "");
        if ($title <> "") {
            $where[$i] = "(title like '%" . $title . "%')";
            $i++;
        }

        $status = I("post.status", "");
        if ($status <> "") {
            $where[$i] = $pre . "userworkorder.status = " . $status;
            $i++;
        }

        $user_del = I("post.user_del", "");
        if ($user_del <> "") {
            $where[$i] = $pre . "userworkorder.user_del = " . $user_del;
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

        $return_arr = UserworkorderModel::workOrderPage($where);
        $this->ajaxReturn($return_arr, 'json');
    }

    //回复用户工单
    public function replyUserWork()
    {
        //查询管理员
        $admin_id = session('admin_info.id');
        $this->assign('admin_id', $admin_id);

        //查询工单信息
        $user_work_id = I('get.id');  //用户工单id
        $info = UserworkorderModel::getWorkInfo($user_work_id);  //获取该条记录信息
        $info['content'] = html_entity_decode($info['content']);
        $this->assign('info', $info);

        //查询工单沟通记录信息
        $list = UserworkordercontentModel::getContent($user_work_id);
        $this->assign('list', $list);

        //将用户的工单改为已读
        UserworkorderModel::changeRead($user_work_id);

        //将用户之前的信息都改为已读
        UserworkordercontentModel::changeRead($user_work_id);

        //判断管理员的谷歌验证
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
        $admin_id = I('post.admin_id');
        $work_id = I('post.work_id');
        $work_num = UserworkorderModel::getWorkNum($work_id);
        $msg = "回复用户工单[工单编号为$work_num]:";
        if (!$reply_content) {
            $this->addAdminOperate($msg . '回复内容为空');
            $this->ajaxReturn(['status' => 'no', 'msg' => '回复内容不能为空']);
        }
        $save_data = [
            'workorder_id' => $work_id,
            'admin_user' => 'admin',
            'user_id' => $admin_id,
            'content' => $reply_content,
            'datetime' => date("Y-m-d H:i:s")
        ];
        $res = UserworkordercontentModel::addContent($save_data);
        if ($res) {
            //修改工单状态
            UserworkorderModel::changeUserWork($work_id, 2);
            $this->addAdminOperate($msg . '回复成功');
            $this->ajaxReturn(['status' => 'ok', 'msg' => '回复成功']);
        } else {
            $this->addAdminOperate($msg . '回复失败');
            $this->ajaxReturn(['status' => 'no', 'msg' => '回复失败，请稍后重试']);
        }

    }

    //谷歌谷歌验证
    //2019-5-5 rml：优化
    public function verifyGoogle()
    {
        $id = I('id');
        $verfiy_code = I('post.verfiy_code', '', 'trim');
        $code_type = I('post.code_type', 0, 'intval');
        $workorder_id = UserworkordercontentModel::getWorkOrderid($id);
        $work_num = UserworkorderModel::getWorkNum($workorder_id);
        $msg = "查看用户工单[工单编号为$work_num]的隐私信息:";

        $this->checkVerifyCode($verfiy_code, $code_type, $msg);
        //查询用户隐私信息
        $content = M('userworkordercontent')->where('id=' . $id)->getField('content');
        $data = [
            'id' => $id,
            'content' => $content,
            'status' => 'ok',
            'msg' => '验证成功',
        ];
        $this->addAdminOperate($msg . '谷歌验证成功');
        $this->ajaxReturn($data, 'json');
    }

    //确认问题已解决
    public function changeStatus()
    {
        $work_id = I('post.work_id');
        $status = I('post.status');
        $work_num = UserworkorderModel::getWorkNum($work_id);
        $msg = "修改用户工单[工单编号为$work_num]的状态:";
        $res = UserworkorderModel::changeUserWork($work_id, $status);
        if ($res) {
            $this->addAdminOperate($msg . '成功修改为已解决');
            $this->ajaxReturn(['status' => 'ok', 'msg' => '修改成功']);
        } else {
            $this->addAdminOperate($msg . '修改失败');
            $this->ajaxReturn(['status' => 'no', 'msg' => '修改失败，请稍后重试']);
        }
    }

    //删除用户工单记录
    public function delWorkOrder()
    {
        $id = I('post.id');
        $work_num = UserworkorderModel::getWorkNum($id);
        $msg = "删除用户工单[工单编号为$work_num]:";
        $res = UserworkorderModel::delUserWork($id);
        if ($res) {
            $this->addAdminOperate($msg . '删除成功');
            $this->ajaxReturn(['status' => 'ok', 'msg' => '删除成功']);
        } else {
            $this->addAdminOperate($msg . '删除失败');
            $this->ajaxReturn(['status' => 'no', 'msg' => '删除失败']);
        }
    }

    //批量删除用户工单记录
    public function delAll()
    {
        $id_str = I('post.id_str', '');
        $msg = "批量删除用户工单:";
        if ($id_str == '') {
            $this->ajaxReturn(['status' => 'no', 'msg' => '请选择工单']);
        }
        //判断此工单是否是帮助文档
        $new_idstr = [];
        $idstr_arr = explode(',', $id_str);
        foreach ($idstr_arr as $k => $v) {
            $info = UserworkorderhelpModel::getInfo($v);
            if ($info) {
                continue;
            } else {
                $new_idstr[] = $v;
            }
        }
        if ($new_idstr) {
            $r = UserworkorderModel::delAllWork($new_idstr);
            if ($r) {
                $this->addAdminOperate($msg . '已删除未被添加为帮助文档的工单');
                $this->ajaxReturn(['status' => 'ok', 'msg' => '已删除未被添加为帮助文档的工单']);
            } else {
                $this->addAdminOperate($msg . '删除失败');
                $this->ajaxReturn(['status' => 'no', 'msg' => '删除失败']);
            }
        } else {
            $this->addAdminOperate($msg . '删除失败,所选择的工单都被添加为帮助文档,不能删除');
            $this->ajaxReturn(['status' => 'no', 'msg' => '删除失败,您所选择的工单都被添加为帮助文档,不能删除']);
        }
    }

    //添加帮助文档页面
    public function addHelpDocument()
    {
        //查询工单信息
        $user_work_id = I('get.id');  //用户工单id
        $info = UserworkorderModel::getWorkInfo($user_work_id);  //获取该条记录信息
        $info['content'] = html_entity_decode($info['content']);
        $this->assign('info', $info);

        $this->display();
    }

    //添加帮助文档处理程序
    public function helpDocumentAdd()
    {
        $workorder_id = I('workorder_id');
        $work_num = UserworkorderModel::getWorkNum($workorder_id);
        $msg = "用户工单[工单编号为$work_num]添加到帮助文档:";
        $title = I('title');
        $content = I('content');
        if (!$title) {
            $this->addAdminOperate($msg . '标题为空');
            $this->ajaxReturn(['status' => 'no', 'msg' => '标题(关键字)不能为空']);
        }
        if (!$content) {
            $this->addAdminOperate($msg . '问题内容为空');
            $this->ajaxReturn(['status' => 'no', 'msg' => '问题内容不能为空']);
        }
        $data = [
            'workorder_id' => $workorder_id,
            'admin_id' => session('admin_info.id'),
            'title' => $title,
            'content' => $content,
            'date_time' => date('Y-m-d H:i:s'),
            'type' => 1,
        ];
        $res = UserworkorderhelpModel::addInfo($data);
        if ($res) {
            $this->addAdminOperate($msg . '添加成功');
            $this->ajaxReturn(['status' => 'ok', 'msg' => '添加成功']);
        } else {
            $this->addAdminOperate($msg . '添加失败');
            $this->ajaxReturn(['status' => 'no', 'msg' => '添加失败']);
        }
    }

    //添加转交按钮
    public function transWorker()
    {
        $where = [
            'status' => 1,
            'del' => 0,
            'type' => 1,  //代表爱码农的管理员
        ];
        //获取转交管理员列表
        $amn_admin = AdminuserModel::getAmnAdmin($where);
        //获取当前工单信息
        $work_info = UserworkorderModel::getWorkInfo(I('get.id'));
        $this->assign('amn_admin', $amn_admin);
        $this->assign('admin_id', session('admin_info.id'));
        $this->assign('work_num', $work_info['work_num']);
        $this->display();
    }

    //发送工单信息给爱码农的管理员
    public function sendEmail()
    {
        $admin_id = I('post.admin_id');
        $work_num = I('post.work_num');
        $msg = "用户工单[工单编号为$work_num]转交:";
        if (!$admin_id) {
            $this->addAdminOperate($msg . '未选择转交的管理员');
            $this->ajaxReturn([
                'status' => 'no',
                'msg' => '请选择转交管理员'
            ]);
        }
        //判断当前管理员有没有基本信息记录
        $count_admin = AdmininfoModel::getCount(session('admin_info.id'));
        if (!$count_admin) {
            $this->addAdminOperate($msg . '当前管理员的邮箱信息不完整');
            $this->ajaxReturn([
                'status' => 'no',
                'msg' => '请先完善当前管理员的基本信息'
            ]);
        }
        //判断选中的爱码农管理员有没有设置基本信息
        $count_amn = AdmininfoModel::getCount($admin_id);
        if (!$count_amn) {
            $this->addAdminOperate($msg . '选择转交的管理员的邮箱信息不完整');
            $this->ajaxReturn([
                'status' => 'no',
                'msg' => '请完善转交管理员的基本信息'
            ]);
        }
        $admin_name = AdminuserModel::getAdminName($admin_id);
        //获取当前系统管理员的邮箱
        $admin_email = AdmininfoModel::getEmail(session('admin_info.id'));
        //2019-4-9 任梦龙：根据邮箱查找数据
        $conf = M('emailseting')->where(['email' => $admin_email])->find();
        if (!$conf) {
            $this->ajaxReturn(['status' => 'no', 'msg' => '请先配置当前管理员的邮箱设置']);
        }
        //获取转交管理员的邮箱地址
        $receive_email = AdmininfoModel::getEmail($admin_id);
        $conf['user_name'] = C('WEBSITE_NAME');  //发件人姓名  到时候可以在配置文件读取
        $conf['title'] = '转交工单';    //邮件主题，先写死
        $conf['content'] = I('post.content') ? I('post.content') : '';    //邮件内容，可填可不填
        $conf['receive_email'] = $receive_email;   //收件人邮箱
        $conf['email'] = $admin_email;    //发件人邮箱名
        if (SendMail($conf)) {
            $this->addAdminOperate($msg . "成功转交给了用户名为'$admin_email'的管理员");
            $this->ajaxReturn([
                'status' => 'ok',
                'msg' => '发送成功'
            ]);
        } else {
            $this->addAdminOperate($msg . "转交给用户名为'$admin_email'的管理员失败");
            $this->ajaxReturn([
                'status' => 'no',
                'msg' => '发送失败'
            ]);
        }
    }


    /**
     * 帮助文档
     */
    //帮助文档列表页面
    public function workHelpList()
    {
        $this->display();
    }

    //加载帮助文档列表
    public function loadWorkHelpList()
    {
        $where = [];
        $i = 0;
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

    //帮助文档的编辑
    public function editHelpDocument()
    {
        $workhelp_id = I('get.id');
        $info = UserworkorderhelpModel::getInfoById($workhelp_id);
        $info['content'] = html_entity_decode($info['content']);
        $info['help_content'] = html_entity_decode($info['help_content']);
        $this->assign('info', $info);

        $this->display();
    }

    //编辑帮助文档处理程序
    public function helpDocumentEdit()
    {
        $workhelp_id = I('workhelp_id');
        $old_title = UserworkorderhelpModel::getHelpTitle($workhelp_id);
        $title = I('title');
        $content = I('content');
        $help_content = I('help_content');
        $msg = "帮助文档[标题为" . $old_title . "]编辑:";
        if (!$title) {
            $this->addAdminOperate($msg . '标题(关键字)为空');
            $this->ajaxReturn(['status' => 'no', 'msg' => '标题(关键字)不能为空']);
        }
        if (!$content) {
            $this->addAdminOperate($msg . '问题内容为空');
            $this->ajaxReturn(['status' => 'no', 'msg' => '问题内容不能为空']);
        }
        $data = [
            'title' => $title,
            'content' => $content,
            'help_content' => $help_content,
        ];
        $res = UserworkorderhelpModel::editInfo($workhelp_id, $data);
        if ($res) {
            $this->addAdminOperate($msg . '修改成功');
            $this->ajaxReturn(['status' => 'ok', 'msg' => '修改成功']);
        } else {
            $this->addAdminOperate($msg . '修改失败');
            $this->ajaxReturn(['status' => 'no', 'msg' => '修改失败']);
        }
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

    //帮助文档的删除
    public function delWorkHelp()
    {
        $id = I('id');
        $title = UserworkorderhelpModel::getHelpTitle($id);
        $msg = "删除帮助文档[标题为" . $title . "]:";
        $res = UserworkorderhelpModel::delInfo($id);
        if ($res) {
            $this->addAdminOperate($msg . '删除成功');
            $this->ajaxReturn(['status' => 'ok', 'msg' => '删除成功']);
        } else {
            $this->addAdminOperate($msg . '删除失败');
            $this->ajaxReturn(['status' => 'no', 'msg' => '删除失败']);
        }
    }

    //批量删除帮助文档
    public function delAllHelp()
    {
        $id_str = I('post.idstr', '');
        $msg = "帮助文档批量删除:";
        if ($id_str == '') {
            $this->addAdminOperate($msg . '未选择帮助文档');
            $this->ajaxReturn(['status' => 'no', 'msg' => '请选择帮助文档']);
        }
        $r = UserworkorderhelpModel::delAllHelp($id_str);
        if ($r) {
            $this->addAdminOperate($msg . '删除成功');
            $this->ajaxReturn(['status' => 'ok', 'msg' => '删除成功']);
        } else {
            $this->addAdminOperate($msg . '删除失败');
            $this->ajaxReturn(['status' => 'no', 'msg' => '删除失败']);
        }
    }

    //帮助文档手动添加页面
    public function addWorkHelp()
    {
        $this->display();
    }

    //手动添加帮助文档处理程序
    public function workHelpAdd()
    {
        $title = I('title');
        $content = I('content');
        $help_content = I('help_content');
        $msg = "管理员手动添加帮助文档:";
        if (!$title) {
            $this->addAdminOperate($msg . '标题(关键词)为空');
            $this->ajaxReturn(['status' => 'no', 'msg' => '标题(关键词)不能为空']);
        }
        if (!$content) {
            $this->addAdminOperate($msg . '问题内容为空');
            $this->ajaxReturn(['status' => 'no', 'msg' => '问题内容不能为空']);
        }
        if (!$help_content) {
            $this->addAdminOperate($msg . '沟通内容为空');
            $this->ajaxReturn(['status' => 'no', 'msg' => '沟通内容不能为空']);
        }
        $data = [
            'admin_id' => session('admin_info.id'),
            'title' => $title,
            'content' => $content,
            'help_content' => $help_content,
            'date_time' => date('Y-m-d H:i:s'),
            'type' => 0,
        ];
        $res = UserworkorderhelpModel::addInfo($data);
        if ($res) {
            $this->addAdminOperate($msg . '添加成功');
            $this->ajaxReturn(['status' => 'ok', 'msg' => '添加成功']);
        } else {
            $this->addAdminOperate($msg . '添加失败');
            $this->ajaxReturn(['status' => 'no', 'msg' => '添加失败']);
        }
    }
}