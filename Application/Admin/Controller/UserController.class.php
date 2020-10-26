<?php

namespace Admin\Controller;

use Admin\Model\SecretkeyModel;
use Admin\Model\UserinfoModel;
use Admin\Model\UserModel;
use Admin\Model\GooglecodeModel;
use Admin\Model\VersionModel;
use Admin\Model\UsertypeModel;
use Admin\Model\UserstatusModel;
use Admin\Model\UserrengzhengModel;
use Admin\Model\UserpasswordModel;
use Admin\Model\SettleversionModel;
use Admin\Model\PayapiModel;
use Admin\Model\PayapiaccountModel;

class UserController extends CommonController
{
    //用户表
    public function UserList()
    {
        $userstatuslist = UserstatusModel::selectUserStatus();
        $this->assign("userstatuslist", $userstatuslist);

        $usertypelist = UsertypeModel::selectUserType();
        $this->assign("usertypelist", $usertypelist);

        $userrengzhenglist = UserrengzhengModel::selectRengZheng();
        $this->assign("userrengzhenglist", $userrengzhenglist);
        $this->display();
    }

    //加载用户列表
    public function LoadUserList()
    {
        $where = [];
        $i = 0;
        $where[$i] = "del=0";
        $i++;
        //查找会员的下级
        $superiorid = I("post.superiorid", "");
        if ($superiorid <> "") {
            $where[$i] = "superiorid = " . $superiorid;
            $i++;
        }

        //查找会员的上级
        $username = I("post.username", "", 'trim');
        if ($username <> "") {
            $where[$i] = "(username like '%" . $username . "%' or getmemberid(id) like '%" . $username . "%')";
            $i++;
        }

        //用户别名
        $bieming = I("post.bieming", "", 'trim');
        if ($bieming <> "") {
            $where[$i] = "(bieming like '%" . $bieming . "%')";
            $i++;
        }

        $status = I("post.status", "");
        if ($status <> "") {
            $where[$i] = "`status` = " . $status;
            $i++;
        }

        $userrengzheng = I("post.userrengzheng", "");
        if ($userrengzheng <> "") {
            $where[$i] = "authentication = " . $userrengzheng;
            $i++;
        }

        $usertype = I("post.usertype", "");
        if ($usertype <> "") {
            $where[$i] = "usertype = " . $usertype;
            $i++;
        }


        $start = I("post.start", "");
        $end = I("post.end", "");
        if ($start <> "") {
            $where[$i] = "DATEDIFF('" . $start . "',regdatetime) <= 0";
            $i++;
        }

        if ($end <> "") {
            $where[$i] = "DATEDIFF('" . $end . "',regdatetime) >= 0";
            $i++;
        }

        $ReturnArr = [
            'code' => 0,
            'msg' => '数据加载成功', //响应结果
            'count' => 0, //总页数
            'data' => [
            ]
        ];

        if (I('order_type') > 0) {
            //关联查询
            $order_type = I('order_type');
            $order_id = I('order_id');
            if ($order_type == 1) {
                //查询充值订单
                $ids = M('order')->where('userordernumber="' . $order_id . '" or sysordernumber="' . $order_id . '"')->field('userid')->select();
            } else {
                //查询结算订单
                $ids = M('settle')->where('ordernumber="' . $order_id . '"')->field('userid')->select();
            }
            $temp = [];
            foreach ($ids as $k => $v) {
                $temp[] = $v['userid'];
            }
            $temp = array_unique($temp);
            $str = implode($temp, ',');
            if ($str) {
                $where[$i] = "(id in (" . $str . "))";
            } else {
                $where[$i] = "(id=0)";
            }
            //总页数
            $count = D('user')->where($where)->count();
            //分页页面展示设置,得到数据库里的数据（del=0）二维数组
            $datalist = D('user')->scope('default_scope')->where($where)->page(I("post.page", "1"), I("post.limit", "10"))->order('status,id DESC')->select();
        } else {
            //普通查询
            //总页数
            $count = D('user')->where($where)->count();
            //分页页面展示设置,得到数据库里的数据（del=0）二维数组
            $datalist = D('user')->scope('default_scope')->where($where)->page(I("post.page", "1"), I("post.limit", "10"))->order('status,id DESC')->select();
        }

        //通过user_id查询对应的用户名字
        foreach ($datalist as $key => $val) {
            //查询用户是否开通谷歌验证
            $google = GooglecodeModel::getInfo('user', $val['id']);
            if ($google) {
                $datalist[$key]['google'] = $google['status'];
            } else {
                $datalist[$key]['google'] = 2;
            }
        }
        $ReturnArr['count'] = $count;
        $ReturnArr['data'] = $datalist;
        $this->ajaxReturn($ReturnArr, 'JSON');

    }

