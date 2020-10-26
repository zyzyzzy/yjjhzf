<?php

/**

 * 菜单列表控制器

 */



namespace Admin\Controller;

use Admin\Model\AdminauthruleModel;

use Admin\Model\AdminauthgroupModel;

use Admin\Model\AdminauthgroupaccessModel;

use Admin\Model\AdminuserModel;

class AdminAuthRuleController extends CommonController
{

    //菜单列表

    public function MenuList()
    {
        $this->display();
    }



    //加载菜单列表

    public function LoadMenuList()
    {

        //梯形递归,一级和二级菜单

        $menu_list = AdminauthruleModel::selectAllMenu();

        $menus = get_column($menu_list);

        foreach ($menus as $key => $val) {
            if ($val['level'] == 1) {
                $menus[$key]['menu_title'] = '----' . $val['menu_title'];

                $url_arr = explode('/', $menus[$key]['menu_url']);

                $menus[$key]['controller'] = $url_arr[0];

                $menus[$key]['action'] = $url_arr[1];
            }
        }


        $ReturnArr = [

            'code' => 0,

            'msg' => '数据加载成功', //响应结果

            'count' => count($menus), //总页数

            'data' => $menus

        ];

        $this->ajaxReturn($ReturnArr, 'JSON');
    }



    //添加菜单页面（一级菜单）

    public function MenuAdd()
    {
        $this->display();
    }



    //由于用户菜单是在管理后台进行操作，而且所有的用户共用一个菜单表的数据，所以当有新菜单生成或者删除时，应该将用户的菜单重新生成

    //提交表单，添加一级菜单数据


    public function createMenu()
    {

        //获取需要添加的数据

        $add_info = I('post.');

        $data = [

            'icon' => $add_info['icon'],

            'menu_title' => $add_info['menu_title'],

        ];

        $msg = '添加主菜单[' . $add_info['menu_title'] . '],';

        $authtable = D('adminauthrule');

        if (!$authtable->create($data)) {
            $this->ajaxReturn(["status" => 'no', "msg" => $authtable->getError()]);
        }

        $res = $authtable->add();

        if ($res) {

            //2019-3-25 任梦龙:删除代码，因为添加菜单只是在数据库中添加数据而已,如果要生成最新的菜单，则是在分配权限时进行处理

            $this->addAdminOperate($msg . '添加成功');

            $this->ajaxReturn(['status' => 'ok', 'msg' => '添加成功']);
        }

        $this->addAdminOperate($msg . '添加失败');

        $this->ajaxReturn(['status' => 'no', 'msg' => '添加失败']);
    }



    //列表中，弹出修改菜单页面

    public function MenuEdit()
    {

        //获取当前菜单id

        $menu_id = I('get.id', 0, 'intval');

        $this->assign('menu_id', $menu_id);

        //通过id获取当前菜单信息

        $info = AdminauthruleModel::getOneInfo($menu_id);

        if ($info['menu_url']) {
            $menu_url = explode('/', $info['menu_url']);

            $info['controller'] = $menu_url[0];

            $info['action'] = $menu_url[1];
        }

        $this->assign('info', $info);

        $this->display();
    }



    //提交表单。修改菜单数据

    public function MenuUpdate()
    {

        //接收修改后的数据

        $edit_info = I('post.');

        $authtable = D('adminauthrule');

        $data = [

            'id' => $edit_info['id'],

            'menu_title' => $edit_info['menu_title'],

            // 'menu_url' => $edit_info['controller'] . '/' . $edit_info['action'],

        ];

        if (I('post.level')>0) {
            $data['menu_url'] = $edit_info['controller'] . '/' . $edit_info['action'];
        }

        if (!$authtable->create($data)) {
            $this->ajaxReturn(["status" => 'no', "msg" => $authtable->getError()]);
        }

        $msg = '修改菜单[' . $edit_info['menu_title'] . '],';

        //获取结果

        $res = $authtable->save();

        if ($res) {

            //如果修改了菜单（比如名称修改了），那么也需要改变菜单文件

            //先获取用到该菜单的角色id组

            $group_arr = $this->reGetRuleId($edit_info['id'], 2);

            $find = AdminauthruleModel::getOneInfo($edit_info['id']);

            //如果修改的是一级或者二级菜单，需要重新生成文件,这样刷新页面时才会出现新样式

            $this->reGetAdminMenu($find['level'], $group_arr);

            $this->addAdminOperate($msg . '修改成功');

            $this->ajaxReturn(['status' => 'ok', 'msg' => '修改成功']);
        }

        $this->addAdminOperate($msg . '修改失败');

        $this->ajaxReturn(['status' => 'no', 'msg' => '修改失败']);
    }



