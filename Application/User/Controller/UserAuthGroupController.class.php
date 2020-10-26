<?php

namespace User\Controller;

use User\Model\ChilduserModel;
use User\Model\UserauthgroupModel;
use User\Model\UserauthgroupaccessModel;
use User\Model\UserModel;
use User\Model\UserauthruleModel;

//用户角色控制器2019-4-10 任梦龙：由于角色不需要存档,所以将用户角色修改为真删除
class UserAuthGroupController extends UserCommonController
{
    public function __construct()
    {
        parent::__construct();
    }

    //角色列表页面
    public function userGroupList()
    {
        $this->display();
    }

    //加载角色列表
    public function loadUserGroup()
    {
        $where = [];
//        $where[0] = 'del=0';
        $where[0] = 'user_id=' . session('user_info.id');
        $i = 1;
        //角色名称
        $auth_name = I('post.auth_name', '', 'trim');
        if ($auth_name <> '') {
            $where[$i] = "auth_name like '%" . $auth_name . "%'";
            $i++;
        }
        //状态
        $status = I("post.status", "");
        if ($status <> "") {
            $where[$i] = '`status` = ' . $status;
            $i++;
        }
        $this->ajaxReturn(PageDataLoad('userauthgroup', $where), 'JSON');
    }

    //添加角色页面
    public function addUserGroup()
    {
        $this->assign('user_id', session('user_info.id'));
        $this->display();
    }

    //确认添加角色
    public function userGroupAdd()
    {
        $msg = '添加角色[' . I('post.auth_name', '', 'trim') . ']:';
        $res = AddSave('userauthgroup', 'add', '添加角色');
        $this->addUserOperate($msg . $res['msg']);
        $this->ajaxReturn($res);
    }

    //修改页面
    public function editUserGroup()
    {
        $id = I('get.id');
        $this->assign('id', $id);
        $info = UserauthgroupModel::getUseauthgroup($id);
        $this->assign('info', $info);
        $this->display();
    }

    //确认修改角色
    public function userGroupEdit()
    {
        $msg = '修改角色[' . I('post.auth_name', '', 'trim') . ']:';
        $res = AddSave('userauthgroup', 'save', '修改角色');
        $this->addUserOperate($msg . $res['msg']);
        $this->ajaxReturn($res);
    }

    //软删除角色,将所有引用到该角色的子账号重新生成菜单文件，菜单数据，还有账号--角色关系数据
    //2019-4-10 任梦龙：修改为真删除
    public function delUserGroup()
    {
        $id = I('post.id', 0, 'intval');
        if (!$id) {
            $this->ajaxReturn(['status' => 'no', 'msg' => '请勿非法操作']);
        }
        $auth_name = UserauthgroupModel::getUserauthNnme($id);
        $msg = '删除角色[' . $auth_name . ']:';
//        $res = UserauthgroupModel::editUserauthType($id, 'del', 1);
        $res = UserauthgroupModel::delUserAuthGroup($id);
        if ($res) {
            $this->reGetUserMenu(session('user_info.id'), $id);
            $this->addUserOperate($msg . '删除成功');
            $this->ajaxReturn(['status' => 'ok', 'msg' => '删除成功']);
        } else {
            $this->addUserOperate($msg . '删除失败');
            $this->ajaxReturn(['status' => 'no', 'msg' => '删除失败']);
        }
    }

    //修改角色状态
    public function updateGroupStstus()
    {
        $user_id = session('user_info.id');
        $id = I("post.id", 0, 'intval');
        $status = I("post.status", 0, 'intval');
        $res = UserauthgroupModel::saveUserAuthGroup($id, ['status' => $status]);
        $auth_name = UserauthgroupModel::getUserauthNnme($id);
        $val = $status == 1 ? '修改为正常,' : '修改为禁用,';
        $msg = '修改角色[' . $auth_name . ']的状态:' . $val;
        if ($res) {
            //改为禁用:与删除本质一致
            if ($status == 2) {
                $this->reGetUserMenu($user_id, $id);
            }
            $this->addUserOperate($msg . '修改成功');
            $this->ajaxReturn(['status' => 'ok', 'msg' => '修改成功']);
        } else {
            $this->addUserOperate($msg . '修改失败');
            $this->ajaxReturn(['status' => 'no', 'msg' => '修改失败']);
        }
    }

    //2019-4-15 rml：由于删除与将修改状态的操作一致，将代码提取出 $type=0:表示删除  $type=1：表示分配权限，不必删除对于关系
    public function reGetUserMenu($user_id, $id, $type = 0)
    {
        //得到所有用到该角色的子账号id组,然后删除对应关系
        $cid_arr = UserauthgroupaccessModel::getChildId($user_id, $id);
        if ($cid_arr) {
            if ($type == 0) {
                UserauthgroupaccessModel::delGroupId($user_id, $id);
            }
            //重新生成菜单数据和文件，如果存在，则存入
            foreach ($cid_arr as $key => $val) {
                $file_path = $this->getUserMenuPath($user_id, $val['cid']);
                if ($file_path) {
                    unlink($file_path);
                }
                //如果有菜单，则重新得到菜单数据
                $child_menus = UserauthgroupaccessModel::getUserRules($user_id, $val['cid']);
                if ($child_menus) {
                    $new_menu = json_encode($child_menus);
                    ChilduserModel::getMenuJson($val['cid'], ['menu_json' => $new_menu, 'menu_path' => $file_path]);
                    file_put_contents($file_path, $new_menu);
                    unset($new_menu);
                } else {
                    ChilduserModel::getMenuJson($val['cid'], ['menu_json' => '', 'menu_path' => '']);
                }
            }
        }
    }


