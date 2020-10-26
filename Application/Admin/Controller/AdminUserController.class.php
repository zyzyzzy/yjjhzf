<?php
/**
 * 后台管理员管理控制器
 */

namespace Admin\Controller;

use Admin\Model\AdminuserModel;
use Admin\Model\DatatemplateModel;
use Admin\Model\DatatemplateuserModel;
use Admin\Model\UserprivacyModel;
use Admin\Model\GooglecodeModel;

class AdminUserController extends CommonController
{
    public function __construct()
    {
        parent::__construct();
    }

    //管理员列表
    public function AdminUserList()
    {
        $this->display();
    }

    //加载管理员列表
    public function LoadAdminUserList()
    {
        $where = [];
        $i = 0;
        $where[$i] = "del=0";
        $i++;
        $user_name = I('post.user_name', '', 'trim');
        if ($user_name <> '') {
            $where[$i] = "user_name like '%" . $user_name . "%'";
            $i++;
        }
        $bieming = I('post.bieming', '', 'trim');
        if ($bieming <> '') {
            $where[$i] = "bieming like '%" . $bieming . "%'";
            $i++;
        }
        $status = I("post.status", "", 'intval');
        if ($status <> "") {
            $where[$i] = '`status` = ' . $status;
            $i++;
        }
        $count = AdminuserModel::getCount($where);
        $datalist = AdminuserModel::getPageLimit($where, I("post.page", "1"), I("post.limit", "10"));
        //2019-4-11 rml:更好的区分谷歌验证状态 1=开通但未开启  2=已开启  3=未开通
        foreach ($datalist as $key => $val) {
            //查询管理员是否开通谷歌验证
            $find_google = GooglecodeModel::getInfo('admin', $val['id']);
            if ($find_google) {
                $datalist[$key]['google'] = $find_google['status'];
            } else {
                $datalist[$key]['google'] = 2;
            }
        }
        $ReturnArr = [
            'code' => 0,
            'msg' => '数据加载成功',
            'count' => $count,
            'data' => $datalist
        ];
        $this->ajaxReturn($ReturnArr, 'JSON');
    }

    //添加管理员页面
    public function AdminUserAdd()
    {
        $this->display();
    }

    //提交表单，添加管理员
    public function createAdminUser()
    {
        $admin_name = I('post.user_name', '', 'trim');
        $return = AddSave('adminuser', 'add', '添加管理员');
        $this->addAdminOperate('添加管理员:' . $admin_name . ',' . $return['msg']);
        $this->ajaxReturn($return, 'json');
    }

    //修改管理员页面
    public function AdminUserEdit()
    {
        $id = I('get.id', 0, 'intval');
        $one_info = AdminuserModel::getAdminiInfo(['id' => $id]);
        $this->assign('one_info', $one_info);
        $this->display();
    }

    //提交表单，修改管理员
    public function AdminUserUpdate()
    {
        $admin_name = I('post.user_name', '', 'trim');
        $return = AddSave('adminuser', 'save', '修改管理员');
        $this->addAdminOperate('修改管理员:' . $admin_name . ',' . $return['msg']);
        $this->ajaxReturn($return, 'json');
    }

    /*
     * 删除管理员
     */
    public function AdminUserDel()
    {
        $id = I('post.id', 0, 'intval');
        $admin_name = AdminuserModel::getAdminName($id);
        $res = AdminuserModel::editPassword($id, ['del' => 1]);
        if ($res) {
            $this->addAdminOperate('删除管理员:' . $admin_name . ',删除成功');
            $this->ajaxReturn(['status' => 'ok', 'msg' => '删除成功']);
        } else {
            $this->addAdminOperate('删除管理员:' . $admin_name . ',删除失败');
            $this->ajaxReturn(['status' => 'no', 'msg' => '删除失败']);
        }
    }

