<?php
/**
 * Created by PhpStorm.
 * User: 任梦龙
 * Date: 2019/1/15/015
 * Time: 下午 6:15
 * 用户菜单模型层
 */

namespace Admin\Model;

use Think\Model;

class UserauthruleModel extends Model
{
    protected $_scope = array(
        // 命名范围normal
        'default_scope' => array(
            'field' => [
                "id"
                , "pid"
                , "menu_url"
                , "menu_title"
                , "is_menu"
                , "status"
                , "icon"
                , "level"
                , "del"
            ],
        ),
    );
    //自动验证
    protected $_validate = array(
        ['menu_title', 'require', '菜单名称不得为空', 0],
        ['menu_url', '', '该菜单url已经存在！', 0, 'unique', 0],
    );

    //添加用户菜单
    public static function addUserMenu($data)
    {
        return M('userauthrule')->add($data);
    }

    //修改菜单
    public static function updateUserMenu($data, $id)
    {
        return M('userauthrule')->where('id = ' . $id)->save($data);
    }

    //软删除单条菜单
    public static function deleteUserMenu($id)
    {
        return M('userauthrule')->where('id = ' . $id)->setField('del', 1);
    }

    //批量软删除菜单
    public static function deleteAllMenu($id_str)
    {
        $where['id'] = ['in', $id_str];
        return M('userauthrule')->where($where)->setField('del', 1);
    }

    //获取单条记录
    public static function getUserMenuInfo($id)
    {
        return M('userauthrule')->where('id = ' . $id)->find();
    }

    //软删除单条记录
    public static function changeDel($table_name, $id)
    {
        return M($table_name)->where('id = ' . $id)->setField('del', 1);
    }

    /**
     * 显示所有菜单
     * $type：1=显示一二级菜单；2=所有菜单
     */
    public static function selectAllMenu($type = 1)
    {
        $where = array(
//            'status' => 1,  //2019-1-17 任梦龙：将菜单状态移除
            'is_menu' => $type,
//            'del' => 0,
        );
        if ($type == 2) {
            unset($where['is_menu']);
        }
        return M('userauthrule')->where($where)->select();
    }

    /**
     * 判断菜单级别:通过id查询level的值
     * 0=一级；1=二级；2=三级
     * $menu_id：当前菜单id
     */
    public static function getUserMenuLevel($id)
    {
        return D('userauthrule')->getFieldById($id, 'level');
    }

    /**
     * 判断菜单名称是否已经存在
     */
    public static function findMenuTitle($where)
    {
        return M('userauthrule')->where($where)->count();
    }

    /**
     * 判断是否已经存在相同的菜单名称
     */
    public static function findMenuUrl($where_url)
    {
        return M('userauthrule')->where($where_url)->count();
    }

    /**
     * 判断是否存在子菜单
     */
    //2019-1-17 任梦龙：isExistSonMenu() 和 selectOpt() 这两个方法添加一致，所以现在只用isExistSonMenu()
    public static function isExistSonMenu($id)
    {
        $where = array(
            'pid' => $id,
//            'status' => 1,
            'del' => 0
        );
        return M('userauthrule')->where($where)->select();
    }

    /**
     * 查询用户的三级菜单  ，暂时不用
     */
    public static function selectOpt($id)
    {
        $where = array(
            'pid' => $id,
//            'status' => 1,
            'del' => 0
        );
        return M('userauthrule')->where($where)->select();
    }

    /**
     * 根据规则id数组获取菜单
     * 2019-1-22 再次添加
     */
    public function getMenus($rules_arr, $is_menu = 1) {
        $where = array(
            'id' => array('in', $rules_arr),
            'is_menu' => $is_menu,
            'del'=>0
        );
        return M('userauthrule')->where($where)->select();
    }


}