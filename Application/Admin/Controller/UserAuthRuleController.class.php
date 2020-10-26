<?php

namespace Admin\Controller;

use Admin\Model\UserauthruleModel;
use Admin\Model\UserauthgroupaccessModel;

//2019-4-10 任梦龙：修改用户角色为真删除
class UserAuthRuleController extends CommonController
{
    public function __construct()
    {
        parent::__construct();
    }

    //菜单列表，显示菜单按钮
    public function userListMenu()
    {
        $this->display();
    }

    //加载菜单列表
    //2019-4-9 任梦龙：去除搜索,分页
    public function loadUserMenu()
    {
        //梯形递归:显示当前用户下的一二级菜单 (is_menu=1)
        $menu_list = UserauthruleModel::selectAllMenu();
        $menus = get_column($menu_list);
        foreach ($menus as $key => $val) {
            if ($val['level'] == 1) {
                $menus[$key]['menu_title'] = '----' . $val['menu_title'];
                $url_arr = explode('/', $menus[$key]['menu_url']);
                $menus[$key]['controller'] = $url_arr[0];
                $menus[$key]['action'] = $url_arr[1];
            }
        }
        //分页
//        $page = I("post.page");
//        $limit = I("post.limit");
//        $i = 0;
//        foreach ($menus as $k => $v) {
//            if ($i < $limit && ($i + ($page - 1) * $limit) < count($menus)) {
//                $new_menus[] = $menus[$i + ($page - 1) * $limit];
//            } else {
//                break;
//            }
//            $i++;
//        }
        $return_arr = [
            'code' => 0,
            'msg' => '数据加载成功', //响应结果
            'count' => count($menus), //总页数
            'data' => $menus
        ];
        $this->ajaxReturn($return_arr);
    }

    //添加一级菜单页面
    public function addUserMenu()
    {
        $this->display();
    }

    //提交表单，一级菜单确认添加-->只有菜单图标和名称
    public function userMenuAdd()
    {
        $add_info = I('post.add_info');
        if (!($add_info['menu_title'] && $add_info['icon'])) {
            $this->ajaxReturn(['status' => 'no', 'msg' => '数据不得为空']);
        }
        $data = [
            'icon' => $add_info['icon'],
            'menu_title' => $add_info['menu_title'],
        ];
        $msg = '添加用户一级菜单[' . $add_info['menu_title'] . '],';
        $authtable = D('userauthrule');
        if (!$authtable->create($data)) {
            $this->ajaxReturn(["status" => 'no', "msg" => $authtable->getError()]);
        }
        $res = $authtable->add();
        if ($res) {
            //2019-4-10 任梦龙：生成菜单时不需要重新生成菜单文件
            //2019-4-12 rml：由于改变了菜单数据，所以需要更新主用户菜单，子账号则由用户去分配，不用管
            $this->unlinkUserMenu();
            $this->addAdminOperate($msg . '添加成功');
            $this->ajaxReturn(['status' => 'ok', 'msg' => '添加成功']);
        } else {
            $this->addAdminOperate($msg . '添加失败');
            $this->ajaxReturn(['status' => 'no', 'msg' => '添加失败']);
        }
    }

    public function unlinkUserMenu()
    {
        if (!file_exists(C('USER_MENU_PATH'))) {
            mkdir(C('USER_MENU_PATH'), '0777', true);
        }
        $main_file_name = hash('sha1', 'usermenu');
        $main_menu_path = C('USER_MENU_PATH') . $main_file_name . '.json';
        if(file_exists($main_menu_path)){
            unlink($main_menu_path);
        }
//        $all_menus = M('userauthrule')->where(['is_menu' => 1])->select();
//        $menus = get_column($all_menus, 2);
//        //将菜单形式转换为配置文件形式的数组
//        $one_menu = [];
//        foreach ($menus as $key => $val) {
//            $one_menu[$val['menu_title']]['icon'] = html_entity_decode($val['icon']);
//            foreach ($val[$val['id']] as $k => $v) {
//                $one_menu[$val['menu_title']]['menu'][$v['menu_title']] = $v['menu_url'];
//            }
//        }
//        file_put_contents($main_menu_path, json_encode($one_menu));
    }

    //添加子菜单，即二级或三级菜单页面
    public function addUserChildMenu()
    {
        $id = I('get.id');  //用户的菜单id
        $this->assign('id', $id);
        $this->display();
    }