    /*
     * 修改管理员状态
     */
    public function UpdateStatus()
    {
        $id = I("post.id", 0, 'intval');
        $admin_name = AdminuserModel::getAdminName($id);
        $status = I("post.status", 0, 'intval');
        if ($status == 1) {
            $msg = '修改为启用';
        } else {
            $msg = '修改为禁用';
        }
        $res = AdminuserModel::editPassword($id, ['status' => $status]);
        if ($res) {
            $this->addAdminOperate('修改管理员[' . $admin_name . ']状态:' . $msg . ',修改成功');
            $this->ajaxReturn(['status' => 'ok', 'msg' => '修改成功']);
        } else {
            $this->addAdminOperate('修改管理员[' . $admin_name . ']状态:' . $msg . ',修改失败');
            $this->ajaxReturn(['status' => 'no', 'msg' => '修改失败']);
        }
    }

    /**************************************************************/
    //2019-1-14 任梦龙：添加系统后台管理员回收站
    //回收站列表页面
    public function recoveryAdminUser()
    {
        $this->display();
    }

    //加载列表
    public function loadRecoveryAdmin()
    {
        $where = [];
        $i = 0;
        $where[$i] = 'del=1';
        $i++;
        //管理员名称
        $user_name = I('post.user_name', '', 'trim');
        if ($user_name <> '') {
            $where[$i] = "user_name like '%" . $user_name . "%'";
            $i++;
        }
        $this->ajaxReturn(PageDataLoad('adminuser', $where), 'JSON');
    }

    //删除单条记录
    public function delActualAdmin()
    {
        $id = I('post.id', 0, 'intval');
        $r = AdminuserModel::delActualAdmin($id);
        $this->recoveryReturn($r, $this->del);
    }

    //删除多条记录
    public function delAllActualInfo()
    {
        $idstr = I("post.idstr", "");
        if ($idstr == "") {
            $this->ajaxReturn(['status' => 'no', 'msg' => '请选择账号！']);
        }
        $r = AdminuserModel::delAllInfo($idstr);
        $this->recoveryReturn($r, $this->del);
    }

    //2019-1-15 任梦龙：添加恢复单条记录方法
    public function recoveryInfo()
    {
        $id = I('post.id', 0, 'intval');
        $r = AdminuserModel::regainInfo($id);
        $this->recoveryReturn($r, $this->recovery);
    }

    //2019-1-15 任梦龙：恢复多条记录
    public function recoveryAll()
    {
        $idstr = I("post.idstr", "");
        if ($idstr == "") {
            $this->ajaxReturn(['status' => 'no', 'msg' => '请选择账号！']);
        }
        $r = AdminuserModel::regainAllData($idstr);
        $this->recoveryReturn($r, $this->recovery);
    }

    //添加分配隐私页面
    public function GivePrivacy()
    {
        $privacy = UserprivacyModel::privacyList();
        $this->assign('privacy', $privacy);
        $id = I('get.id');
        $this->assign('id', $id);
        //查找管理员是否已经有分配隐私
        $privacy_id = AdminuserModel::findPrivacyId($id);
        if ($privacy_id) {
            $pri_arr = explode(',', $privacy_id);
            $this->assign('pri_arr', $pri_arr);
        }
        $this->display();
    }

    //确认分配隐私
    //2019-4-10 任梦龙：修改之前先去查找原来是否有隐私id组,对比是否有重新修改  （测试以正常）
    public function ConfirmPrivacy()
    {
        $id = I('post.id');
        $privacy_id = I('post.privacy_id', '');
        $old_privacy_id = AdminuserModel::findPrivacyId($id);
        $old_arr = $old_privacy_id ? explode(',', $old_privacy_id) : '';
        if ($privacy_id == $old_arr) {
            $this->ajaxReturn(['status' => 'no', 'msg' => '未进行任何修改,请确认']);
        }
        $pri_id_arr = $privacy_id ? implode(',', $privacy_id) : '';
        $admin_name = AdminuserModel::getAdminName($id);
        $msg = '为管理员[' . $admin_name . ']分配隐私:';
//        $res = AdminuserModel::editPrivacyId($id, $pri_id_arr);
        $res = AdminuserModel::editPassword($id, ['privacy_id' => $pri_id_arr]);
        if ($res) {
            $this->addAdminOperate($msg . '分配成功');
            $this->ajaxReturn(['status' => 'ok', 'msg' => '分配成功']);
        } else {
            $this->addAdminOperate($msg . '分配失败');
            $this->ajaxReturn(['status' => 'no', 'msg' => '分配失败']);
        }
    }

