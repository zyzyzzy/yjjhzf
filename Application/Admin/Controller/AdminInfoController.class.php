<?php

namespace Admin\Controller;

use Think\Controller;
use Admin\Model\AdminuserModel;
use Admin\Model\AdmininfoModel;
use Admin\Model\GooglecodeModel;
use Admin\Model\AdminoperaterecordModel;
use Admin\Model\UsersessionidModel;

//2019-4-17 rml：管理自己的信息不受权限控制
class AdminInfoController extends Controller
{
    public function __construct()
    {
        parent::__construct();
        //2019-4-22 rml：由于不受权限控制,那么需要单独写掉线的方法
        $same_admin = AdminuserModel::getSameAdmin(session('admin_info.id'));
        if ($same_admin == 1) {
            $old_sessionid = UsersessionidModel::getSessionId(session('admin_info.id'));
            if ($old_sessionid != session_id()) {
                session_destroy();
                exit("<script>alert('对不起，账号在另一处登录,您被迫下线');window.location.href='" . U('AdminLogin/index') . "';</script>");
            }
        }
        if (!session('admin_info.id')) {
            $this->redirect('AdminLogin/index');
        }
    }

    public function getCodeType()
    {
        $google = GooglecodeModel::getInfo('admin', session('admin_info.id'));
        $manage_status = AdminuserModel::getManageStatus(session('admin_info.id'));
        //二次验证级别高于管理密码，标志状态：1=以管理密码页面遮罩; 2=以二次验证页面遮罩 3=以二次验证遮罩层（当两者都开启的情况下）
        $code_type = 1;
        if ($google) {
            if ($google['status'] == 1) {
                $code_type = 2;
            }
            if ($google['status'] == 1 && $manage_status == 1) {
                $code_type = 3;
            }
        }
        return $code_type;
    }

    public function isExistGoogle()
    {
        $google_code = 0;
        $google = GooglecodeModel::getInfo('admin', session('admin_info.id'));
        if ($google) {
            if ($google['status'] != 1) {
                $google_code = 1;
            } else {
                $google_code = 2;
            }
        }
        return $google_code;
    }

    public function returnCodeRes($verfiy_code, $code_type)
    {
        switch ($code_type) {
            //只验证管理密码
            case 1:
                $manage_pwd = AdminuserModel::getManagePwd(session('admin_info.id'));
                $res = md5($verfiy_code) == $manage_pwd ? true : false;
                break;
            //只验证二次验证码
            case 2:
                $res = $this->veryGoogleCode('admin', session('admin_info.id'), $verfiy_code) ? true : false;
                break;
            //同时验证
            case 3:
                $manage_pwd = AdminuserModel::getManagePwd(session('admin_info.id'));
                $res_manage = md5($verfiy_code) == $manage_pwd ? true : false;
                $res_google = $this->veryGoogleCode('admin', session('admin_info.id'), $verfiy_code) ? true : false;
                if ($res_manage || $res_google) {
                    $res = true;
                } else {
                    $res = false;
                }
                break;
        }
        return $res;
    }

    public function verifyEmpty($verfiy_code)
    {
        if (!$verfiy_code) {
            $this->ajaxReturn(['status' => 'no', 'msg' => '请填写验证码']);
        }
    }

    //2019-4-22 rml:添加，不能修改密码是因为没有这个方法
    public function veryGoogleCode($type, $user_id, $google_code)
    {
        $secret = GooglecodeModel::getSecret($type, $user_id);
        $res = verifcode_googlecode($secret, $google_code);
        return $res;
    }

    public function checkVerifyCode($verfiy_code, $code_type, $msg = '')
    {
        $this->verifyEmpty($verfiy_code);   //判断空值
        $return_res = $this->returnCodeRes($verfiy_code, $code_type);
        if (!$return_res) {
//            $this->addAdminOperate($msg . '验证码错误');
            $this->ajaxReturn(['status' => 'no', 'msg' => '验证码错误,请重试']);
        }
    }

    public function addAdminOperate($msg)
    {
        $data = [
            'admin_id' => session('admin_info.id'),  //2019-3-21 任梦龙：修改为session取值
            'admin_ip' => getIp(),
            'msg' => $msg,
            'date_time' => date('Y-m-d H:i:s')
        ];
        AdminoperaterecordModel::addLoginTemp($data);
    }

    /**
     * 2019-2-25 任梦龙：修改为个人信息总页面
     */
    public function adminInfo()
    {
        //2019-3-11 任梦龙：已进入页面，给一个session值作为标识
        session('code_switch', 1);
        $this->display();
    }

    //基本信息页面
    public function basicInformation()
    {
        $admin_id = session('admin_info.id');
        $admin = AdminuserModel::getAdmin($admin_id);
        $admin_info = AdmininfoModel::getAdmininfo($admin_id);
        $this->assign('admin', $admin);
        $this->assign('admin_info', $admin_info);
        $this->assign('admin_id', $admin_id);
        //2019-3-11 任梦龙：判断当前操作者的二次验证与管理密码的开启状态,将判断验证码状态的代码提取到CommonController,因为还会有很多页面需要做这样的判断
        $code_type = $this->getCodeType();
        $this->assign('code_type', $code_type);
        $this->display();
    }