    //添加用户页面
    public function UserAdd()
    {
        $types = UsertypeModel::selectUserType();
        $status = UserstatusModel::selectUserStatus();
//        $rengzheng = UserrengzhengModel::selectRengZheng();
        $users = UserModel::selectUser(['del' => 0]);
        $loginpassword = C('DEFAULT_LOGINPASSWORD');
        $paypassword = C('DEFAULT_PAYPASSWORD');
        $this->assign('types', $types);
        $this->assign('status', $status);
//        $this->assign('rengzheng', $rengzheng);
        $this->assign('users', $users);
        $this->assign('loginpassword', $loginpassword);
        $this->assign('paypassword', $paypassword);
        $this->display();
    }

    //确认添加用户
    public function createdUser()
    {
        $msg = '添加用户:' . I('post.username', '', 'trim');
        $user = D('user');
        if (!$user->create()) {
            $this->addAdminOperate($msg . ',' . $user->getError() . ',还未产生任何记录');
            $this->ajaxReturn(['status' => 'no', 'msg' => $user->getError()]);
        }
        //添加用户表
        $res_user = $user->add();
        if (!$res_user) {
            $this->addAdminOperate($msg . ',添加失败,还未产生任何记录');
            $this->ajaxReturn(['status' => 'no', 'msg' => '用户添加失败']);
        }
        $userpwd = D('userpassword');
        //添加用户密码表
        $data_pwd = [
            'loginpassword' => I('post.loginpassword', '', 'trim'),
            'manage_pwd' => I('post.manage_pwd', '', 'trim'),
            'userid' => $res_user
        ];
        if (!$userpwd->create($data_pwd)) {
            UserModel::delUser($res_user);
            $this->addAdminOperate($msg . ',添加失败,' . $userpwd->getError() . ',此时产生用户记录');
            $this->ajaxReturn(['status' => 'no', 'msg' => $userpwd->getError()]);
        }
        $res_pwd = $userpwd->add();
        if (!$res_pwd) {
            UserModel::delUser($res_user);
            $this->addAdminOperate($msg . ',添加失败,' . $userpwd->getError() . ',此时产生用户记录');
            $this->ajaxReturn(['status' => 'no', 'msg' => '用户添加失败']);
        }
        //添加用户密钥表
        $secret = D('secretkey');
        $data1 = [
            'userid' => $res_user
        ];
        if (!$secret->create($data1)) {
            UserModel::delUser($res_user);
            UserpasswordModel::delUserpwd($res_user);
            $this->addAdminOperate($msg . ',' . $secret->getError() . ',此时产生用户记录和用户密钥记录');
            $this->ajaxReturn(['status' => 'no', 'msg' => $secret->getError()]);
        }
        $res_secret = $secret->add();
        if (!$res_secret) {
            UserModel::delUser($res_user);
            UserpasswordModel::delUserpwd($res_user);
            $this->addAdminOperate($msg . ',' . $secret->getError() . ',此时产生用户密码和用户密钥记录');
            $this->ajaxReturn(['status' => 'no', 'msg' => '用户添加失败']);
        }
        $res_usermoney = M('usermoney')->add([
            'userid' => $res_user
        ]);
        if (!$res_usermoney) {
            UserModel::delUser($res_user);
            UserpasswordModel::delUserpwd($res_user);
            M('secretkey')->where([
                'userid' => $res_user
            ])->delete();
            $this->addAdminOperate($msg . ',' . $secret->getError() . ',此时产生用户密码,用户密钥记录');
            $this->ajaxReturn(['status' => 'no', 'msg' => '用户添加失败']);
        }
        $this->addAdminOperate($msg . ',添加成功,' . '此时产生用户密码和用户密钥记录');
        $this->ajaxReturn(['status' => 'ok', 'msg' => '用户添加成功']);

    }

    //用户修改页面
    public function UserEdit()
    {
        session('code_switch', 1);
        $userid = I('userid');
        $this->assign('userid', $userid);
        $this->display();
    }

    //用户信息页面
    public function UserInformation()
    {
        $userid = I('userid');
        $types = UsertypeModel::selectUserType();
        $status = UserstatusModel::selectUserStatus();
        //2019-5-7 rml:既然能够修改，说明已被激活,改变状态值时就不需要未激活了
        unset($status[0]);
        $users = UserModel::selectUser(['del' => 0]);
        $user = UserModel::getInfo($userid);
        $user['userrengzhengname'] = UserrengzhengModel::getRenzhenName($user['authentication']);
        $this->assign('types', $types);
        $this->assign('status', $status);
        $this->assign('users', $users);
        $this->assign('user', $user);
        $code_type = $this->getCodeType();
        $this->assign('code_type', $code_type);
        $this->display();
    }