    //提交表单，确认添加二级或三级菜单
    public function userChildMenuAdd()
    {
        $add_info = I('post.add_child');
        if (!($add_info['menu_title'] && $add_info['controller'] && $add_info['action'])) {
            $this->ajaxReturn(['status' => 'no', 'msg' => '数据不得为空']);
        }
        $menu_url = $add_info['controller'] . '/' . $add_info['action'];
        //判断是添加二级菜单还是三级菜单
        $data = [
            'pid' => $add_info['pid'],  //父级菜单id
            'menu_url' => $menu_url,
            'menu_title' => $add_info['menu_title']
        ];
        $level = UserauthruleModel::getUserMenuLevel($add_info['pid']);
        //添加二级菜单
        if ($level == 0) {
            $type = '二级菜单';
            $data['level'] = 1;
            $data['is_menu'] = 1;
        }
        //添加三级菜单
        if ($level == 1) {
            $type = '三级菜单';
            $data['level'] = 2;
            $data['is_menu'] = 2;
        }
        $msg = '添加用户的' . $type . '[' . $add_info['menu_title'] . '],';
        $authtable = D('userauthrule');
        if (!$authtable->create($data)) {
            $this->ajaxReturn(["status" => 'no', "msg" => $authtable->getError()]);
        }
        $res = $authtable->add();
        if ($res) {
            $this->unlinkUserMenu();
            $this->addAdminOperate($msg . '添加成功');
            $this->ajaxReturn(['status' => 'ok', 'msg' => '添加成功']);
        } else {
            $this->addAdminOperate($msg . '添加失败');
            $this->ajaxReturn(['status' => 'no', 'msg' => '添加失败']);
        }
    }

    //2019-4-9 任梦龙：重新生成用户菜单
    public function reMakeUserMenupath()
    {
        $menus = UserauthgroupaccessModel::getAllUserMenu();
        $user_menupath = $this->getUserMenupath();
        if (file_exists($user_menupath)) {
            unlink($user_menupath);
        }
        file_put_contents($user_menupath, json_encode($menus));
    }


    //修改菜单页面
    public function editUserMenu()
    {
        $id = I('get.id');  //用户的菜单id
        $this->assign('id', $id);
        //页面以level判断是一级还是二三级
        $info = UserauthruleModel::getUserMenuInfo($id);
        if ($info['menu_url']) {
            $menu_url = explode('/', $info['menu_url']);
            $info['controller'] = $menu_url[0];
            $info['action'] = $menu_url[1];
        }
        $this->assign('info', $info);
        $this->display();
    }

    //提交表单，确认修改:
    public function userMenuEdit()
    {
        $edit_info = I('post.edit_info');
        $level = UserauthruleModel::getUserMenuLevel($edit_info['id']);

        //一级菜单
        if ($level == 0) {
            $type = '一级菜单';
            $data = [
                'icon' => $edit_info['icon'],
                'menu_title' => $edit_info['menu_title']
            ];
            $res = UserauthruleModel::updateUserMenu($data, $edit_info['id']);
        }

        //二级或者三级菜单
        if ($level == 1 || $level == 2) {
            $type = $level == 1 ? '二级菜单' : '三级菜单';
            $menu_url = $edit_info['controller'] . '/' . $edit_info['action'];
            //判断菜单url是否重复
            $where_url = [
                'id' => ['neq', $edit_info['id']],
                'menu_url' => ['eq', $menu_url]
            ];
            $count_url = UserauthruleModel::findMenuUrl($where_url);
            if ($count_url > 0) {
                $this->ajaxReturn(['status' => 'no', 'msg' => '已存在相同的菜单url']);
            }
            $data = [
                'menu_url' => $menu_url,
                'menu_title' => $edit_info['menu_title']
            ];
            $res = UserauthruleModel::updateUserMenu($data, $edit_info['id']);
        }
        $msg = '修改用户的' . $type . '[' . $edit_info['menu_title'] . ']:';
        if ($res) {
            $this->unlinkUserMenu();
            $this->addAdminOperate($msg . '修改成功');
            $this->ajaxReturn(['status' => 'ok', 'msg' => '修改成功'], 'JSON');

        } else {
            $this->addAdminOperate($msg . '修改失败');
            $this->ajaxReturn(['status' => 'no', 'msg' => '修改失败'], 'JSON');
        }

    }