    //确认修改基本信息
    public function basicInformationEdit()
    {
        $msg = '修改个人基本信息:';
        $verfiy_code = I('post.verfiy_code', '', 'trim');
        $code_type = I('post.code_type', 0, 'intval');
        $this->checkVerifyCode($verfiy_code, $code_type, $msg);
        $admin_info = AdmininfoModel::getCount(session('admin_info.id'));
        if ($admin_info) {
            $return_res = AddSave('admininfo', 'save', '修改基本信息');
        } else {
            $return_res = AddSave('admininfo', 'add', '修改基本信息');
        }
        $this->addAdminOperate($msg . $return_res['msg']);
        $this->ajaxReturn($return_res, 'json');
    }

    //2019-3-25 任梦龙：管理员的谷歌验证页面
    public function googleInfo()
    {
        $google_code = $this->isExistGoogle();
        $this->assign('google_code', $google_code);
        $this->display();
    }

    //管理员的登录密码
    public function adminLoginPassword()
    {
        //2019-3-13 任梦龙：判断当前操作者的二次验证与管理密码的开启状态
        $code_type = $this->getCodeType();
        $this->assign('code_type', $code_type);
        $this->display();
    }

    //确认修改管理员的登录密码
    public function editLoginPassword()
    {
        $oldloginpassword = I('post.oldloginpassword', '', 'trim');
        $password = I('post.password', '', 'trim');
        $repassword = I('post.repassword', '', 'trim');
        $verfiy_code = I('post.verfiy_code', '', 'trim');  //修改数据时的验证码
        $code_type = I('post.code_type', 0, 'intval');  //验证码类型
        //判断原始密码是否正确
        $old_password = AdminuserModel::getPassword(session('admin_info.id'));
        if (!$oldloginpassword) {
            $this->ajaxReturn(['status' => 'no', 'msg' => '原始密码不得为空']);
        }
        if (md5($oldloginpassword) != $old_password) {
            $this->ajaxReturn(['status' => 'no', 'msg' => '原始密码输入错误']);
        }
        if (!$password) {
            $this->ajaxReturn(['status' => 'no', 'msg' => '登录密码不得为空']);
        }
        if (md5($password) == $old_password) {
            $this->ajaxReturn(['status' => 'no', 'msg' => '原始密码与新密码一致,请重新输入']);
        }
        //验证密码规则,6-20 位字符与数字
        $rule_pwd = '/^[A-Za-z0-9]{6,20}$/';
        if (!preg_match($rule_pwd, $password)) {
            $this->ajaxReturn(['status' => 'no', 'msg' => '密码由大小写英文，数字组成，长度在6-20字符之间！']);
        }
        if ($password != $repassword) {
            $this->ajaxReturn(['status' => 'no', 'msg' => '确认密码不一致,请重新输入']);
        }
        $msg = '修改个人登录密码:';
        //验证谷歌验证
        $this->checkVerifyCode($verfiy_code, $code_type, $msg);
        $res = AdminuserModel::editPassword(session('admin_info.id'), ['password' => md5($password)]);
        if ($res) {
            $this->addAdminOperate($msg . '修改成功');
            $this->ajaxReturn(['status' => 'ok', 'msg' => '登录密码修改成功']);
        } else {
            $this->addAdminOperate($msg . '修改失败');
            $this->ajaxReturn(['status' => 'no', 'msg' => '登录密码修改失败']);
        }
    }

    //管理员自己的管理密码页面
    public function managePwd()
    {
        //2019-3-13 任梦龙：判断当前操作者的二次验证与管理密码的开启状态
        $code_type = $this->getCodeType();
        $this->assign('code_type', $code_type);
        $this->display();
    }

    //确认修改管理员的管理密码
    public function editManagePwd()
    {
        $old_manage_pwd = I('post.old_manage_pwd', '', 'trim');
        $new_manage_pwd = I('post.manage_pwd', '', 'trim');
        $repassword = I('post.repassword', '', 'trim');
        $verfiy_code = I('post.verfiy_code', '', 'trim');  //修改数据时的验证码
        $code_type = I('post.code_type', 0, 'intval');  //验证码类型
        $post = I('post.', '', 'trim');
        $manage_pwd = AdminuserModel::getManagePwd(session('admin_info.id'));
        if (!$old_manage_pwd) {
            $this->ajaxReturn(['status' => 'no', 'msg' => '原始密码不得为空']);
        }
        //判断原始密码和原始数据库中的数据是否一致
        if (md5($old_manage_pwd) != $manage_pwd) {
            $this->ajaxReturn(['status' => 'no', 'msg' => '原始密码错误']);
        }
        if (!$new_manage_pwd) {
            $this->ajaxReturn(['status' => 'no', 'msg' => '新密码不得为空']);
        }
        //验证新密码与旧密码是否一致
        if (md5($new_manage_pwd) == $manage_pwd) {
            $this->ajaxReturn(['status' => 'no', 'msg' => '原始密码与新密码一致，请重新输入']);
        }
        //验证密码规则,6-18 位字符与数字
        $rule_pwd = '/^[A-Za-z0-9]{6,20}$/';  // 2019-3-13 任梦龙：修改规则
        if (!preg_match($rule_pwd, $post['manage_pwd'])) {
            $this->ajaxReturn(['status' => 'no', 'msg' => '密码由大小写英文，数字组成，长度在6-20字符之间！']);
        }
        //验证确认密码
        if ($repassword != $new_manage_pwd) {
            $this->ajaxReturn(['status' => 'no', 'msg' => '确认密码不一致，请重新输入']);
        }
        $msg = '修改个人管理密码:';
        //验证码的判断
        $this->checkVerifyCode($verfiy_code, $code_type, $msg);
        $res = AdminuserModel::editPassword(session('admin_info.id'), ['manage_pwd' => md5($new_manage_pwd)]);
        if ($res) {
            $this->addAdminOperate($msg . '修改成功');
            $this->ajaxReturn(['status' => 'ok', 'msg' => '管理密码修改成功']);
        } else {
            $this->addAdminOperate($msg . '修改失败');
            $this->ajaxReturn(['status' => 'no', 'msg' => '管理密码修改失败']);
        }


    }