    //修改谷歌验证功能页面
    public function editGoogle()
    {
        $id = I('get.id', 0);
        if (!$id) {
            $this->ajaxReturn(['status' => 'no', 'msg' => '请勿非法操作']);
        }
        $code_type = $this->getCodeType();
        $this->assign('code_type', $code_type);
        $this->display();
    }

    //确认修改
    public function googleEdit()
    {
        $verfiy_code = I('post.verfiy_code', '', 'trim');
        $code_type = I('post.code_type', 0, 'intval');
        $admin_id = I('post.admin_id');
        $switch = I('post.switch');
        $google = I('post.google');
        $admin_name = AdminuserModel::getAdminName($admin_id);
        if ($switch == 1) {
            if ($google != 2) {
                $this->ajaxReturn(['status' => 'no', 'msg' => '已是开通状态,请确认']);
            }
            $msg = '修改管理员[' . $admin_name . ']的谷歌验证状态:修改为开通,';
            $this->checkVerifyCode($verfiy_code, $code_type, $msg);
            $res = GooglecodeModel::addInfo('admin', $admin_id);
        } else {
            if ($google == 2) {
                $this->ajaxReturn(['status' => 'no', 'msg' => '已是关闭状态,请确认']);
            }
            $msg = '修改管理员[' . $admin_name . ']的谷歌验证状态:修改为关闭,';
            $this->checkVerifyCode($verfiy_code, $code_type, $msg);
            $res = GooglecodeModel::delInfo('admin', $admin_id);
        }
        if ($res) {
            $this->addAdminOperate($msg . '修改成功');
            $this->ajaxReturn(['status' => 'ok', 'msg' => '修改成功']);
        }
        $this->addAdminOperate($msg . '修改失败');
        $this->ajaxReturn(['status' => 'no', 'msg' => '修改失败']);
    }

    /**
     * 2019-01-29汪桂芳添加
     * 选择统计模板页面
     */
    public function selectTemplate()
    {
        $admin_id = I('get.id');
        $this->assign('admin_id', $admin_id);
        //查询该账号的模板
        $user_imgs = DatatemplateuserModel::getAllTemplate($admin_id);
        $count = count($user_imgs);
        $this->assign('count', $count);

        //查询所有模板
        $all_imgs = DatatemplateModel::getAllTemplate();
        foreach ($all_imgs as $k => $v) {
            $all_imgs[$k]['src'] = '/' . $v['img_name'];
            foreach ($user_imgs as $key => $val) {
                if ($v['id'] == $val['datatemplate_id']) {
                    $all_imgs[$k]['select'] = 1;
                }
            }
        }
        $this->assign('all_imgs', $all_imgs);

        $this->display();
    }