    //修改用户信息
    public function UserUpdate()
    {
        $id = I('post.id');
        $user_name = UserModel::getUserName($id);
        $msg = '修改用户[' . $user_name . ']的用户信息:';
        //检测验证码的代码
        $verfiy_code = I('post.verfiy_code', '', 'trim');
        $code_type = I('post.code_type', 0, 'intval');
        $this->checkVerifyCode($verfiy_code, $code_type, $msg);
        $return = AddSave('user', 'save', '修改用户信息');
        $this->addAdminOperate($msg . $return['msg']);
        $this->ajaxReturn($return, "json");
    }

    //用户的基本信息页面
    public function EssentialInformation()
    {
        $userinfo = UserinfoModel::getUserinfo(I('userid'));
        $this->assign('userinfo', $userinfo);
        $this->assign('userid', I('userid'));
        $code_type = $this->getCodeType();
        $this->assign('code_type', $code_type);
        $this->display();
    }

    //修改用户的基本信息
    public function basicInformationEdit()
    {
        $user_id = I('post.userid');  //用户id
        $user_name = UserModel::getUserName($user_id);
        $msg = '修改用户[' . $user_name . ']的用户信息:';
        //检测验证码的代码
        $verfiy_code = I('post.verfiy_code', '', 'trim');  //修改数据时的验证码
        $code_type = I('post.code_type', 0, 'intval');  //验证码类型
        $this->checkVerifyCode($verfiy_code, $code_type, $msg);   //验证密码
        if (UserinfoModel::getUserinfo($user_id)) {
            $return = AddSave('userinfo', 'save', '修改基本信息');
        } else {
            $return = AddSave('userinfo', 'add', '修改基本信息');
        }
        $this->addAdminOperate($msg . $return['msg']);
        $this->ajaxReturn($return, "json");
    }

    //认证信息页面
    public function verifyInformation()
    {
        $user_id = I('get.userid');
        $verify = M('userauthimgs')->where('user_id=' . $user_id)->find();
        if (!$verify) {
            $verify['authentication'] = 1;
        }
        $this->assign('verify', $verify);
        $this->assign('user_id', $user_id);
        $this->display();
    }

    //审核认证信息程序
    public function applyAuth()
    {
        $user_id = I("post.user_id");
        $type = I("post.type");
        $user_name = UserModel::getUserName($user_id);
        $msg = '修改用户[' . $user_name . ']的认证信息:';
        $Userverifyinfo = M("userauthimgs");
        $data["authentication"] = $type;
        $res = $Userverifyinfo->where("user_id=" . $user_id)->save($data);
        if ($res) {
            if ($type == 4) {
                //审核未通过,改成未认证状态
                $data["authentication"] = 1;
            }
            M('user')->where("id=" . $user_id)->save($data);
            $this->addAdminOperate($msg, '修改成功');
            $this->ajaxReturn(['status' => 'ok', 'msg' => '修改成功']);
        }
        $this->addAdminOperate($msg, '修改失败');
        $this->ajaxReturn(['status' => 'no', 'msg' => '修改失败']);
    }

    //谷歌验证的页面
    public function googleInformation()
    {
        $user_id = I('get.userid', 0);
        if (!$user_id) {
            $this->ajaxReturn(['status' => 'no', 'msg' => '请勿非法操作']);
        }
        $code_type = $this->getCodeType();
        $this->assign('code_type', $code_type);
        $this->display();
    }

    //谷歌验证的处理程序
    public function editGoogle()
    {
        $verfiy_code = I('post.verfiy_code', '', 'trim');
        $code_type = I('post.code_type', 0, 'intval');
        $user_id = I('post.user_id');
        $switch = I('post.switch');
        $google = I('post.google');
        $user_name = UserModel::getUserName($user_id);
        if ($switch == 1) {
            if ($google != 2) {
                $this->ajaxReturn(['status' => 'no', 'msg' => '已是开通状态,请确认']);
            }
            $msg = '修改用户[' . $user_name . ']的谷歌验证状态:修改为开通,';
            $this->checkVerifyCode($verfiy_code, $code_type, $msg);
            $res = GooglecodeModel::addInfo('user', $user_id);
        } else {
            if ($google == 2) {
                $this->ajaxReturn(['status' => 'no', 'msg' => '已是关闭状态,请确认']);
            }
            $msg = '修改用户[' . $user_name . ']的谷歌验证状态:修改为关闭,';
            $this->checkVerifyCode($verfiy_code, $code_type, $msg);
            $res = GooglecodeModel::delInfo('user', $user_id);
        }
        if ($res) {
            $this->addAdminOperate($msg . '修改成功');
            $this->ajaxReturn(['status' => 'ok', 'msg' => '修改成功']);
        }
        $this->addAdminOperate($msg . '修改失败');
        $this->ajaxReturn(['status' => 'no', 'msg' => '修改失败']);
    }

