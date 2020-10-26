<?php

namespace User\Controller;

use Think\Controller;
use User\Model\ChilduserModel;
use User\Model\UserpasswordModel;
use User\Model\GooglecodeModel;

//2019-4-22 rml:由于用户个人信息需要单独存在，所以不需要继承公共控制器
class UserSelfController extends Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    public function getUserGoogle()
    {
        $google_code = 0;
        if (empty(session('child_info'))) {
            $google = GooglecodeModel::getInfo('user', session('user_info.id'));
        } else {
            $google = GooglecodeModel::getInfo('child', session('child_info.id'));
        }
        if ($google) {
            if ($google['status'] != 1) {
                $google_code = 1;
            } else {
                $google_code = 2;
            }
        }
        return $google_code;
    }

    public function verifyUsertype()
    {
        if (empty(session('child_info'))) {
            $user_type = 1;   //主用户
        } else {
            $user_type = 2;   //子账号
        }
        return $user_type;
    }

    public function getCodeType()
    {
        $code_type = 1;   //默认开启管理密码
        if (!empty(session('child_info'))) {
            $child_google = GooglecodeModel::getInfo('child', session('child_info.id'));
            if ($child_google && $child_google['status'] == 1) {
                $code_type = 2;   //由于管理密码没有状态,所以表示开启了谷歌验证和管理密码
            }
        } else {
            $user_google = GooglecodeModel::getInfo('user', session('user_info.id'));
            if ($user_google && $user_google['status'] == 1) {
                $code_type = 2;
            }
        }
        return $code_type;
    }

    public function checkVerifyCode($verfiy_code, $code_type, $msg='')
    {
        $this->verifyEmpty($verfiy_code);   //判断空值
        $return_res = $this->returnCodeRes($verfiy_code, $code_type);
        if (!$return_res) {
//            $this->addUserOperate($msg . '验证码错误');
            $this->ajaxReturn(['status' => 'no', 'msg' => '验证码错误,请重试']);
        }
    }

    public function verifyEmpty($verfiy_code)
    {
        if (!$verfiy_code) {
            $this->ajaxReturn(['status' => 'no', 'msg' => '请填写验证码']);
        }
    }

    public function returnCodeRes($verfiy_code, $code_type)
    {
        switch ($code_type) {
            case 1:
                if (!empty(session('child_info'))) {
                    $manage_pwd = ChilduserModel::getUserManagepwd(session('child_info.id'));
                } else {
                    $manage_pwd = UserpasswordModel::getManagePwd(session('user_info.id'));
                }
                $res = md5($verfiy_code) == $manage_pwd ? true : false;
                break;
            case 2:
                if (!empty(session('child_info'))) {
                    $manage_pwd = ChilduserModel::getUserManagepwd(session('child_info.id'));
                    $google = $this->veryGoogleCode('child', session('child_info.id'), $verfiy_code);
                } else {
                    $manage_pwd = UserpasswordModel::getManagePwd(session('user_info.id'));
                    $google = $this->veryGoogleCode('user', session('user_info.id'), $verfiy_code);
                }
                $res_pwd = md5($verfiy_code) == $manage_pwd ? true : false;
                $res_google = $google ? true : false;
                if ($res_pwd || $res_google) {
                    $res = true;
                } else {
                    $res = false;
                }
                break;
        }
        return $res;
    }

    public function veryGoogleCode($type, $user_id, $google_code)
    {
        $secret = GooglecodeModel::getSecret($type, $user_id);
        $res = GooglecodeModel::verifyCode($secret, $google_code);
        return $res;
    }

    public function addUserOperate($content)
    {
        if (!empty(session('child_info'))) {
            $child_id = session('child_info.id');
        } else {
            $child_id = 0;
        }
        $data = [
            'userid' => session('user_info.id'),
            'userip' => getIp(),
            'operatedatetime' => date('Y-m-d H:i:s'),
            'content' => $content,
            'child_id' => $child_id,
        ];
        M('useroperaterecord')->add($data);
    }

    //列表页面
    public function userInfo()
    {
        $this->display();
    }

    //用户谷歌验证页面
    public function userGoogle()
    {
        $google_code = $this->getUserGoogle();
        $this->assign('google_code', $google_code);
        $user_type = $this->verifyUsertype();
        $this->assign('user_type', $user_type);
        $this->display();
    }

    //登陆密码页面
    //2019-3-27 任梦龙：修改
    public function userLoginPwd()
    {
        $code_type = $this->getCodeType();
        $this->assign('code_type', $code_type);
        $this->display();
    }

    //修改登陆密码
    public function editLoginPwd()
    {
        $old_login_pwd = I('post.old_login_pwd', '', 'trim');
        if (!$old_login_pwd) {
            $this->ajaxReturn(['status' => 'no', 'msg' => '原始密码不得为空']);
        }
        $new_login_pwd = I('post.new_login_pwd', '', 'trim');
        $relogin_pwd = I('post.relogin_pwd', '', 'trim');
        if (!empty(session('child_info'))) {
            $login_pwd = ChilduserModel::getUserLoginpwd(session('child_info.id'));
        } else {
            $login_pwd = UserpasswordModel::getLoginPwd(session('user_info.id'));
        }

        if (md5($old_login_pwd) != $login_pwd) {
            $this->ajaxReturn(['status' => 'no', 'msg' => '原始密码输入错误']);
        }
        if (!$new_login_pwd) {
            $this->ajaxReturn(['status' => 'no', 'msg' => '新密码不得为空']);
        }
        if (md5($new_login_pwd) == $login_pwd) {
            $this->ajaxReturn(['status' => 'no', 'msg' => '原始密码与新密码一致,请重新输入']);
        }
        $rule_pwd = '/^[A-Za-z0-9]{6,20}$/';
        if (!preg_match($rule_pwd, $new_login_pwd)) {
            $this->ajaxReturn(['status' => 'no', 'msg' => '密码由大小写英文，数字组成，长度在6-20字符之间！']);
        }
        if ($new_login_pwd != $relogin_pwd) {
            $this->ajaxReturn(['status' => 'no', 'msg' => '确认密码不一致,请重新输入']);
        }
        //检测验证码的代码
        $msg = '修改登陆密码:';
        $verfiy_code = I('post.verfiy_code', '', 'trim');
        $code_type = I('post.code_type', 0, 'intval');
        $this->checkVerifyCode($verfiy_code, $code_type, $msg);
        if (!empty(session('child_info'))) {
            $res = ChilduserModel::getMenuJson(session('child_info.id'), ['child_pwd' => md5($new_login_pwd)]);
        } else {
            $res = UserpasswordModel::editUserpwd(session('user_info.id'), ['loginpassword' => md5($new_login_pwd)]);
        }
        if ($res) {
            $this->addUserOperate($msg . '修改成功');
            $this->ajaxReturn(['status' => 'ok', 'msg' => '登录密码修改成功']);
        }
        $this->addUserOperate($msg . '修改失败');
        $this->ajaxReturn(['status' => 'no', 'msg' => '登录密码修改失败']);
    }

    //管理密码页面
    //2019-3-27 任梦龙：修改
    public function userMangePwd()
    {
        $code_type = $this->getCodeType();
        $this->assign('code_type', $code_type);
        $this->display();
    }

    //修改管理密码
    public function editManagePwd()
    {
        $old_manage_pwd = I('post.old_manage_pwd', '', 'trim');
        if (!$old_manage_pwd) {
            $this->ajaxReturn(['status' => 'no', 'msg' => '原始密码不得为空']);
        }
        $new_manage_pwd = I('post.new_manage_pwd', '', 'trim');
        $remanage_pwd = I('post.remanage_pwd', '', 'trim');
        if (!empty(session('child_info'))) {
            $manage_pwd = ChilduserModel::getUserManagepwd(session('child_info.id'));
        } else {
            $manage_pwd = UserpasswordModel::getManagePwd(session('user_info.id'));
        }

        if (md5($old_manage_pwd) != $manage_pwd) {
            $this->ajaxReturn(['status' => 'no', 'msg' => '原始密码输入错误']);
        }
        if (!$new_manage_pwd) {
            $this->ajaxReturn(['status' => 'no', 'msg' => '新密码不得为空']);
        }
        if (md5($new_manage_pwd) == $manage_pwd) {
            $this->ajaxReturn(['status' => 'no', 'msg' => '原始密码与新密码一致,请重新输入']);
        }
        $rule_pwd = '/^[A-Za-z0-9]{6,20}$/';
        if (!preg_match($rule_pwd, $new_manage_pwd)) {
            $this->ajaxReturn(['status' => 'no', 'msg' => '密码由大小写英文，数字组成，长度在6-20字符之间！']);
        }
        if ($new_manage_pwd != $remanage_pwd) {
            $this->ajaxReturn(['status' => 'no', 'msg' => '确认密码不一致,请重新输入']);
        }
        //检测验证码的代码
        $msg = '修改管理密码:';
        $verfiy_code = I('post.verfiy_code', '', 'trim');
        $code_type = I('post.code_type', 0, 'intval');
        $this->checkVerifyCode($verfiy_code, $code_type, $msg);
        if (!empty(session('child_info'))) {
            $res = ChilduserModel::getMenuJson(session('child_info.id'), ['manage_pwd' => md5($new_manage_pwd)]);
        } else {
            $res = UserpasswordModel::editUserpwd(session('user_info.id'), ['manage_pwd' => md5($new_manage_pwd)]);
        }
        if ($res) {
            $this->addUserOperate($msg . '修改成功');
            $this->ajaxReturn(['status' => 'ok', 'msg' => '管理密码修改成功']);
        }
        $this->addUserOperate($msg . '修改失败');
        $this->ajaxReturn(['status' => 'no', 'msg' => '管理密码修改失败']);
    }

}