    /**
     * 2019-01-29汪桂芳添加
     * 选择统计模板处理程序
     */
    //2019-3-22 任梦龙：添加操作记录
    public function confirmTeplate()
    {
        $admin_id = I('post.admin_id');
        $admin_name = AdminuserModel::getAdminName($admin_id);
        $msg = '选择管理员[' . $admin_name . ']的选择统计模板:';
        //读取原来用户的模板
        $all = DatatemplateuserModel::getAllTemplate($admin_id);
        $old = [];//存储原始数据的数组
        foreach ($all as $k => $v) {
            $old[] = $v['datatemplate_id'];
        }
        $templateid = I('post.templateid');
        if (!$templateid) {
            $this->ajaxReturn(['status' => 'no', 'msg' => '请选择统计模板']);
        }
        $templateid = rtrim($templateid, ',');
        $template = explode(',', $templateid);
        $now = [];//存储一直存在的数据的数据
        foreach ($template as $k => $v) {
            //存在就不管,不存在就添加,多余就删除
            if (!in_array($v, $old)) {
                DatatemplateuserModel::addTemplate($admin_id, $v);
            } else {
                $now[] = $v;
            }
        }
        //删除多余数据,即从原始数据里去掉一直存在的数据,剩下的就是要删掉的数据
        $del = array_diff($old, $now);
        if ($del) {
            $del = implode(',', $del);
            DatatemplateuserModel::deleteTemplate($admin_id, $del);
        }
        $this->addAdminOperate($msg . '选择成功');
        $this->ajaxReturn(['status' => 'ok', 'msg' => '选择成功']);
    }

    //添加管理密码的开关
    public function editManageStatus()
    {
        $id = I("post.id", 0, 'intval');
        $manage_status = I("post.manage", 0, 'intval');
        $admin_name = AdminuserModel::getAdminName($id);  //查询对应的管理信息
        $msg = '修改管理员[' . $admin_name . ']的管理密码状态：';
        //管理密码和谷歌验证两者必须存在一个.即当关闭管理密码时，需要判断谷歌验证是否有开启(需为开通并开启)，如果没有，则该管理密码不能关闭
        if ($manage_status == 2) {
            $error_msg = '修改为关闭';
            $find_google = GooglecodeModel::getInfo('admin', $id);
            if (!$find_google) {
                $this->addAdminOperate($msg . $error_msg . ',修改失败,谷歌验证与管理密码必须开启其中一个,此时没有谷歌验证记录');
                $this->ajaxReturn(['status' => 'no', 'msg' => '修改失败,谷歌验证与管理密码必须开启其中一个']);
            }
            if ($find_google['status'] != 1) {
                $this->addAdminOperate($msg . $error_msg . ',修改失败,谷歌验证与管理密码必须开启其中一个,此时还没开启谷歌验证');
                $this->ajaxReturn(['status' => 'no', 'msg' => '修改失败,该管理员还未开启谷歌验证']);
            }
        }
        if ($manage_status == 1) {
            $error_msg = '修改为开启';
        }
        $res = AdminuserModel::editPassword($id, ['manage_status' => $manage_status]);
        if ($res) {
            $this->addAdminOperate($msg . $error_msg . ',修改成功');
            $this->ajaxReturn(['status' => 'ok', 'msg' => '修改成功']);
        } else {
            $this->addAdminOperate($msg . $error_msg . ',修改失败');
            $this->ajaxReturn(['status' => 'no', 'msg' => '修改失败']);
        }
    }

    //修改密码的总页面
    public function editAdminPwd()
    {
        $this->assign('admin_id', I('get.id'));
        $this->display();
    }

    //修改登录密码页面
    public function editLoginPwd()
    {
        $code_type = $this->getCodeType();
        $this->assign('code_type', $code_type);
        $this->display();
    }

    //确认修改登录密码
    public function loginPwdEdit()
    {
        $verfiy_code = I('post.verfiy_code', '', 'trim');
        $code_type = I('post.code_type', 0, 'intval');
        $login_pwd = I('post.password', '', 'trim');
        $admin_id = I('post.admin_id');
        $admin_info = AdminuserModel::getAdminiInfo(['id' => $admin_id]);
        $msg = '修改管理员[' . $admin_info['user_name'] . ']的登录密码:';
        $this->checkVerifyCode($verfiy_code, $code_type, $msg);
        //验证密码的合法性
        if (!$login_pwd) {
            $this->ajaxReturn(['status' => 'no', 'msg' => '请输入新密码']);
        }
        if (MD5($login_pwd) == $admin_info['password']) {
            $this->ajaxReturn(['status' => 'no', 'msg' => '密码与原密码一致，请重新输入']);
        }
        //2019-4-18 rml：修改验证密码格式
        $pwd_rule = '/^[A-Za-z0-9]{6,20}$/';
        if (!preg_match($pwd_rule, $login_pwd)) {
            $this->ajaxReturn(['status' => 'no', 'msg' => '登录密码由大小写英文，数字组成，长度在6-20字符之间']);
        }
        $res = AdminuserModel::editPassword($admin_id, ['password' => md5($login_pwd)]);
        if ($res) {
            $this->addAdminOperate($msg . ',修改成功');
            $this->ajaxReturn(['status' => 'ok', 'msg' => '修改成功']);
        }
        $this->addAdminOperate($msg . ',修改失败');
        $this->ajaxReturn(['status' => 'no', 'msg' => '修改失败']);
    }

