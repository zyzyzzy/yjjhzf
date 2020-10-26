<?php
/**
 * 角色管理
 */

namespace Admin\Controller;

use Admin\Model\AdminuserModel;
use Admin\Model\AdminauthgroupModel;
use Admin\Model\AdminauthruleModel;
use Admin\Model\AdminauthgroupaccessModel;

//2019-4-10 任梦龙：角色修改为真删除
class AdminAuthGroupController extends CommonController
{
    /*
     * 角色列表
     */
    public function RoleList()
    {
        $this->display();
    }

    /*
     * 加载角色列表数据
     */
    public function LoadRoleList()
    {
        $where = [];
        $i = 0;
        //角色名称
        $auth_name = I('post.auth_name', '', 'trim');
        if ($auth_name <> '') {
            $where[$i] = "auth_name = '" . $auth_name . "'";
            $i++;
        }
        //状态
        $status = I("post.status", "");
        if ($status <> "") {
            $where[$i] = '`status` = ' . $status;
            $i++;
        }
        $this->ajaxReturn(PageDataLoad('adminauthgroup', $where), 'JSON');
    }

    /*
     * 添加角色页面
     */
    public function RoleAdd()
    {
        $this->display();
    }

    /*
     * 提交表单，添加数据
     */
    public function createRole()
    {
        $msg = '添加角色:' . I('post.auth_name', '', 'trim') . ',';
        $return = AddSave('adminauthgroup', 'add', '添加角色');
        $this->addAdminOperate($msg . $return['msg']);
        $this->ajaxReturn($return, 'json');
    }

    /*
     * 修改角色页面
     */
    public function RoleEdit()
    {
        $id = I('id');
        $one_info = AdminauthgroupModel::getAuthGroup($id);
        $this->assign('one_info', $one_info);
        $this->display();
    }

    /*
     * 提交表单，修改数据
     */
    public function RoleUpdate()
    {
        $msg = '修改角色:' . I('post.auth_name', '', 'trim') . ',';
        $return = AddSave('adminauthgroup', 'save', '修改角色');
        $this->addAdminOperate($msg . $return['msg']);
        $this->ajaxReturn($return, 'json');
    }

    /*
     * 根据当前角色id删除数据
     */
    //2019-4-10 任梦龙：删除角色时，需要将用到此角色的所有管理员的菜单重新生成
    public function RoleDel()
    {
        $role_id = I("id");
        $auth_name = AdminauthgroupModel::getAuthGroup($role_id);
        $msg = '删除角色:' . $auth_name;
        $res = AdminauthgroupModel:: delRealAuth($role_id);
        if ($res) {
            //找到用到此角色的管理员id组,然后为每个管理员重新生成菜单文件
            $admin_arr = AdminauthgroupaccessModel::getUid($role_id);
            if ($admin_arr) {
                $this->reGetAdminMenu($role_id);
            }
            $this->addAdminOperate($msg . ',删除成功');
            $this->ajaxReturn(['status' => 'ok', 'msg' => '删除成功']);
        }
        $this->addAdminOperate($msg . ',删除失败');
        $this->ajaxReturn(['status' => 'no', 'msg' => '删除失败']);
    }

    /*
     * 修改状态
     */
    public function UpdateStatus()
    {
        $id = I("post.id", 0, 'intval');  //角色id
        $auth_name = AdminauthgroupModel::getAuthname($id);
        $status = I("post.status", "");
        if ($status == 1) {
            $msgs = '修改为启用';
        } else {
            $msgs = '修改为禁用';
        }
        $msg = '修改角色[' . $auth_name . ']的状态:' . $msgs;
        $res = AdminauthgroupModel::editAuthgroup($id, ['status' => $status]);
        if ($res) {
            //如果是修改为禁用,本质与删除一致，则需要把用到该角色的管理员权限值先删除再重新生成
            if ($status == 2) {
                $this->reGetAdminMenu($id);
            }
            $this->addAdminOperate($msg . ',修改成功');
            $this->ajaxReturn(['status' => 'ok', 'msg' => '修改成功']);
        }
        $this->addAdminOperate($msg . ',修改失败');
        $this->ajaxReturn(['status' => 'no', 'msg' => '修改失败']);
    }