    //2019-4-29 rml：重新写权限页面，页面与系统上的一致
    //分配权限页面
    public function giveUserMenu()
    {
        $id = I('get.id', 0, 'intval');
        //获取菜单列表，树状
        $menus = UserauthruleModel::getMenu(0);
        //获取当前角色信息，如果存在rule_id，则将菜单id转换为数组
        $role_info = UserauthgroupModel::getUseauthgroup($id);
        if ($role_info['rule_id']) {
            $rulesArr = explode(',', $role_info['rule_id']);
            $this->assign('rulesArr', $rulesArr);
        }
        //依据用户表的usertype字段值：1=普通商户;2=代理商,如果是代理商，则显示代理专区，否则不显示
        $user_type = UserModel::getUserType(session('user_info.id'));
        foreach ($menus as $key => $val) {
            if ($user_type == 1) {
                if ($val['menu_title'] == '代理专区') {
                    unset($menus[$key]);
                }
            }
            //子账号管理只有主用户才有
            if ($val['menu_title'] == '子账号管理') {
                unset($menus[$key]);
            }
        }
        $this->assign('menus', $menus);
        $this->assign('id', $id);
        $this->display();
    }

    //确认分配角色权限,当角色的权限改变时，将每一个用到该角色的子账号的菜单文件和数据修改
    public function confirmRuleGroup()
    {
        $id = I('post.id');   //当前的一级菜单id
        $group_id = I('post.group_id');  //角色的id
        $checkBox = I('post.checkBox', '');  //得到当前一级菜单下的所有被选中的子菜单(二级和三级)
        $group = UserauthgroupModel::getUseauthgroup($group_id);

        //判断是否存在原菜单组
        if(!$group['rule_id']){
            //没有选择菜单：直接报错;有选择：将当前一级菜单和选中菜单存入
            if(!$checkBox){
                $this->ajaxReturn(['status' => 'error', 'msg' => '暂未分配权限,请选择子菜单'], 'json', JSON_UNESCAPED_UNICODE);
            }
            array_push($checkBox,$id);  //将当前一级菜单整合
            $new_rule = implode(',', $checkBox);
        }else {
            $childIds = UserauthruleModel::getMenuIds($id);  //获取当前一级菜单下所有子菜单(二级和三级)
            array_push($childIds,$id);  //将当前一级菜单整合
            //获取新的原菜单组(即不包含该一级菜单及子菜单)
            $old_rule = explode(',',$group['rule_id']);
            foreach ($old_rule as $key => $val) {
                if (in_array($val, $childIds)) {
                    unset($old_rule[$key]);
                }
            }

            if(!$checkBox){
                $new_rule = implode(',',$old_rule);
            }else {
                //如果$old_rule为空了：表示原菜单组就是被选中的菜单组
//                if(!$old_rule){
//                    $this->ajaxReturn(['status' => 'error', 'msg' => '未做修改,请确认'], 'json', JSON_UNESCAPED_UNICODE);
//                }
                //将新的菜单组与原菜单组整合
                array_push($checkBox,$id);  //将当前一级菜单整合
                $new_arr = array_merge($old_rule,$checkBox);
                $new_arr = array_unique($new_arr);
                $new_rule = implode(',', $new_arr);
            }
        }
        $res = UserauthgroupModel::editRuleId($group_id, $new_rule);
        if ($res) {
            $child_ids = UserauthgroupaccessModel::getChildId(session('user_info.id'), $group_id);
            foreach ($child_ids as $val) {
                //依次删除原文件，然后重新生成新菜单文件
                $user_path = $this->getUserMenuPath(session('user_info.id'), $val);
                if (file_exists($user_path)) {
                    unlink($user_path);
                }
                //删除菜单文件的同时,将数据库中的从菜单数据清空,子账号在首页刷新时直接刷新时，根据菜单表重新生成菜单文件
                ChilduserModel::getMenuJson($val, ['menu_json' => '', 'menu_path' => '']);
            }
            $this->addUserOperate('为角色[' . $group['auth_name'] . ']分配权限值修改成功');
            $this->ajaxReturn([
                'status' => 'success',
                'msg' => '修改成功'
            ], 'json', JSON_UNESCAPED_UNICODE);
        } else {
            $this->addUserOperate('为角色[' . $group['auth_name'] . ']分配权限值修改失败');
            $this->ajaxReturn([
                'status' => 'error',
                'msg' => '修改失败'
            ], 'json', JSON_UNESCAPED_UNICODE);
        }
    }

}