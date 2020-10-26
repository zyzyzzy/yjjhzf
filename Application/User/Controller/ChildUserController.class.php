<?php

namespace User\Controller;

use User\Model\ChilduserModel;
use User\Model\UserauthgroupModel;
use User\Model\UserauthgroupaccessModel;
use User\Model\GooglecodeModel;
use User\Model\IpaccesslistModel;
use User\Model\UserModel;

class ChildUserController extends UserCommonController
{
    public function __construct()
    {
        parent::__construct();
    }

    //用户子账号列表页面
    public function childList()
    {
        $this->display();
    }

    //加载用户的子账号列表
    public function loadChildUserList()
    {
        $where = [];
        $where[0] = "del=0";
        $where[1] = "user_id=" . session('user_info.id');
        $i = 2;
        //子账号名称
        $child_name = I('post.child_name', '', 'trim');
        if ($child_name <> '') {
            $where[$i] = "child_name like '%" . $child_name . "%'";
            $i++;
        }
        //添加子账号的别名
        $bieming = I('post.bieming', '', 'trim');
        if ($bieming <> '') {
            $where[$i] = "bieming like '%" . $bieming . "%'";
            $i++;
        }
        //状态
        $status = I("post.status", "");
        if ($status <> "") {
            $where[$i] = '`status` = ' . $status;
            $i++;
        }
        $count = D('childuser')->where($where)->count();
        $datalist = D('childuser')->scope('default_scope')->where($where)->page(I("post.page", "1"), I("post.limit", "10"))->order('id DESC')->select();
        foreach ($datalist as $key => $val) {
            //查询是否开启二次验证
            $find = GooglecodeModel::getInfo('child', $val['id']);
            if ($find) {
                $datalist[$key]['google'] = $find['status'];
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

    //添加子账号页面
    public function addChildUser()
    {
        $this->display();
    }

    //确认添加子账号
    public function childUserAdd()
    {
        $msg = '添加子账号:' . I('post.child_name', '', 'trim') . ',';
        $return = AddSave('childuser', 'add', '添加子账号');
        $this->addUserOperate($msg . $return['msg']);
        $this->ajaxReturn($return, 'JSON');
    }

    //修改子账号页面
    public function editChildUser()
    {
        $id = I('get.id');
        $this->assign('id', $id);
        $info = ChilduserModel::getChildInfo($id);
        $this->assign('info', $info);
        $this->display();
    }

    //确认修改子账号
    public function childUserEdit()
    {
        $msg = '修改子账号:' . I('post.child_name', '', 'trim') . ',';
        $return = AddSave('childuser', 'save', '修改子账号');
        $this->addUserOperate($msg . $return['msg']);
        $this->ajaxReturn($return, 'JSON');
    }

    //软删除子账号
    public function delChildUser()
    {
        $id = I('post.id', 0, 'intval');
        if (!$id) {
            $this->ajaxReturn(['status' => 'no', 'msg' => '请勿非法操作']);
        }
        $child_name = ChilduserModel::getChildName($id);
        $msg = '删除子账号[' . $child_name . ']:';
//        $res = ChilduserModel::editChilduserType($id, 'del', 1);
        $res = ChilduserModel::getMenuJson($id, ['del' => 1]);
        if ($res) {
            //删除子账号时，同时将权限数据，菜单文件,菜单数据清空
            UserauthgroupaccessModel::delcid($id, session('user_info.id'));  //删除账号--角色关系数据
            $file_path = $this->getUserMenuPath(session('user_info.id'), $id);  //得到子账号菜单文件路径
            if (file_exists($file_path)) {
                unlink($file_path);
            }
            ChilduserModel::getMenuJson($id, ['menu_json' => '', 'menu_path' => '']);
            $this->addUserOperate($msg . '删除成功');
            $this->ajaxReturn(['status' => 'ok', 'msg' => '删除成功']);
        } else {
            $this->addUserOperate($msg . '删除失败');
            $this->ajaxReturn(['status' => 'no', 'msg' => '删除失败']);
        }
    }

    //修改状态
    //修改状态时，不必将菜单文件删除,因为这个状态只是代表子账号在登陆时的状态
    public function updateStatus()
    {
        $id = I("post.id", 0, 'intval');  //子账号id
        $child_name = ChilduserModel::getChildName($id);
        $msg = '修改子账号[' . $child_name . ']的状态:';
        $status = I("post.status", 0, 'intval');
        if ($status == 1) {
            $val = '修改为正常';
        } else {
            $val = '修改为禁用';
        }
        //2019-4-15 rml：修改
        $res = ChilduserModel::getMenuJson($id, ['status' => $status]);
        if ($res) {
            $this->addUserOperate($msg . $val . ',修改成功');
            $this->ajaxReturn(['status' => 'ok', 'msg' => '修改成功']);
        } else {
            $this->addUserOperate($msg . $val . ',修改失败');
            $this->ajaxReturn(['status' => 'no', 'msg' => '修改失败']);
        }
    }

    //主用户为子账号分配角色页面
    public function giveUserGroup()
    {
        $id = I('id', 0, 'intval');
        $list = UserauthgroupModel::getAuthGrouplist(session('user_id'));
        //获取这个主用户为这个子账号分配的角色,在前台对比角色id是否在这个数组中
        $check_list = UserauthgroupaccessModel::findUserGroupAccess($id, session('user_id'));;
        $this->assign('list', $list);
        $this->assign('check_list', $check_list);
        $this->assign('id', $id);
        $this->display();
    }

    //主用户为子账号分配角色处理程序,同时生成对应的菜单json文件存进数据库中，生成文件，文件名要加密（hash加密）
    //2019-3-27 任梦龙：为子账号分配角色处理程序
    public function confirmUserGroup()
    {
        if (IS_POST) {
            $user_id = session('user_info.id');
            $id = I('id', 0, 'intval');
            if (!($user_id && $id)) {
                $this->ajaxReturn(['status' => 'no', 'msg' => '请勿非法操作']);
            }
            $child_name = ChilduserModel::getChildName($id);
            $msg = '为子账号[' . $child_name . ']分配角色:';
            $group_id = I('post.group_id', '');  //获取角色id组
            if($group_id){
                sort($group_id);
            }
            //获取该子账号原来的角色
            $old_group = UserauthgroupaccessModel::findUserGroupAccess($id, $user_id);
            $old_groupid = $old_group ? $old_group : '';
            //比较新角色列表与原角色列表的否一致，如果是则不用修改
            if ($group_id == $old_groupid) {
                $this->ajaxReturn(['status' => 'no', 'msg' => '未进行分配,请确认']);
            }
            if (!file_exists(C('USER_MENU_PATH'))) {
                mkdir(C('USER_MENU_PATH'), '0777', true);
            }
            $file_path = $this->getUserMenuPath($user_id, $id);
            if (file_exists($file_path)) {
                unlink($file_path);
            }
            //如果存在原有角色,删除
            if ($old_group) {
                UserauthgroupaccessModel::delcid($id, $user_id);
            }
            //如果重新分配了角色,则往数据库中添加数据，子账号-角色关系
            if (!empty($group_id)) {
                foreach ($group_id as $v) {
                    $add_data = array(
                        'cid' => $id,
                        'group_id' => $v,
                        'user_id' => $user_id,
                    );
                    UserauthgroupaccessModel::addUserAuthGroup($add_data);
                }
                //重新得到子账号的菜单,存入数据库,并且生成文件，存入文件路径
                $child_menus = UserauthgroupaccessModel::getUserRules($user_id, $id);
                if ($child_menus) {
                    $new_menu = json_encode($child_menus);
                    ChilduserModel::getMenuJson($id, ['menu_json' => $new_menu, 'menu_path' => $file_path]);
                    file_put_contents($file_path, $new_menu);
                    unset($new_menu);
                } else {
                    ChilduserModel::getMenuJson($id, ['menu_json' => '', 'menu_path' => '']);
                }
                $this->addUserOperate($msg . '分配成功,重新分配了角色');
                $this->ajaxReturn(['status' => 'ok', 'msg' => '分配成功']);
            }
            //如果是取消角色分配,菜单数据清空
            if (empty($group_id)) {
                ChilduserModel::getMenuJson($id, ['menu_json' => '', 'menu_path' => '']);
                $this->addUserOperate($msg . '分配成功,将角色清空了');
                $this->ajaxReturn(['status' => 'ok', 'msg' => '分配成功']);
            }
        }
    }

    //谷歌验证页面
    public function childGoogle()
    {
        $id = I('get.id', 0);
        if (!$id) {
            $this->ajaxReturn(['status' => 'no', 'msg' => '请勿非法操作']);
        }
        $code_type = $this->getCodeType();
        $this->assign('code_type', $code_type);
        $this->display();
    }

    //谷歌验证的开启和关闭处理程序
    public function editGoogle()
    {
        $res_user = GooglecodeModel::getInfo('user', session('user_info.id'));
        if (!$res_user) {
            $this->ajaxReturn(['status' => 'no', 'msg' => '主用户还未开通谷歌验证功能']);
        }
        if ($res_user['status'] != 1) {
            $this->ajaxReturn(['status' => 'no', 'msg' => '主用户还未开启谷歌验证功能']);
        }
        $verfiy_code = I('post.verfiy_code', '', 'trim');
        $code_type = I('post.code_type', 0, 'intval');
        $child_id = I('post.child_id');
        $switch = I('post.switch');
        $google = I('post.google');
        $child_name = ChilduserModel::getChildName($child_id);
        if ($switch == 1) {
            if ($google != 2) {
                $this->ajaxReturn(['status' => 'no', 'msg' => '已是开通状态,请确认']);
            }
            $msg = '修改子账号[' . $child_name . ']的谷歌验证状态:修改为开通,';
            $this->checkVerifyCode($verfiy_code, $code_type, $msg);
            $res = GooglecodeModel::addInfo('child', $child_id);
        } else {
            if ($google == 2) {
                $this->ajaxReturn(['status' => 'no', 'msg' => '已是关闭状态,请确认']);
            }
            $msg = '修改子账号[' . $child_name . ']的谷歌验证状态:修改为关闭,';
            $this->checkVerifyCode($verfiy_code, $code_type, $msg);
            $res = GooglecodeModel::delInfo('child', $child_id);
        }
        if ($res) {
            $this->addUserOperate($msg . '修改成功');
            $this->ajaxReturn(['status' => 'ok', 'msg' => '修改成功']);
        }
        $this->addUserOperate($msg . '修改失败');
        $this->ajaxReturn(['status' => 'no', 'msg' => '修改失败']);
    }

    //修改密码页面
    public function editUserPwd()
    {
        $this->assign('child_id', I('get.id'));
        $this->display();
    }

    //修改登录密码页面
    public function editLoginPwd()
    {
        $code_type = $this->getCodeType();
        $this->assign('code_type', $code_type);
        $this->display();
    }

    //修改登录密码处理程序
    public function loginPwdEdit()
    {
        //检测验证码的代码
        $verfiy_code = I('post.verfiy_code', '', 'trim');
        $code_type = I('post.code_type', 0, 'intval');
        $login_pwd = I('post.child_pwd', '', 'trim');
        $id = I('post.id');
        $child_info = ChilduserModel::getChildInfo($id);
        $msg = '修改子账号[' . $child_info['child_name'] . ']的登录密码:';
        $this->checkVerifyCode($verfiy_code, $code_type, $msg);
        //验证密码的合法性
        if (!$login_pwd) {
            $this->ajaxReturn(['status' => 'no', 'msg' => '请输入新密码']);
        }
        if (MD5($login_pwd) == $child_info['child_pwd']) {
            $this->ajaxReturn(['status' => 'no', 'msg' => '密码与原密码一致，请重新输入']);
        }
        //2019-4-17 rml：修改
        $pwd_rule = '/^[A-Za-z0-9]{6,20}$/';
        if (!preg_match($pwd_rule, $login_pwd)) {
            $this->ajaxReturn(['status' => 'no', 'msg' => '密码由大小写英文，数字组成，长度在6-20字符之间']);
        }
        $res = ChilduserModel::getMenuJson($id,['child_pwd'=>md5($login_pwd)]);
        if ($res) {
            $this->addUserOperate($msg . '修改成功');
            $this->ajaxReturn(['status' => 'ok', 'msg' => '修改成功']);
        }
        $this->addUserOperate($msg . '修改失败');
        $this->ajaxReturn(['status' => 'no', 'msg' => '修改失败']);

    }

    //修改管理密码页面
    public function editManagePwd()
    {
        $code_type = $this->getCodeType();
        $this->assign('code_type', $code_type);
        $this->display();
    }

    //修改管理密码处理程序
    public function managePwdEdit()
    {
        //检测验证码的代码
        $verfiy_code = I('post.verfiy_code', '', 'trim');
        $code_type = I('post.code_type', 0, 'intval');
        $manage_pwd = I('post.manage_pwd', '', 'trim');
        $id = I('post.id');
        $child_info = ChilduserModel::getChildInfo($id);
        $msg = '修改子账号[' . $child_info['child_name'] . ']的管理密码:';
        $this->checkVerifyCode($verfiy_code, $code_type, $msg);
        //验证密码的合法性
        if (!$manage_pwd) {
            $this->ajaxReturn(['status' => 'no', 'msg' => '请输入新密码']);
        }
        if (md5($manage_pwd) == $child_info['manage_pwd']) {
            $this->ajaxReturn(['status' => 'no', 'msg' => '密码与原密码一致，请重新输入']);
        }
        //2019-4-17 rml：修改
        $pwd_rule = '/^[A-Za-z0-9]{6,20}$/';
        if (!preg_match($pwd_rule, $manage_pwd)) {
            $this->ajaxReturn(['status' => 'no', 'msg' => '密码由大小写英文，数字组成，长度在6-20字符之间']);
        }
        $res = ChilduserModel::getMenuJson($id,['manage_pwd'=>md5($manage_pwd)]);
        if ($res) {
            $this->addUserOperate($msg . '修改成功');
            $this->ajaxReturn(['status' => 'ok', 'msg' => '修改成功']);
        }
        $this->addUserOperate($msg . '修改失败');
        $this->ajaxReturn(['status' => 'no', 'msg' => '修改失败']);

    }

    //2019-3-27 任梦龙：子账号的IP白名单页面程序写了，但是没有进行判断
    /*****************************************/
    //子账号ip白名单页面
    public function childIpList()
    {
        $id = I('get.id');
        $this->assign('id', $id);
        $this->assign('user_id', session('user_info.id'));
        $this->display();
    }

    //加载列表
    public function loadChildIp()
    {
        $child_id = I('get.child_id');
        $where = [
            'admin_id' => 0,
            'user_id' => session('user_info.id'),
            'child_id' => $child_id
        ];
        $this->ajaxReturn(PageDataLoad('ipaccesslist', $where));
    }

    //添加子账号的ip白名单
    public function addChildIp()
    {
        $user_name = UserModel::getUsername(session('user_info.id'));
        $child_name = ChilduserModel::getChildName(I('post.child_id'));
        $msg = '用户[' . $user_name . ']为子账号[' . $child_name . ']添加IP白名单[' . I('post.ip', '', 'trim') . ']:';
        $return = AddSave('ipaccesslist', 'add', '添加ip');
        $this->addUserOperate($msg . $return['msg']);
        $this->ajaxReturn($return);
    }

    //删除ip
    public function delChildIp()
    {
        $id = I('post.id');
        if (!$id) {
            $this->ajaxReturn(['status' => 'no', 'msg' => '请勿非法操作']);
        }
        $user_name = UserModel::getUsername(session('user_info.id'));
        $child_name = ChilduserModel::getChildName(I('get.child_id'));
        $ip = IpaccesslistModel::getChilduserIp($id);
        $msg = '用户[' . $user_name . ']删除子账号[' . $child_name . ']的IP白名单[' . $ip . ']:';
        $res = IpaccesslistModel::delIp($id);
        if ($res) {
            $this->addUserOperate($msg . '删除成功');
            $this->ajaxReturn(['status' => 'ok', 'msg' => '删除成功']);
        }
        $this->addUserOperate($msg . '删除失败');
        $this->ajaxReturn(['status' => 'no', 'msg' => '删除失败']);
    }
    /*********************************************/

    //2019-4-1 任梦龙：设置子账号同一账号登录页面
    public function setSameChild()
    {
        $code_type = $this->getCodeType();
        $this->assign('code_type', $code_type);
        $this->display();
    }

    //2019-4-1 任梦龙：确认修改同一账号登录设置
    public function editSameChild()
    {
        $id = I('post.id');
        $switch = I('post.switch');
        $child_name = ChilduserModel::getChildName($id);
        $verfiy_code = I('post.verfiy_code', '', 'trim');
        $code_type = I('post.code_type', 0, 'intval');
        $val = $switch ? '修改为关闭,' : '修改为开启,';
        $msg = '设置子账号[' . $child_name . ']是否可以同时登录:' . $val;
        $this->checkVerifyCode($verfiy_code, $code_type, $msg);
        $res = ChilduserModel::getMenuJson($id, ['same_child' => $switch]);
        if ($res) {
            $this->addUserOperate($msg . '修改成功');
            $this->ajaxReturn(['status' => 'ok', 'msg' => '修改成功']);
        }
        $this->addUserOperate($msg . ',修改失败');
        $this->ajaxReturn(['status' => 'no', 'msg' => '修改失败']);
    }


}