    /**
     * 2019-4-22 rml：改为禁用和删除的代码有部分相同，提取出来
     * @param $id :角色id
     */
    public function reGetAdminMenu($id)
    {
        //找到用到此角色的管理员id组,然后为每个管理员重新生成菜单文件
        $admin_arr = AdminauthgroupaccessModel::getUid($id);
        if ($admin_arr) {
            AdminauthgroupaccessModel::delGroup($id);
            foreach ($admin_arr as $val) {
                $admin_path = $this->getAdminMenuPath($val);
                if (file_exists($admin_path)) {
                    unlink($admin_path);
                }
                //重新计算这个管理员拥有的权限，如果有，修改数据，没有则清空数据
                $new_menu = AdminauthgroupaccessModel::getUserRules($val);
                if ($new_menu) {
                    $admin_menu = json_encode($new_menu);
                    AdminuserModel::editPassword($val, ['menu_json' => $admin_menu, 'menu_path' => $admin_path]);
                    file_put_contents($admin_path, $admin_menu);
                } else {
                    AdminuserModel::editPassword($val, ['menu_json' => '', 'menu_path' => '']);
                }
                unset($val);
            }
        }
    }


    /*
     * 分配角色页面
     */
    public function GiveRole()
    {
        $admin_id = I('id', 0, 'intval');
        //获取角色列表
        $list = AdminauthgroupModel::getGroupInfo();
        //该管理员拥有的角色id组
        $check_list = AdminauthgroupaccessModel::getGroupid($admin_id);
        $check_list = $check_list ? $check_list : [];
        $this->assign('list', $list);
        $this->assign('check_list', $check_list);
        $this->assign('admin_id', $admin_id);
        $this->display();
    }

    /*
     * 确认分配角色
     */
    //2019-4-10 任梦龙：修改
    public function ConfirmRoleGroup()
    {
        $admin_id = I('post.admin_id', 0, 'intval');
        if (!$admin_id) {
            $this->ajaxReturn(['status' => 'no', 'msg' => '请勿非法操作']);
        }
        $admin_name = AdminuserModel::getAdminName($admin_id);
        $msg = '为管理员[' . $admin_name . ']分配角色:';
        //获取角色id组，同时重新排序数组(重新排序下标)
        $group_id = I('post.group_id', '');
        if ($group_id) {
            sort($group_id);
        }
        //获取该管理员原来的角色
        $old_group = AdminauthgroupaccessModel::getGroupid($admin_id);
        $old_groupid = $old_group ? $old_group : '';
        //判断新角色组与原来的是否一致
        if ($group_id == $old_groupid) {
            $this->ajaxReturn(['status' => 'no', 'msg' => '未做任何修改,请确认!']);
        }
        if (!file_exists(C('ADMIN_MENU_PATH'))) {
            mkdir(C('ADMIN_MENU_PATH'), '0777', true);
        }
        $file_path = $this->getAdminMenuPath($admin_id);
        //如果存在原有角色,删除
        if ($old_group) {
            AdminauthgroupaccessModel::delUid($admin_id);
        }
        //将旧文件删除
        if (file_exists($file_path)) {
            unlink($file_path);
        }
        //修改时区分是选择了角色还是清空了角色
        if (!empty($group_id)) {
            foreach ($group_id as $v) {
                $add_data = array(
                    'uid' => $admin_id,
                    'group_id' => $v,
                );
                AdminauthgroupaccessModel::addUserAuthGroup($add_data);
            }
            //重新获取权限，和菜单文件，菜单路径
            $new_menu = AdminauthgroupaccessModel::getUserRules($admin_id);
            //如果新的角色组有权限则修改，否则清空
            if ($new_menu) {
                $admin_menu = json_encode($new_menu);
                AdminuserModel::editPassword($admin_id, ['menu_json' => $admin_menu, 'menu_path' => $file_path]);
                file_put_contents($file_path, $admin_menu);
            } else {
                AdminuserModel::editPassword($admin_id, ['menu_json' => '', 'menu_path' => '']);
            }
            $this->addAdminOperate($msg . '分配成功,重新分配了角色');
        } else {
            AdminuserModel::editPassword($admin_id, ['menu_json' => '', 'menu_path' => '']);
            $this->addAdminOperate($msg . '分配成功,将角色清空了');
        }
        $this->ajaxReturn(['status' => 'ok', 'msg' => '分配成功']);
    }

