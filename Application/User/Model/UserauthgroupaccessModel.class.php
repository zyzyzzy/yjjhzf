<?php

/**
 * 用户的子账号--角色关系表
 */

namespace User\Model;

use Think\Model;

class UserauthgroupaccessModel extends Model
{
    /*
     * 根据用户id删除数据
     */
    public static function delcid($id, $user_id)
    {
        M('userauthgroupaccess')->where(['cid' => $id, 'user_id' => $user_id])->delete();
    }


    /**
     * 通过主用户id和角色id删除所有用到该角色的子账号
     * @param $user_id
     * @param $group_id：角色id
     */
    public static function delGroupId($user_id, $group_id)
    {
        M('userauthgroupaccess')->where(['group_id' => $group_id, 'user_id' => $user_id])->delete();
    }

    //判断该子账号原来是否有分配角色
    //2019-3-15 任梦龙：修改，获取这个主用户为这个子账号分配的角色
    public static function findUserGroupAccess($id, $user_id)
    {
        return M('userauthgroupaccess')->where(['cid' => $id, 'user_id' => $user_id])->getField('group_id', true);
    }


    /*
     * 添加用户与角色关系组
     */
    public static function addUserAuthGroup($data)
    {
        M('userauthgroupaccess')->add($data);
    }

    /**
     * 如果是子账号登录，获取子账号的权限:树状递归
     * @param $user_id ：用户主账号id
     * @param string $child_id ：用户子账号id
     * @return array|mixed|\multitype
     */
    public static function getUserRules($user_id, $child_id)
    {
        //读取表前缀
//        $pre = C('DB_PREFIX');
        //如果是子账号登录
        $where = array(
            'a.cid' => $child_id,
            'a.user_id' => $user_id,
            'b.status' => 1,
//            'b.del' => 0,
        );
//        $join = 'LEFT JOIN ' . $pre . 'userauthgroup b ON b.id=a.group_id';
        //得到每个角色拥有的权限id
        $rules = M('userauthgroupaccess')->alias('a')
            ->where($where)
            ->join('__USERAUTHGROUP__ b ON b.id=a.group_id')
            ->field('b.rule_id')
            ->select();
        if (!$rules) {
            return array();
        }
        $rules_str = '';
        foreach ($rules as $v) {
            $rules_str .= $v['rule_id'] . ',';
        }
        $rules_str = rtrim($rules_str, ',');
        $rules_arr = array_unique(explode(',', $rules_str));
        $user_menu_model = new \Admin\Model\UserauthruleModel();
        $menus = $user_menu_model->getMenus($rules_arr);
        $menus = get_column($menus, 2);
        //将菜单形式转换为配置文件形式的数组
        $one_menu = [];
        foreach ($menus as $key => $val) {
            $one_menu[$val['menu_title']]['icon'] = html_entity_decode($val['icon']);
            foreach ($val[$val['id']] as $k => $v) {
                $one_menu[$val['menu_title']]['menu'][$v['menu_title']] = $v['menu_url'];
            }
        }
        return $one_menu;
    }

    /**
     * 如果是主账号登录，则直接查找账号的一级和二级菜单
     */
    public static function getAllUserMenu()
    {
//        $where = [
//            'status' => 1,
//            'is_menu' => 1,
//            'del' => 0
//        ];
        $all_menus = M('userauthrule')->where(['is_menu' => 1])->select();
        $menus = get_column($all_menus, 2);
        //2019-1-21 任梦龙：将菜单形式转换为配置文件形式的数组
        $one_menu = [];
        foreach ($menus as $key => $val) {
            $one_menu[$val['menu_title']]['icon'] = html_entity_decode($val['icon']);
            foreach ($val[$val['id']] as $k => $v) {
                $one_menu[$val['menu_title']]['menu'][$v['menu_title']] = $v['menu_url'];
            }
        }
        return $one_menu;
    }

    /**
     * 2019-3-27 任梦龙：根据主用户id和角色id查找所有用到该角色的子账号id
     */
    //2019-4-29 rml：修改
    public static function getChildId($user_id, $group_id)
    {
        return M('userauthgroupaccess')->where(['group_id' => $group_id, 'user_id' => $user_id])->getField('cid',true);
    }

}
