<?php
/*
 * user 任梦龙
 * time 2018/11/12 12:37
 * 角色列表模型
 */

namespace Admin\Model;

use Think\Model;

class AdminauthgroupModel extends Model
{
    protected $_scope = array(
        // 命名范围normal
        'default_scope' => array(
            'field' => [
                "id"
                , "auth_name"
//                , "rule_id"
                , "is_manage"
                , "status"
//                , "del"
            ],
        ),
    );
    //自动验证
    protected $_validate = array(
        ['auth_name', 'require', '角色名称不能为空', 0],
        ['auth_name', '', '角色名称已被注册！', 0, 'unique', 3],
        ['auth_name', '2,20', '角色名称最短2个字符，最长20个字符！', 0, 'length', 3],
    );

    /**
     * 根据id查找角色信息
     */
    public static function findRuleGroup($role_id)
    {
        $where = array(
            'id' => $role_id,
            'status' => 1,
//            'del' => 0
        );
        return M('adminauthgroup')->where($where)->find();
    }

    /**
     * 2019-3-21 任梦龙：根据角色id获取该角色的权限值
     * @param int $user_id
     * @return array
     */
    public static function getRuleid($id)
    {
        return M('adminauthgroup')->where(['id' => $id])->getField('rule_id');
    }


    /*
     * 获取角色列表
     */
//    public static function getGroupList($user_id = 0)
//    {
//        //读取表前缀
//        $pre = C('DB_PREFIX');
//        $where = array(
//            'status' => 1,
//            'del' => 0
//        );
//        if (!$user_id) {
//            $list = M('adminauthgroup')->where($where)->order('is_manage DESC,id DESC')->select();
//        } else {
//            $where = array(
//                'a.status' => 1,
//            );
//            $field = "a.*,b.uid";
//            $join = 'LEFT JOIN ' . $pre . 'adminauthgroupaccess as b ON a.id=b.group_id AND b.uid=' . $user_id;
//            $list = M('adminauthgroup')->alias('a')->field($field)->join($join)->where($where)->order('a.is_manage DESC,a.id DESC')->select();
//        }
//        return array(
//            'list' => $list
//        );
//    }

    /*
     * 通过角色id查询一条信息
     */
    public static function getAuthGroup($role_id)
    {
        return M('adminauthgroup')->find($role_id);
    }

    /*
     * 获取角色表里的id和角色名
     */
    public static function getGroupInfo()
    {
        return M('adminauthgroup')->field(['id', 'auth_name'])->where(['status' => 1])->select();  //二维数组
    }

    /**
     * 2019-3-21 任梦龙：修改角色信息
     */
    public static function editAuthgroup($id, $data)
    {
        return M('adminauthgroup')->where(['id' => $id])->save($data);
    }

    /**
     * 2019-3-13 任梦龙：通过id只查询名称
     */
    public static function getAuthname($id)
    {
        return M('adminauthgroup')->where('id=' . $id)->getField('auth_name');
    }

    /**
     * 2019-3-13 任梦龙：通过id改变del的值（软删除）
     */
    public static function delAuthgroup($id)
    {
        return M('adminauthgroup')->where('id=' . $id)->setField('del', 1);
    }

    //2019-4-10 任梦龙：真删除角色
    public static function delRealAuth($id)
    {
        return M('adminauthgroup')->where(['id' => $id])->delete();
    }

    /**
     * 2019-4-10 rml：修改字段值
     * @param $id
     * @param $type_name ：字段名称
     * @param $type_val ：字段值
     */
    public static function editAuthStatus($id, $type_name, $type_val)
    {
        return M('adminauthgroup')->where(['id' => $id])->setField($type_name, $type_val);
    }

    public static function getGroupArr()
    {
        return M('adminauthgroup')->where(['status' => 1])->field('id,rule_id')->select();
    }

    public static function getInfo($id)
    {
        return D('adminauthgroup')->find($id);
    }

    //2019-8-27 rml:获取手机端分页数据
    public static function getDatasPage($start = 0, $pagesize = 5)
    {
        return D('adminauthgroup')->field('id,auth_name,status')->order('id DESC')->limit($start, $pagesize)->select();
    }

    //计算总记录数据
    public static function getCount()
    {
        return D('adminauthgroup')->count();
    }


}
