<?php

namespace User\Controller;

use User\Model\UserModel;
use User\Model\UserinfoModel;
use User\Model\ChilduserModel;
use User\Model\UserpasswordModel;

class UserInfoController extends UserCommonController
{
    public function __construct()
    {
        parent::__construct();
    }

    //账号信息总页面
    public function userInfo()
    {
        session('switch_code', 1);
        $this->display();
    }

    //用户信息页面
    public function userInformation()
    {
        $userInfo = UserModel::getUserInfo(session('user_info.id'));
        $this->assign('userInfo', $userInfo);
        //查询用户是否设置公司logo(收银台设置)
        $userLogo = M('cashier')->where('user_id=' . session('user_info.id'))->getField('logo');
        if ($userLogo && file_get_contents($userLogo)) {
            $this->assign('userLogo', $userLogo);
        }
        $this->display();
    }

    //基本信息页面
    //2019-4-15 rml：修改
    public function basicInformation()
    {
        $userinfo = UserinfoModel::getUserinfo(session('user_info.id'));
        if ($userinfo) {
            $this->assign('userinfo', $userinfo);
        }
        $this->assign('userid', session('user_info.id'));
        $code_type = $this->getCodeType();
        $this->assign('code_type', $code_type);
        $this->display();
    }

    //基本信息修改
    public function basicInformationEdit()
    {
        $msg = '修改用户[' . session('user_info.username') . ']的基本信息:';
        //检测验证码的代码
        $verfiy_code = I('post.verfiy_code', '', 'trim');
        $code_type = I('post.code_type', 0, 'intval');
        $this->checkVerifyCode($verfiy_code, $code_type, $msg);
        //2019-4-17 rml：利用是否存在id来判断是增加还是修改,这样避免操作数据库
        $id = I('post.id', 0, 'intval');
        if ($id) {
            $return = AddSave('userinfo', 'save', '修改基本信息');
        } else {
            $return = AddSave('userinfo', 'add', '修改基本信息');
        }
        $this->addUserOperate($msg . $return['msg']);
        $this->ajaxReturn($return, 'json');
    }

    //密码管理页面
    public function userPassword()
    {
        $this->display();
    }

    //修改登录密码页面
    public function userLoginPassword()
    {
        $code_type = $this->getCodeType();
        $this->assign('code_type', $code_type);
        $this->display();
    }