    //修改用户状态
    public function UserStatus()
    {
        $id = I("post.id", "");
        $status = I("post.status", "");
        $user_name = UserModel::getUserName($id);
        if ($status == 2) {
            $msg = '修改用户[' . $user_name . ']的状态：修改为正常,';
        } else {
            $msg = '修改用户[' . $user_name . ']的状态：修改为禁用,';
        }
        $r = UserModel::editUserInfo($id, ['status' => $status]);
        if ($r) {
            $this->addAdminOperate($msg . '修改成功');
            $this->ajaxReturn(['status' => 'ok', 'msg' => '修改成功']);
        } else {
            $this->addAdminOperate($msg . '修改失败');
            $this->ajaxReturn(['status' => 'no', 'msg' => '修改失败']);
        }
    }

    //修改用户交易状态
    public function UserOrder()
    {
        $id = I("post.id", "");
        $order = I("post.order", "");
        $r = M("user")->where("id=" . $id)->setField("order", $order);
        if ($r) {
            $this->ajaxReturn(['status' => 'ok', 'msg' => '修改成功']);
        } else {
            $this->ajaxReturn(['status' => 'no', 'msg' => '修改失败']);
        }
    }

    //修改用户的测试状态
    public function userTestStatus()
    {
        $id = I("post.id", "");
        $test_status = I("post.test_status", "");
        $r = M("user")->where("id=" . $id)->setField("test_status", $test_status);
        if ($r) {
            $this->ajaxReturn(['status' => 'ok', 'msg' => '修改成功']);
        } else {
            $this->ajaxReturn(['status' => 'no', 'msg' => '修改失败']);
        }
    }

    //生成商户号页面
    public function showmemberid()
    {
        $userid = I("get.userid");
        $this->assign('userid', $userid);
        $this->display();
    }

    //创建商户会员号
    public function CreateMemberid()
    {
        $userid = I('post.userid', '');
        $createtype = I('post.createtype', '');
        if ($userid == '' || $createtype == '') {
            $this->ajaxReturn(['msg' => '请选择商户号生成规律!', 'status' => 'no'], 'json');
        }
        $user_name = UserModel::getUserName($userid);
        $msg = '为用户[' . $user_name . ']生成商户号:';
        $res = SecretkeyModel::createdMemberid($userid, $createtype);
        if ($res) {
            $this->addAdminOperate($msg . '生成成功');
            $this->ajaxReturn(['msg' => '商户号生成成功!', 'status' => 'ok'], 'json');
        } else {
            $this->addAdminOperate($msg . '生成失败');
            $this->ajaxReturn(['msg' => '商户号生成失败!', 'status' => 'no'], 'json');
        }
    }

    //修改密码页面
    public function editUserPwd()
    {
        $this->display();
    }

    //修改用户登录密码页面
    public function editLoginPwd()
    {
        $userid = I('userid');
        $this->assign('userid', $userid);
        $code_type = $this->getCodeType();
        $this->assign('code_type', $code_type);
        $this->display();
    }

    //修改登录密码处理程序,添加操作记录，验证码
    public function loginPwdEdit()
    {
        $new_pwd = I('post.password', '', 'trim');
        $repassword = I('post.repassword', '', 'trim');
        $user_id = I('post.userid', '', 'trim');
        $user_name = UserModel::getUserName($user_id);
        $user_pwd = UserpasswordModel::getUserPassword($user_id);
        //检测验证码的代码
        $msg = '为用户[' . $user_name . ']修改登录密码:';
        $verfiy_code = I('post.verfiy_code', '', 'trim');  //修改数据时的验证码
        $code_type = I('post.code_type', 0, 'intval');  //验证码类型
        if (!$new_pwd) {
            $this->ajaxReturn(['status' => 'no', 'msg' => '请输入新密码']);
        }
        if (md5($new_pwd) == $user_pwd['loginpassword']) {
            $this->ajaxReturn(['status' => 'no', 'msg' => '新密码与原始密码一致,请确认']);
        }
        //6-20 位字符与数字  2019-4-22 rml:修改
        $rule_pwd = '/^[A-Za-z0-9]{6,20}$/';
        if (!preg_match($rule_pwd, $new_pwd)) {
            $this->ajaxReturn(['status' => 'no', 'msg' => '密码由大小写英文，数字组成，长度在6-20字符之间！']);
        }
        if ($new_pwd != $repassword) {
            $this->ajaxReturn(['status' => 'no', 'msg' => '确认密码不一致,请重新输入']);
        }
        $this->checkVerifyCode($verfiy_code, $code_type, $msg);
        $res = UserpasswordModel::saveLoginPwd($user_id, ['loginpassword' => md5($new_pwd)]);
        if ($res) {
            $this->addAdminOperate($msg . '密码修改成功');
            $this->ajaxReturn(['status' => 'ok', 'msg' => '密码修改成功']);
        }
        $this->addAdminOperate($msg . '密码修改失败');
        $this->ajaxReturn(['status' => 'no', 'msg' => '密码修改失败']);
    }