    //管理员的登录记录页面
    public function adminLoginRecord()
    {
        $admin_list = AdminuserModel::getAmnAdmin(['del' => 0]);
        $this->assign('admin_list', $admin_list);
        $this->display();
    }

    //加载管理员的登录记录列表,区分超管和普通管理员。如果为超管，则显示所有记录，如果为普通，则只显示自己的记录
    public function loadAdminLoginRecord()
    {
        $where = [];
        $i = 0;
        if (session('admin_info.super_admin') == 1) {
            $admin_id = I('post.admin_id');
            if ($admin_id) {
                $where[$i] = 'admin_id=' . $admin_id;
            }
        } else {
            $where[$i] = 'admin_id=' . session('admin_info.id');
        }
        $i++;
        $start = I("post.start", "");
        $end = I("post.end", "");
        if ($start <> "") {
            $where[$i] = "DATEDIFF('" . $start . "',login_datetime) <= 0";
            $i++;
        }
        if ($end <> "") {
            $where[$i] = "DATEDIFF('" . $end . "',login_datetime) >= 0";
            $i++;
        }
        $login_ip = I("post.login_ip", "", 'trim');
        if ($login_ip <> "") {
            $where[$i] = "(login_ip='" . $login_ip . "')";
            $i++;
        }
        $count = D('adminloginrecord')->where($where)->count();
        //分页页面展示设置,得到数据库里的数据（del=0）二维数组
        $datalist = D('adminloginrecord')->where($where)->page(I("post.page", "1"), I("post.limit", "10"))->order('id DESC')->select();
        //通过user_id查询对应的用户名字
        foreach ($datalist as $key => $val) {
            $datalist[$key]['admin_name'] = AdminuserModel::getAdminName($val['admin_id']);
        }
        $ReturnArr = [
            'code' => 0,
            'msg' => '数据加载成功', //响应结果
            'count' => $count, //总页数
            'data' => $datalist
        ];
        $this->ajaxReturn($ReturnArr, 'JSON');
    }

    //管理员的操作记录页面
    public function adminOperateList()
    {
        $admin_list = AdminuserModel::getAmnAdmin(['del' => 0]);
        $this->assign('admin_list', $admin_list);
        $this->display();
    }

    //加载管理员的操作记录列表,区分超管和普通管理员。如果为超管，则显示所有记录，如果为普通，则只显示自己的记录
    public function loadAdminOperate()
    {
        $where = [];
        $i = 0;
        if (session('admin_info.super_admin') == 1) {
            $admin_id = I('post.admin_id');
            if ($admin_id) {
                $where[$i] = 'admin_id=' . $admin_id;
            }
        } else {
            $where[$i] = 'admin_id=' . session('admin_info.id');
        }
        $i++;
        $start = I("post.start", "");
        $end = I("post.end", "");
        if ($start <> "") {
            $where[$i] = "DATEDIFF('" . $start . "',date_time) <= 0";
            $i++;
        }
        if ($end <> "") {
            $where[$i] = "DATEDIFF('" . $end . "',date_time) >= 0";
            $i++;
        }
        $admin_ip = I("post.admin_ip", "", 'trim');
        if ($admin_ip <> "") {
            $where[$i] = "(admin_ip = '" . $admin_ip . "')";
            $i++;
        }
        $count = M('adminoperaterecord')->where($where)->count();
        $datalist = M('adminoperaterecord')->where($where)->page(I('post.page', 0), I('post.limit', 10))->order('id DESC')->select();
        foreach ($datalist as $key => $val) {
            $datalist[$key]['admin_name'] = AdminuserModel::getAdminName($val['admin_id']);
        }
        $ReturnArr = [
            'code' => 0,
            'msg' => '数据加载成功', //响应结果
            'count' => $count, //总页数
            'data' => $datalist
        ];
        $this->ajaxReturn($ReturnArr, 'JSON');
    }

}