    //修改登录密码处理程序
    //2019-4-17 rml:修改
    public function editLoginPassword()
    {
        $msg = '修改登陆密码:';
        $old_login_pwd = I('post.old_login_pwd', '', 'trim');
        if (!$old_login_pwd) {
            $this->addUserOperate($msg . '原始密码为空');
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
            $this->addUserOperate($msg . '原始密码输入错误');
            $this->ajaxReturn(['status' => 'no', 'msg' => '原始密码输入错误']);
        }
        if (!$new_login_pwd) {
            $this->addUserOperate($msg . '新密码为空');
            $this->ajaxReturn(['status' => 'no', 'msg' => '新密码不得为空']);
        }
        if (md5($new_login_pwd) == $login_pwd) {
            $this->addUserOperate($msg . '原始密码与新密码一致');
            $this->ajaxReturn(['status' => 'no', 'msg' => '原始密码与新密码一致,请重新输入']);
        }
        $rule_pwd = '/^[A-Za-z0-9]{6,20}$/';
        if (!preg_match($rule_pwd, $new_login_pwd)) {
            $this->addUserOperate($msg . '登录密码由大小写英文，数字组成，长度在6-20字符之间');
            $this->ajaxReturn(['status' => 'no', 'msg' => '登录密码由大小写英文，数字组成，长度在6-20字符之间']);
        }
        if ($new_login_pwd != $relogin_pwd) {
            $this->addUserOperate($msg . '确认密码不一致');
            $this->ajaxReturn(['status' => 'no', 'msg' => '确认密码不一致,请重新输入']);
        }
        //检测验证码的代码
        $verfiy_code = I('post.verfiy_code', '', 'trim');
        $code_type = I('post.code_type', 0, 'intval');
        $this->checkVerifyCode($verfiy_code, $code_type, $msg);
        if (!empty(session('child_info'))) {
//            $res = ChilduserModel::editUserPwd(session('child_info.id'), 'child_pwd', md5($new_login_pwd));
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

    //修改管理密码页面
    public function userManagePwd()
    {
        $code_type = $this->getCodeType();
        $this->assign('code_type', $code_type);
        $this->display();
    }

    //修改管理密码处理程序
    //2019-4-17 rml:修改
    public function editManagePwd()
    {
        $msg = '修改管理密码:';
        $old_manage_pwd = I('post.old_manage_pwd', '', 'trim');
        if (!$old_manage_pwd) {
            $this->addUserOperate($msg . '原始密码为空');
            $this->ajaxReturn(['status' => 'no', 'msg' => '原始密码不得为空']);
        }
        $new_manage_pwd = I('post.new_manage_pwd', '', 'trim');
        $remanage_pwd = I('post.remanage_pwd', '', 'trim');
        if (!empty(session('child_info'))) {
//            $manage_pwd = session('child_info.manage_pwd');
            $manage_pwd = ChilduserModel::getUserManagepwd(session('child_info.id'));
        } else {
            $manage_pwd = UserpasswordModel::getManagePwd(session('user_info.id'));
        }

        if (md5($old_manage_pwd) != $manage_pwd) {
            $this->addUserOperate($msg . '原始密码输入错误');
            $this->ajaxReturn(['status' => 'no', 'msg' => '原始密码输入错误']);
        }
        if (!$new_manage_pwd) {
            $this->addUserOperate($msg . '新密码为空');
            $this->ajaxReturn(['status' => 'no', 'msg' => '新密码不得为空']);
        }
        if (md5($new_manage_pwd) == $manage_pwd) {
            $this->addUserOperate($msg . '原始密码与新密码一致');
            $this->ajaxReturn(['status' => 'no', 'msg' => '原始密码与新密码一致,请重新输入']);
        }
        $rule_pwd = '/^[A-Za-z0-9]{6,20}$/';
        if (!preg_match($rule_pwd, $new_manage_pwd)) {
            $this->addUserOperate($msg . '密码由大小写英文，数字组成，长度在6-20字符之间');
            $this->ajaxReturn(['status' => 'no', 'msg' => '管理密码由大小写英文，数字组成，长度在6-20字符之间']);
        }
        if ($new_manage_pwd != $remanage_pwd) {
            $this->addUserOperate($msg . '确认密码不一致');
            $this->ajaxReturn(['status' => 'no', 'msg' => '确认密码不一致,请重新输入']);
        }
        //检测验证码的代码
        $verfiy_code = I('post.verfiy_code', '', 'trim');
        $code_type = I('post.code_type', 0, 'intval');
        $this->checkVerifyCode($verfiy_code, $code_type, $msg);
        if (!empty(session('child_info'))) {
//            $res = ChilduserModel::editUserPwd(session('child_info.id'), 'manage_pwd', md5($new_manage_pwd));
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


    //登录记录列表
    public function loginRecordList()
    {
        $child_list = ChilduserModel::getChildUserlist(session('user_info.id'));
        $this->assign('child_list', $child_list);
        $this->display();
    }

    //加载登录记录
    public function loadLoginRecordList()
    {
        $where = [];
        //如果是主用户，则查询他自己和他底下所有子账号的记录，如果为子账号登录，则只查询该子账号自身的记录
        if (!empty(session('child_info'))) {
            $where[0] = 'userid=' . session('user_info.id');
            $where[1] = 'child_id=' . session('child_info.id');
        } else {
            $child_id = I('post.child_id', 0);  //子账号id
            $where[0] = 'userid=' . session('user_info.id');  //默认是主用户登录
            if ($child_id) {
                $where[1] = 'child_id=' . $child_id;
            }
        }
        $i = 2;
        $start = I("post.start", "");
        $end = I("post.end", "");
        if ($start <> "") {
            $where[$i] = "DATEDIFF('" . $start . "',logindatetime) <= 0";
            $i++;
        }
        if ($end <> "") {
            $where[$i] = "DATEDIFF('" . $end . "',logindatetime) >= 0";
            $i++;
        }
        $loginip = I("post.loginip", "", 'trim');
        if ($loginip <> "") {
            $where[$i] = "(loginip='" . $loginip . "')";
            $i++;
        }
        $count = D('userloginrecord')->where($where)->count();
        $datalist = D('userloginrecord')->field('loginip,logindatetime,loginaddress,child_id')
            ->where($where)->page(I("post.page", "1"), I("post.limit", "10"))
            ->order('id DESC')->select();
        //账号名称：
        foreach ($datalist as $key => $val) {
            $datalist[$key]['user_name'] = session('user_info.username');  //主用户
            if ($val['child_id']) {
                $datalist[$key]['child_name'] = ChilduserModel::getChildName($val['child_id']);
            } else {
                $datalist[$key]['child_name'] = '-';
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

    //操作记录列表
    public function operateRecordList()
    {
        $child_list = ChilduserModel::getChildUserlist(session('user_info.id'));
        $this->assign('child_list', $child_list);
        $this->display();
    }

    //加载操作记录
    public function loadOperateRecordList()
    {
        $where = [];
        //如果是主用户，则查询他自己和他底下所有子账号的记录，如果为子账号登录，则只查询该子账号自身的记录
        if (!empty(session('child_info'))) {
            $where[0] = 'userid=' . session('user_info.id');
            $where[1] = 'child_id=' . session('child_info.id');
        } else {
            $child_id = I('post.child_id', 0);  //子账号id
            $where[0] = 'userid=' . session('user_info.id');  //默认是主用户登录
            if ($child_id) {
                $where[1] = 'child_id=' . $child_id;
            }
        }
        $i = 2;
        $start = I("post.start", "");
        $end = I("post.end", "");
        if ($start <> "") {
            $where[$i] = "DATEDIFF('" . $start . "',operatedatetime) <= 0";
            $i++;
        }
        if ($end <> "") {
            $where[$i] = "DATEDIFF('" . $end . "',operatedatetime) >= 0";
            $i++;
        }
        $userip = I("post.userip", "", 'trim');
        if ($userip <> "") {
            $where[$i] = "(userip= '" . $userip . "')";
            $i++;
        }
        $count = D('useroperaterecord')->where($where)->count();
        $datalist = D('useroperaterecord')->field('userip,operatedatetime,content,child_id')
            ->where($where)->page(I("post.page", "1"), I("post.limit", "10"))
            ->order('id DESC')->select();
        //账号名称：
        foreach ($datalist as $key => $val) {
            $datalist[$key]['user_name'] = session('user_info.username');  //主用户
            if ($val['child_id']) {
                $datalist[$key]['child_name'] = ChilduserModel::getChildName($val['child_id']);
            } else {
                $datalist[$key]['child_name'] = '-';
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

}