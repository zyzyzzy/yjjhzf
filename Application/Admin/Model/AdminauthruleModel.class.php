<?php

/*

 * user 任梦龙

 * time 2018/11/14 17:55

 * 菜单列表模型

 */



namespace Admin\Model;

use Think\Model;

//2019-3-22 任梦龙：修改代码

//2019-4-10 任梦龙：修改为真删除

class AdminauthruleModel extends Model
{

    //自动验证

    protected $_validate = array(

        ['menu_title', 'require', '菜单名称不得为空', 0],

        ['menu_url', '', '该菜单url已经存在！', 0, 'unique', 0],//2019-4-19 rml:修改为"3全部情况下验证"

    );



    /**

     * 显示所有菜单

     * $type：1=显示一二级菜单；2=所有菜单

     */

    public static function selectAllMenu($type = 1)
    {
        $where['is_menu'] = 1;

        if ($type == 2) {
            unset($where['is_menu']);
        }

        return M('adminauthrule')->where($where)->select();
    }



    /**

     * 查询三级菜单

     */

    public function selectOpt($id)
    {
        return M('adminauthrule')->where(['pid' => $id])->select();
    }



    /*

     * 获取一条信息

     */

    public static function getOneInfo($menu_id)
    {
        return D('adminauthrule')->where(['id' => $menu_id])->find();
    }



    /*

     * 删除菜单

     */

    public static function menuDel($id)
    {
        return D('adminauthrule')->where('id=' . $id)->delete();
    }



    /*

     * 判断菜单级别:通过id查询level的值

     * 0=一级；1=二级；2=三级

     */

    public static function MenuLevel($id)
    {
        return M('adminauthrule')->where(['id' => $id])->getField('level');
    }



    /**

     * 根据规则id数组获取菜单

     */

    public function getMenus($rules_arr, $is_menu = 1)
    {
        $where = array(

            'id' => array('in', $rules_arr),

            'is_menu' => $is_menu,

        );

        return M('adminauthrule')->where($where)->select();
    }



    /**

     * 2019-3-22 任梦龙：根据id获取菜单名称

     */

    public static function getMenutitle($id)
    {
        return M('adminauthrule')->where('id=' . $id)->getField('menu_title');
    }



    /**

     * 2019-3-26 任梦龙：批量删除三级菜单

     */

    public static function delAllMenu($id_str)
    {
        return D('adminauthrule')->where(['id' => ['in', $id_str]])->delete();
    }



    public static function getParents()
    {
        return D('adminauthrule')->where([

            'pid'=>['eq',0]

        ])->select();
    }



    public static function getChilds($pid)
    {
        return D('adminauthrule')->where([

            'pid'=>['eq',$pid]

        ])->select();
    }



    public static function getCountChilds($pid)
    {
        return D('adminauthrule')->where([

            'pid'=>['eq',$pid]

        ])->count();
    }



    public static function getMenu($pid)
    {
        $parents = static::getChilds($pid);

        $arr = [];

        if (count($parents)>0) {
            foreach ($parents as $key=>$parent) {
                $arr[$key]=$parent;

                if ($parent['level']<2) {
                    $childs = static::getMenu($parent['id']);

                    if (count($childs)>0) {
                        $arr[$key]['childs']=static::getMenu($parent['id']);
                    }
                }
            }
        }

        return $arr;
    }



    public static function getMenuIds($pid)
    {
        $parents = static::getChilds($pid);

        $arr = [];

        if (count($parents)>0) {
            foreach ($parents as $key=>$parent) {
                $arr[]=$parent['id'];

                if ($parent['level']<2) {
                    $childs = static::getMenuIds($parent['id']);

                    if (count($childs)>0) {
                        $arr=array_merge($arr, static::getMenuIds($parent['id']));
                    }
                }
            }
        }

        return $arr;
    }



    public static function getChildIds($pid)
    {
        return D('adminauthrule')->where([

            'pid'=>['eq',$pid]

        ])->getField('id', true);
    }
}