    //修改用户管理密码页面
    public function editManagePwd()
    {
        $userid = I('userid');
        $this->assign('userid', $userid);
        //2019-3-14 任梦龙：判断当前操作者的谷歌验证与管理密码的开启状态
        $code_type = $this->getCodeType();
        $this->assign('code_type', $code_type);
        $this->display();
    }

    //修改管理密码处理程序,添加操作记录，验证码
    public function managePwdEdit()
    {
        $new_pwd = I('post.manage_pwd', '', 'trim');
        $repassword = I('post.repassword', '', 'trim');
        $user_id = I('post.userid', '', 'trim');
        $user_name = UserModel::getUserName($user_id);
        $user_pwd = UserpasswordModel::getUserPassword($user_id);
        //检测验证码的代码
        $msg = '为用户[' . $user_name . ']修改管理密码:';
        $verfiy_code = I('post.verfiy_code', '', 'trim');  //修改数据时的验证码
        $code_type = I('post.code_type', 0, 'intval');  //验证码类型
        if (!$new_pwd) {
            $this->ajaxReturn(['status' => 'no', 'msg' => '请输入新密码']);
        }
        if (md5($new_pwd) == $user_pwd['manage_pwd']) {
            $this->ajaxReturn(['status' => 'no', 'msg' => '新密码与原始密码一致,请确认']);
        }
        //2019-4-22 rml:修改密码规则
        $rule_pwd = '/^[A-Za-z0-9]{6,20}$/';
        if (!preg_match($rule_pwd, $new_pwd)) {
            $this->ajaxReturn(['status' => 'no', 'msg' => '密码由大小写英文，数字组成，长度在6-20字符之间！']);
        }
        if ($new_pwd != $repassword) {
            $this->ajaxReturn(['status' => 'no', 'msg' => '确认密码不一致,请重新输入']);
        }
        $this->checkVerifyCode($verfiy_code, $code_type, $msg);   //验证密码
        $res = UserpasswordModel::saveLoginPwd($user_id, ['manage_pwd' => md5($new_pwd)]);
        if ($res) {
            $this->addAdminOperate($msg . '密码修改成功');
            $this->ajaxReturn(['status' => 'ok', 'msg' => '密码修改成功']);
        }
        $this->addAdminOperate($msg . '密码修改失败');
        $this->ajaxReturn(['status' => 'no', 'msg' => '密码修改失败']);
    }

    //展示银行卡相关信息的页面
    public function showBankCard()
    {
        $userid = I('userid');
        $bankcard = UserModel::getBackCard($userid);
        $this->assign('bankcard', $bankcard);
        $this->display();
    }

    //展示用户的认证照片
    public function showAuthpicture()
    {
        $userid = I('userid');
        $authpicture = UserModel::getAuthPicture($userid);
        $this->assign('authpicture', $authpicture);
        $this->display();
    }

    public function showUserInfo()
    {
        $userid = I('userid');
        $user = UserModel::getInfo($userid);
        $this->assign('user', $user);
        $this->display();
    }


    //修改删除的输出方式为json
    //2019-4-9 任梦龙：修改
    public function userDel()
    {
        $id = I("post.id", 0, 'intval');
        $find = M('user')->where('id=' . $id)->find();
        $msg = '删除用户' . $find['username'] . ':';
        if (!$id) {
            $this->ajaxReturn(['status' => 'no', 'msg' => '请勿非法操作']);
        }
        $res_user = M('user')->where("id='" . $id . "'")->setField('del', 1);  //删除用户自己的记录
        if ($res_user) {
            $this->addAdminOperate($msg . '删除成功');
            $this->ajaxReturn(['status' => 'ok', 'msg' => '删除成功']);
        } else {
            $this->addAdminOperate($msg . '删除失败');
            $this->ajaxReturn(['status' => 'no', 'msg' => '删除失败']);
        }
    }

    public function showsxf()
    {
        $userid = I('userid');
        $this->assign('userid', $userid);
        $this->display();
    }

    //交易记录列表
    public function userOrderList()
    {
        $userid = I('userid');
        $this->assign('userid', $userid);

        $statustype = C('STATUSTYPE');
        $this->assign('statustype', $statustype);

        $shangjias = M('payapishangjia')->select();
        $this->assign('shangjias', $shangjias);

        $payapis = M('payapi')->select();
        $this->assign('payapis', $payapis);

        $accounts = M('payapiaccount')->select();
        $this->assign('accounts', $accounts);

        $this->display();
    }