    //软删除用户菜单
    public function delUserMenu()
    {
        $id = I('post.id');  //用户菜单id
        $exist = UserauthruleModel::isExistSonMenu($id);
        //2019-1-18 任梦龙：修改判断条件
        if (count($exist)) {
            $this->ajaxReturn(['status' => 'no', 'msg' => '删除失败，存在子菜单']);
        }
        $find = UserauthruleModel::getUserMenuInfo($id);
        if ($find['level'] == 0) {
            $level = '一级菜单';
        } elseif ($find['level'] == 1) {
            $level = '二级菜单';
        } else {
            $level = '三级菜单';
        }
        $msg = '删除用户的' . $level . '[' . $find['menu_title'] . ']:';
        $res = UserauthruleModel::deleteUserMenu($id);
        if ($res) {
            //删除用到该菜单的子账号菜单,先通过菜单id是否存在于菜单组中,得到用到此菜单的角色id组
            //得到所有用到该菜单的角色id，存入数组
            $user_group = M('userauthgroup')->field('id,rule_id')->where(['del' => 0])->select();
            $group_arr = [];   //存储角色id
            foreach ($user_group as $key => $val) {
                $rule_arr = explode(',', $val['rule_id']);
                foreach ($rule_arr as $ko => $vk) {
                    //如果菜单id存在其中，剔除菜单id，然后重新给角色赋予权限id组
                    if ($id == $vk) {
                        $group_arr[] = $val['id'];
                        unset($rule_arr[$ko]);
                        $new_rule = implode(',', $rule_arr);
                        M('userauthgroup')->where('id=' . $val['id'])->save(['rule_id' => $new_rule]);
                        break;
                    }
                }
            }
            //由于菜单文件存放的只是一级或者二级菜单，所以只有删除一级或者二级菜单时需要重新生成菜单文件,用户的菜单需要重新生成
            if($find['level'] != 2){
                $this->reMakeUserMenupath();
                //通过角色id查询出用到该角色的子账号id组
                if ($group_arr) {
                    foreach ($group_arr as $k => $v) {
                        $find = M('userauthgroupaccess')->where('group_id=' . $v['id'])->select();
                        if ($find) {
                            foreach ($find as $kk => $vo) {
                                $file_name = 'childmenujson-' . $vo['user_id'] . '-' . $vo['cid'];  //拼接文件名称
                                $file_name = hash('sha1', $file_name);  //文件名加盐哈希加密(逻辑需要考虑，先测试正常的文件名)
                                $file_path = C('USER_MENU_PATH') . $file_name . '.json';    //拼接文件路径
                                //如果存在子账号菜单文件则删除，而后重新生成文件并修改数据库
                                if (file_exists($file_path)) {
                                    unlink($file_path);
                                }
                                //重新生成菜单文件
                                $new_childmenu = UserauthgroupaccessModel::getUserRules($vo['user_id'], $vo['cid']);
                                file_put_contents($file_path, json_encode($new_childmenu));
                                M('childuser')->where(['id' => $vo['cid']])->save(['menu_json' => json_encode($new_childmenu), 'menu_path' => $file_path]);
                            }
                        }
                    }
                }
            }


            $this->addAdminOperate($msg . '删除成功');
            $this->ajaxReturn(['status' => 'ok', 'msg' => '删除成功']);
        } else {
            $this->addAdminOperate($msg . '删除失败');
            $this->ajaxReturn(['status' => 'no', 'msg' => '删除失败']);
        }

    }

    //查看操作---三级菜单
    public function viewUserOpt()
    {
        $id = I('get.id');
        //先判断是否存在操作
        $list_opt = UserauthruleModel::isExistSonMenu($id);
        if (!count($list_opt)) {
            $this->ajaxReturn(['status' => 'no', 'msg' => '该菜单还未添加任何操作',]);
        } else {
            $info = UserauthruleModel::getUserMenuInfo($id);  //获取单条记录
            $this->assign('info', $info);
            $this->assign('id', $id);
            $this->assign('opts', $list_opt);
            $this->display();
        }
    }

    //加载操作 --三级菜单
    public function loadUserOptList()
    {
        $pid = I('get.id');
        $menu_title = I('post.menu_title', '', 'trim');
        $where = [];
        $where[0] = 'pid=' . $pid;
        if ($menu_title) {
            $where[1] = "menu_title='" . $menu_title . "'";
        }
        $count = M('userauthrule')->where($where)->count();
        $list_opt = M('userauthrule')->where($where)->page(I('post.page', 1), I('post.limit', 10))->select();
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

    //批量软删除
    public function delAllUserMenu()
    {
        $id_str = I('post.idstr');
        if (!$id_str) {
            $this->ajaxReturn(['status' => 'no', 'msg' => '请选择菜单']);
        }
        $res = UserauthruleModel::deleteAllMenu($id_str);
        $msg = '批量删除用户的三级菜单:';
        if ($res) {
            $this->addAdminOperate($msg . '删除成功');
            $this->ajaxReturn(['status' => 'ok', 'msg' => '删除成功']);
        } else {
            $this->addAdminOperate($msg . '删除失败');
            $this->ajaxReturn(['status' => 'no', 'msg' => '删除失败']);
        }
    }

}