    //删除菜单

    public function MenuDel()
    {

        //判断是否存在子菜单

        $menu_id = I('post.id');

        $is_exist = AdminauthruleModel::selectOpt($menu_id);

        if ($is_exist) {
            $this->ajaxReturn(['status' => 'no', 'msg' => '存在子菜单,暂不能删除']);
        }

        $find = AdminauthruleModel::getOneInfo($menu_id);

        $msg = '删除菜单[' . $find['menu_title'] . ']:';

        $res = AdminauthruleModel::menuDel($menu_id);

        if ($res) {

            //2019-4-17 rml：删除菜单时，将用到此菜单的角色id组的权限值重新分配

            $group_arr = $this->reGetRuleId($menu_id);

            //由于菜单文件存放的只是一级或者二级菜单，所以只有删除一级或者二级菜单时需要重新生成菜单文件,用户的菜单需要重新生成

            $this->reGetAdminMenu($find['level'], $group_arr);

            $this->addAdminOperate($msg . '删除成功');

            $this->ajaxReturn(['status' => 'ok', 'msg' => '删除成功']);
        }

        $this->addAdminOperate($msg . '删除失败');

        $this->ajaxReturn(['status' => 'no', 'msg' => '删除失败']);
    }



    //列表中的添加子菜单页面

    public function ChildMenuAdd()
    {
        $menu_id = I('get.id');

        $this->assign('menu_id', $menu_id);

        $this->display();
    }



    //提交表单，确认添加子菜单数据

    public function confirmChildMenu()
    {
        $child_info = I('post.');

        if (!($child_info['controller'] && $child_info['action'] && $child_info['menu_title'])) {
            $this->ajaxReturn(["status" => 'no', "msg" => '数据不能为空']);
        }

        $child_info['is_menu'] = 1;

        $child_info['level'] = 1;

        //通过level级别判断是添加二级菜单还是三级菜单

        $menu_level = AdminauthruleModel::MenuLevel($child_info['id']);

        if ($menu_level) {
            $child_info['is_menu'] = 2;

            $child_info['level'] = 2;
        }

        $data = [

            'pid' => $child_info['id'],

            'menu_title' => $child_info['menu_title'],

            'menu_url' => $child_info['controller'] . '/' . $child_info['action'],

            'is_menu' => $child_info['is_menu'],

            'level' => $child_info['level']

        ];

        $msg = '添加子菜单[' . $child_info['menu_title'] . '],';

        $authtable = D('adminauthrule');

        if (!$authtable->create($data)) {
            $this->ajaxReturn(["status" => 'no', "msg" => $authtable->getError()]);
        }

        $res = $authtable->add();

        if ($res) {
            $this->addAdminOperate($msg . '添加成功');

            $this->ajaxReturn(['status' => 'ok', 'msg' => '添加成功']);
        }

        $this->addAdminOperate($msg . '添加失败');

        $this->ajaxReturn(['status' => 'no', 'msg' => '添加失败']);
    }



    //查看三级菜单

    public function viewAdminOpt()
    {
        $id = I('get.id', '', 'intval');

        $_opts = AdminauthruleModel::selectOpt($id);

        if (!$_opts) {
            $this->ajaxReturn(['status' => 'no', 'msg' => '该菜单还未添加任何操作',]);
        } else {
            $this->assign('id', $id);

            $this->assign('opts', $_opts);

            $this->display();
        }
    }



    //加载三级菜单列表