    //分配权限页面
    public function GiveRuleGroup()
    {
        $id = I('get.id', 0, 'intval');
        if (!$id) {
            $this->ajaxReturn(['status' => 'no', 'msg' => '请勿非法操作']);
        }
        $this->assign('id', $id);
        $menus = AdminauthruleModel::getMenu(0);

        //获取当前角色信息，如果存在rule_id，则将菜单id转换为数组
        $rule_id = AdminauthgroupModel::getRuleid($id);
        if ($rule_id) {
            $rulesArr = explode(',', $rule_id);
            $this->assign('rulesArr', $rulesArr);
        }
        $this->assign('menus', $menus);
        $this->display();
    }

    public function test()
    {
        dump($_POST);
    }

    /*
     * 确认分配权限
     */
    public function confirmRuleGroup()
    {
        $id = I('post.id');   //当前的一级菜单id
        $group_id = I('post.group_id');  //角色的id
        $checkBox = I('post.checkBox', '');  //得到当前一级菜单下的所有被选中的子菜单(二级和三级)
        $group = AdminauthgroupModel::getInfo($group_id);

        //判断是否存在原菜单组
        if (!$group['rule_id']) {
            //没有选择菜单：直接报错;有选择：将当前一级菜单和选中菜单存入
            if (!$checkBox) {
                $this->ajaxReturn(['status' => 'error', 'msg' => '暂未分配权限,请选择子菜单'], 'json', JSON_UNESCAPED_UNICODE);
            }
            array_push($checkBox, $id);  //将当前一级菜单整合
            $new_rule = implode(',', $checkBox);
        } else {
            $childIds = AdminauthruleModel::getMenuIds($id);  //获取当前一级菜单下所有子菜单(二级和三级)
            array_push($childIds, $id);  //将当前一级菜单整合
            //获取新的原菜单组(即不包含该一级菜单及子菜单)
            $old_rule = explode(',', $group['rule_id']);
            foreach ($old_rule as $key => $val) {
                if (in_array($val, $childIds)) {
                    unset($old_rule[$key]);
                }
            }
            if (!$checkBox) {
                $new_rule = implode(',', $old_rule);
            } else {
                //将新的菜单组与原菜单组整合
                array_push($checkBox, $id);  //将当前一级菜单整合
                $new_arr = array_merge($old_rule, $checkBox);
                $new_arr = array_unique($new_arr);
                $new_rule = implode(',', $new_arr);
            }
        }
        $res = AdminauthgroupModel::editAuthgroup($group_id, ['rule_id' => $new_rule]);
        if ($res) {
            $userids = AdminauthgroupaccessModel::getUid($group_id);
            foreach ($userids as $val) {
                //依次删除原文件，然后重新生成新菜单文件
                $admin_path = $this->getAdminMenuPath($val);
                if (file_exists($admin_path)) {
                    unlink($admin_path);
                }
                //删除菜单文件的同时,将数据库中的从菜单数据清空,管理员在首页刷新时直接刷新时，根据菜单表重新生成菜单文件
                AdminuserModel::editPassword($val, ['menu_json' => '', 'menu_path' => '']);
            }
            $this->addAdminOperate('为角色[' . $group['auth_name'] . ']分配权限值修改成功');
            $this->ajaxReturn([
                'status' => 'success',
                'msg' => '修改成功'
            ], 'json', JSON_UNESCAPED_UNICODE);
        } else {
            $this->addAdminOperate('为角色[' . $group['auth_name'] . ']分配权限值修改失败');
            $this->ajaxReturn([
                'status' => 'error',
                'msg' => '修改失败'
            ], 'json', JSON_UNESCAPED_UNICODE);
        }
    }
}