    //修改管理密码页面
    public function editManagePwd()
    {
        $code_type = $this->getCodeType();
        $this->assign('code_type', $code_type);
        $this->display();
    }

    //确认修改管理密码
    public function managePwdEdit()
    {
        $verfiy_code = I('post.verfiy_code', '', 'trim');
        $code_type = I('post.code_type', 0, 'intval');
        $manage_pwd = I('post.manage_pwd', '', 'trim');
        $admin_id = I('post.admin_id');
        $admin_info = AdminuserModel::getAdminiInfo(['id' => $admin_id]);
        $msg = '修改管理员[' . $admin_info['user_name'] . ']的管理密码:';
        $this->checkVerifyCode($verfiy_code, $code_type, $msg);
        //验证密码的合法性
        if (!$manage_pwd) {
            $this->ajaxReturn(['status' => 'no', 'msg' => '请输入新密码']);
        }
        if (md5($manage_pwd) == $admin_info['manage_pwd']) {
            $this->ajaxReturn(['status' => 'no', 'msg' => '密码与原密码一致，请重新输入']);
        }
        //2019-4-18 rml：修改验证密码格式
        $pwd_rule = '/^[A-Za-z0-9]{6,20}$/';
        if (!preg_match($pwd_rule, $manage_pwd)) {
            $this->ajaxReturn(['status' => 'no', 'msg' => '管理密码由大小写英文，数字组成，长度在6-20字符之间']);
        }
        $res = AdminuserModel::editPassword($admin_id, ['manage_pwd' => md5($manage_pwd)]);
        if ($res) {
            $this->addAdminOperate($msg . ',修改成功');
            $this->ajaxReturn(['status' => 'ok', 'msg' => '修改成功']);
        }
        $this->addAdminOperate($msg . ',修改失败');
        $this->ajaxReturn(['status' => 'no', 'msg' => '修改失败']);
    }

    //设置管理员同一账号登录页面
    public function setSameAdmin()
    {
        $code_type = $this->getCodeType();
        $this->assign('code_type', $code_type);
        $same_admin = AdminuserModel::getSameAdmin(I('get.id'));
        $this->assign('same_admin', $same_admin);
        $this->display();
    }

    //确认修改同一账号登录设置
    public function editSameAdmin()
    {
        $id = I('post.id');
        $switch = I('post.switch');
        $admin_name = AdminuserModel::getAdminName($id);
        $verfiy_code = I('post.verfiy_code', '', 'trim');
        $code_type = I('post.code_type', 0, 'intval');
        $val = $switch ? '修改为关闭,' : '修改为开启,';
        $msg = '设置管理员[' . $admin_name . ']账号是否可以同时登录:' . $val;
        $this->checkVerifyCode($verfiy_code, $code_type, $msg);   //验证密码
        $res = AdminuserModel::editPassword($id, ['same_admin' => $switch]);
        if ($res) {
            $this->addAdminOperate($msg . '修改成功');
            $this->ajaxReturn(['status' => 'ok', 'msg' => '修改成功']);
        }
        $this->addAdminOperate($msg . ',修改失败');
        $this->ajaxReturn(['status' => 'no', 'msg' => '修改失败']);

    }


}