    //加载交易记录数据
    public function loadUserOrderList()
    {
        //搜索
        $where = [];
        $i = 1;
        $where[$i] = "userid = " . I("get.userid", "");
        $sysordernumber = I("post.sysordernumber", "");
        if ($sysordernumber <> "") {
            $where[$i] = "(sysordernumber like '%" . $sysordernumber . "%')";
            $i++;
        }
        $shangjiaid = I("post.shangjiaid", "");
        if ($shangjiaid <> "") {
            $where[$i] = "shangjiaid = " . $shangjiaid;
            $i++;
        }
        $payapiid = I("post.payapiid", "");
        if ($payapiid <> "") {
            $where[$i] = "payapiid = " . $payapiid;
            $i++;
        }
        $payapiaccountid = I("post.accountid", "");
        if ($payapiaccountid <> "") {
            $where[$i] = "payapiaccountid = " . $payapiaccountid;
            $i++;
        }
        $status = I("post.status", "");
        if ($status <> "") {
            $where[$i] = "status = " . $status;
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
        $count = M('orderinfo')->where($where)->count();
        $page = I('post.page');
        $limit = I('post.limit');
        $datalist = M('orderinfo')->where($where)->page($page, $limit)->select();

        //查询数据
        $statustype = C('STATUSTYPE');
        foreach ($datalist as $k => $v) {
            $datalist[$k]['shangjianame'] = $this->getNameById('payapishangjia', $v['shangjiaid'], 'shangjianame');
            $datalist[$k]['payapiname'] = $this->getNameById('payapi', $v['payapiid'], 'zh_payname');
            $datalist[$k]['accountname'] = $this->getNameById('payapiaccount', $v['payapiaccountid'], 'bieming');
            $datalist[$k]['status'] = $statustype[$v['status']];
        }

        $ReturnArr['count'] = $count;
        $ReturnArr['data'] = $datalist;
        $this->ajaxReturn($ReturnArr, 'json');
    }

    //通过id获取名称
    public function getNameById($table, $where, $field)
    {
        return M($table)->where('id=' . $where)->getField($field);
    }

    //交易记录操作：查看
    public function seeOrderInfo()
    {
        $order = M('order')->where('id=' . I('orderid'))->find();
        $order_info = M('orderinfo')->where('orderid=' . I('orderid'))->find();
        $statustype = C('STATUSTYPE');
        $order_info['username'] = $this->getNameById('user', $order_info['userid'], 'username');
        $order_info['shangjianame'] = $this->getNameById('payapishangjia', $order_info['shangjiaid'], 'shangjianame');
        $order_info['payapiname'] = $this->getNameById('payapi', $order_info['payapiid'], 'zh_payname');
        $order_info['accountname'] = $this->getNameById('payapiaccount', $order_info['payapiaccountid'], 'bieming');
        $order_info['status'] = $statustype[$order_info['status']];
        $order_info['callbackurl'] = $order['callbackurl'];
        $order_info['notifyurl'] = $order['notifyurl'];
        $this->assign('order_info', $order_info);
        $this->display();
    }

    //登录记录页面
    public function loginRecordList()
    {
        $userid = I('get.userid');
        $this->assign('userid', $userid);
        //查询该主用户下所有还未删除的子账号
        $child_list = M('childuser')->field('id,child_name,bieming')
            ->where(['user_id' => $userid, 'del' => 0])->select();
        $this->assign('child_list', $child_list);
        $this->display();
    }

    //加载登录记录列表
    public function loadLoginRecordList()
    {
        $where = [];
        $userid = I("get.userid", "");
        $user_name = UserModel::getUserName($userid);
        $where[0] = "userid = " . $userid;
        $i = 1;
        $child_id = I('post.child_id', 0);  //子账号id
        if ($child_id) {
            $where[$i] = 'child_id=' . $child_id;
            $i++;
        }
        $start = I("post.start", "");
        if ($start <> "") {
            $where[$i] = "DATEDIFF('" . $start . "',logindatetime) <= 0";
            $i++;
        }
        $end = I("post.end", "");
        if ($end <> "") {
            $where[$i] = "DATEDIFF('" . $end . "',logindatetime) >= 0";
            $i++;
        }
        $loginip = I("post.loginip", "", 'trim');
        if ($loginip <> "") {
            $where[$i] = "(loginip = '" . $loginip . "')";
            $i++;
        }
        $count = D('userloginrecord')->where($where)->count();
        $datalist = D('userloginrecord')->field('loginip,logindatetime,loginaddress,child_id')
            ->where($where)->page(I("post.page", "1"), I("post.limit", "10"))
            ->order('id DESC')->select();
        //账号名称：
        foreach ($datalist as $key => $val) {
            $datalist[$key]['user_name'] = $user_name;  //主用户
            if ($val['child_id']) {
                $datalist[$key]['child_name'] = M('childuser')->where('id = ' . $val['child_id'])->getField('child_name');
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
        $userid = I('get.userid');
        $this->assign('userid', $userid);
        //查询该主用户下所有还未删除的子账号
        $child_list = M('childuser')->field('id,child_name,bieming')
            ->where(['user_id' => $userid, 'del' => 0])->select();
        $this->assign('child_list', $child_list);
        $this->display();
    }

    //加载操作记录
    public function loadOperateRecordList()
    {
        $where = [];
        $userid = I("get.userid", "");
        $user_name = UserModel::getUserName($userid);
        $where[0] = "userid = " . $userid;
        $i = 1;
        $child_id = I('post.child_id', 0);  //子账号id
        if ($child_id) {
            $where[$i] = 'child_id=' . $child_id;
            $i++;
        }
        $start = I("post.start", "");
        if ($start <> "") {
            $where[$i] = "DATEDIFF('" . $start . "',operatedatetime) <= 0";
            $i++;
        }
        $end = I("post.end", "");
        if ($end <> "") {
            $where[$i] = "DATEDIFF('" . $end . "',operatedatetime) >= 0";
            $i++;
        }
        $userip = I("post.userip", "", 'trim');
        if ($userip <> "") {
            $where[$i] = "(userip = '" . $userip . "')";
            $i++;
        }
        $count = D('useroperaterecord')->where($where)->count();
        $datalist = D('useroperaterecord')->field('userip,operatedatetime,content,child_id')
            ->where($where)->page(I("post.page", "1"), I("post.limit", "10"))
            ->order('id DESC')->select();
        //账号名称：
        foreach ($datalist as $key => $val) {
            $datalist[$key]['user_name'] = $user_name;  //主用户
            if ($val['child_id']) {
                $datalist[$key]['child_name'] = M('childuser')->where('id = ' . $val['child_id'])->getField('child_name');
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

    //用户交易接口版本页面,添加验证码
    public function versionList()
    {
        $user_id = I('get.userid');
        //获取该用户所拥有的交易接口版本id组,如果存在则渲染
        $version = UserModel::getVersionId($user_id);
        $version_arr = $version ? explode(',', $version) : [];
        $this->assign('version_arr', $version_arr);
        $list = VersionModel::getVersionList(['status' => 1, 'del' => 0,]);
        $this->assign('list', $list);
        $this->assign('user_id', $user_id);
        $code_type = $this->getCodeType();
        $this->assign('code_type', $code_type);
        $this->display();
    }

    //确认修改用户交易接口版本
    //2019-5-7 rml：修改
    public function chooseVersion()
    {
        $user_id = I('post.user_id', 0, 'intval');
        $user_name = UserModel::getUserName($user_id);
        $msg = '修改用户[' . $user_name . ']的交易接口版本信息:';
        $verfiy_code = I('post.verfiy_code', '', 'trim');
        $code_type = I('post.code_type', 0, 'intval');
        $this->checkVerifyCode($verfiy_code, $code_type, $msg);
        $version_arr = I('post.version', '');
        //如果数组存在，则往数据库中修改，否则修改为空字符串
        $version_id = $version_arr ? implode(',', $version_arr) : '';
        $res = UserModel::editVersionid($user_id, $version_id);
        if ($res) {
            $this->addAdminOperate($msg . '修改成功');
            $this->ajaxReturn(['status' => 'ok', 'msg' => '修改成功']);
        }
        $this->addAdminOperate($msg . '修改失败');
        $this->ajaxReturn(['status' => 'no', 'msg' => '修改失败，请重试']);
    }

    //用户结算接口版本页面
    public function settleVersion()
    {
        $user_id = I('get.userid');
        //获取该用户所拥有的结算接口版本id组,如果存在则渲染
        $version = UserModel::getSettleVersion($user_id);
        $version_arr = $version ? explode(',', $version) : [];
        $this->assign('version_arr', $version_arr);
        $list = SettleversionModel::getVersionList(['status' => 1, 'del' => 0,]);
        $this->assign('list', $list);
        $this->assign('user_id', $user_id);
        $code_type = $this->getCodeType();
        $this->assign('code_type', $code_type);
        $this->display();
    }

    //确认修改用户的结算接口版本
    public function chooseSettleVer()
    {
        $user_id = I('post.user_id', 0, 'intval');
        $user_name = UserModel::getUserName($user_id);
        $msg = '修改用户[' . $user_name . ']的结算接口版本信息:';
        $version_arr = I('post.version', '');
        $verfiy_code = I('post.verfiy_code', '', 'trim');
        $code_type = I('post.code_type', 0, 'intval');
        $this->checkVerifyCode($verfiy_code, $code_type, $msg);
        //如果数组存在，则往数据库中修改，否则修改为空字符串
        $settle_version = $version_arr ? implode(',', $version_arr) : '';
        $res = UserModel::editSettleVer($user_id, $settle_version);
        if ($res) {
            $this->addAdminOperate($msg . '修改成功');
            $this->ajaxReturn(['status' => 'ok', 'msg' => '修改成功']);
        }
        $this->addAdminOperate($msg . '修改失败');
        $this->ajaxReturn(['status' => 'no', 'msg' => '修改失败，请重试']);
    }

    //用户的自助通道设置开关页面
    public function userAccountSet()
    {
        $user_id = I('get.userid');
        $this->assign('user_id', $user_id);
        $self_payapi = UserModel::getSelfPayapi($user_id);
        $this->assign('self_payapi', $self_payapi);
        //获取通道列表
        $payapi_list = PayapiModel::getPayapiidList(['status' => 1, 'del' => 0]);
        $user_payapi = UserModel::getPayapiId($user_id);
        $check_payapi = $user_payapi ? explode(',', $user_payapi) : [];
        $this->assign('check_payapi', $check_payapi);
        $this->assign('payapi_list', $payapi_list);
        $code_type = $this->getCodeType();
        $this->assign('code_type', $code_type);
        $this->display();
    }

    //修改自助通道账号的状态
    public function editUserAccount()
    {
        $self_payapi = I('post.self_payapi');
        $user_id = I('post.user_id');
        $payapi_arr = I('post.payapi_id');
        $payapi_id = implode(',', $payapi_arr);
        $payapi_id = $payapi_id ? $payapi_id : '';
        $user_name = UserModel::getUserName($user_id);
        $msg = '修改用户[' . $user_name . ']自助通道功能:';
        if ($self_payapi == 1) {
            $val = $msg . '修改为开通,';
        } else {
            $val = $msg . '修改为关闭,';
        }
        $verfiy_code = I('post.verfiy_code', '', 'trim');
        $code_type = I('post.code_type', 0, 'intval');
        $this->checkVerifyCode($verfiy_code, $code_type, $val);
        $res = UserModel::editUserInfo($user_id, ['self_payapi' => $self_payapi, 'payapi_id' => $payapi_id]);
        if ($res) {
            //关掉功能时，不必判断，因为判断在pay模块里有写开通与否
            $this->addAdminOperate($val . '修改成功');
            $this->ajaxReturn(['status' => 'ok', 'msg' => '修改成功']);
        }
        $this->addAdminOperate($val . '修改失败');
        $this->ajaxReturn(['status' => 'no', 'msg' => '修改失败，请重试']);

    }

    //2019-4-1 任梦龙：设置用户同一账号登录页面
    public function setSameUser()
    {
        $code_type = $this->getCodeType();
        $this->assign('code_type', $code_type);
        $same_admin = UserModel::getSameUser(I('get.userid'));
        $this->assign('same_admin', $same_admin);
        $this->display();
    }

    //确认修改同一账号登录设置
    public function editSameUser()
    {
        $id = I('post.id');
        $switch = I('post.switch');
        $user_name = UserModel::getUserName($id);
        $verfiy_code = I('post.verfiy_code', '', 'trim');
        $code_type = I('post.code_type', 0, 'intval');
        $val = $switch ? '修改为关闭,' : '修改为开启,';
        $msg = '设置用户[' . $user_name . ']账号是否可以同时登录:' . $val;
        $this->checkVerifyCode($verfiy_code, $code_type);
        $res = UserModel::editUserInfo($id, ['same_user' => $switch]);
        if ($res) {
            $this->addAdminOperate($msg . '修改成功');
            $this->ajaxReturn(['status' => 'ok', 'msg' => '修改成功']);
        }
        $this->addAdminOperate($msg . ',修改失败');
        $this->ajaxReturn(['status' => 'no', 'msg' => '修改失败']);
    }

    //2019-4-12 rml:激活用户页面
    public function activeUser()
    {
        $code_type = $this->getCodeType();
        $this->assign('code_type', $code_type);
        $this->display();
    }

    //2019-4-12 rml:确认激活
    public function confirmActive()
    {
        $user_id = I('post.user_id');
        $verfiy_code = I('post.verfiy_code', '', 'trim');
        $code_type = I('post.code_type', 0, 'intval');
        $this->checkVerifyCode($verfiy_code, $code_type);
        $user_name = UserModel::getUserName($user_id);
        $res_user = UserModel::editUserInfo($user_id, ['status' => 2]);
        if ($res_user) {
            $this->addAdminOperate('激活用户[' . $user_name . ']:操作成功');
            $this->ajaxReturn(['status' => 'ok', 'msg' => '操作成功']);
        } else {
            $this->addAdminOperate('激活用户[' . $user_name . ']:操作失败');
            $this->ajaxReturn(['status' => 'no', 'msg' => '操作失败']);
        }
    }


}
