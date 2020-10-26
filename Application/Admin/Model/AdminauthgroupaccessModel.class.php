<?php

/*
 * user 任梦龙
 * time 2018/11/17 15:25
 * 用户-角色关系模型
 */

namespace Admin\Model;

use Think\Model;

class AdminauthgroupaccessModel extends Model
{
    /*
     * 根据用户id删除数据
     */
    public static function delUid($user_id)
    {
        M('adminauthgroupaccess')->where('uid = ' . $user_id)->delete();
    }

    //根据角色id删除数据
    public static function delGroup($group_id)
    {
        M('adminauthgroupaccess')->where(['group_id' => $group_id])->delete();
    }


    /*
     * 添加用户与角色关系组
     */
    public static function addUserAuthGroup($data)
    {
        M('adminauthgroupaccess')->add($data);
    }

    /**
     * 获取用户所有权限
     */
    //2019-3-21 任梦龙：直接生成配置菜单栏形式的菜单信息
    public static function getUserRules($admin_id)
    {
        //读取表前缀
        $pre = C('DB_PREFIX');
        $where = array(
            'a.uid' => $admin_id,
        );
        $join = 'LEFT JOIN ' . $pre . 'adminauthgroup as b ON b.id=a.group_id';
        $rules = D('adminauthgroupaccess')->alias('a')
            ->where('a.uid=' . $admin_id)
            ->join('LEFT JOIN __ADMINAUTHGROUP__ b ON b.id=a.group_id')
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
        $admin_menu_model = new AdminauthruleModel();
        $menus = $admin_menu_model->getMenus($rules_arr);
        $menus = get_column($menus, 2);
        //通过循环，转换为配置文件的形式
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
     * 2019-3-21 任梦龙：根据角色id获取用户id组
     */
    public static function getUid($group_id)
    {
        return M('adminauthgroupaccess')->where(['group_id' => $group_id])->getField('uid', true);
    }

    /**
     * 2019-3-21 任梦龙：根据管理员id获取角色组
     */
    public static function getGroupid($admin_id)
    {
        return M('adminauthgroupaccess')->where(['uid' => $admin_id])->getField('group_id', true);
    }


}