    public function loadAdminOptList()
    {
        $pid = I('get.pid');

        $menu_title = I('post.menu_title', '', 'trim');

        $where = [];

        $where[0] = 'pid=' . $pid;

        if ($menu_title) {
            $where[1] = "menu_title='" . $menu_title . "'";
        }

        $count = M('adminauthrule')->where($where)->count();

        $list_opt = M('adminauthrule')->where($where)->page(I('post.page', 1), I('post.limit', 10))->select();

        foreach ($list_opt as $key => $val) {
            $menu_url = explode('/', $val['menu_url']);

            $list_opt[$key]['controller'] = $menu_url[0];

            $list_opt[$key]['action'] = $menu_url[1];
        }

        $return_arr = [

            'code' => 0,

            'msg' => '数据加载成功', //响应结果

            'count' => $count, //总页数

            'data' => $list_opt

        ];

        $this->ajaxReturn($return_arr);
    }



    //批量删除三级菜单

    public function delAllAdminMenu()
    {
        $id_str = I('post.idstr', '');

        if (!$id_str) {
            $this->ajaxReturn(['status' => 'no', 'msg' => '请选择菜单']);
        }

        $res = AdminauthruleModel::delAllMenu($id_str);

        $msg = '批量删除菜单:';

        if ($res) {

            //2019-4-17 rml：批量删除三级菜单后，需要将角色中的权限id组重新分配，菜单文件不必了

            //查询出用到每一个菜单的角色id

            $del_arr = explode(',', $id_str);

            foreach ($del_arr as $key => $menu_id) {
                $group_arr = $this->reGetRuleId($menu_id);

                $find = AdminauthruleModel::getOneInfo($menu_id);

                $this->reGetAdminMenu($find['level'], $group_arr);
            }

            $this->addAdminOperate($msg . '删除成功');

            $this->ajaxReturn(['status' => 'ok', 'msg' => '删除成功']);
        } else {
            $this->addAdminOperate($msg . '删除失败');

            $this->ajaxReturn(['status' => 'no', 'msg' => '删除失败']);
        }
    }



    /**

     * 2019-4-17 rml：封装：将所有用到此菜单的角色的权限值重新分配，同时得到角色id组

     * @param $menu_id :菜单id

     * @param $type :1=角色需要重新分配权限  2=不需要

     * @return array

     */

    public function reGetRuleId($menu_id, $type = 1)
    {

        //得到角色列表

        $admin_group = AdminauthgroupModel::getGroupArr();

        $group_arr = [];   //存储角色id

        foreach ($admin_group as $val) {

            //只有拥有权限id组的角色才会去循环

            if ($val['rule_id']) {
                $rule_arr = explode(',', $val['rule_id']);

                foreach ($rule_arr as $ko => $vk) {

                    //如果菜单id存在其中，剔除菜单id，然后重新给角色赋予权限id组

                    if ($menu_id == $vk) {
                        $group_arr[] = $val['id'];

                        if ($type == 1) {
                            unset($rule_arr[$ko]);

                            $new_rule = $rule_arr ? implode(',', $rule_arr) : '';

                            AdminauthgroupModel::editAuthgroup($val['id'], ['rule_id' => $new_rule]);
                        }

                        break;
                    }
                }
            }
        }

        return $group_arr;
    }



    /**

     * 2019-4-17 rml：封装：重新获取每一个管理的菜单文件及菜单json数据

     * @param $level：菜单等级

     * @param $group_arr：角色列表

     */

    public function reGetAdminMenu($level, $group_arr)
    {
        if ($level != 2) {
            if ($group_arr) {
                foreach ($group_arr as $k => $v) {

                    //通过角色id查询出用到该角色的用户组id

                    $find = AdminauthgroupaccessModel::getUid($v['id']);

                    if ($find) {
                        foreach ($find as $kk => $vo) {

                            //获取管理员菜单路径名称

                            $file_path = $this->getAdminMenuPath($vo);

                            if (file_exists($file_path)) {
                                unlink($file_path);
                            }

                            //重新生成菜单文件

                            $new_adminmenu = AdminauthgroupaccessModel::getUserRules($vo);

                            if ($new_adminmenu) {
                                file_put_contents($file_path, json_encode($new_adminmenu));

                                AdminuserModel::editPassword($vo, ['menu_json' => json_encode($new_adminmenu), 'menu_path' => $file_path]);
                            } else {
                                AdminuserModel::editPassword($vo, ['menu_json' => '', 'menu_path' => '']);
                            }
                        }
                    }
                }
            }
        }
    }
}
