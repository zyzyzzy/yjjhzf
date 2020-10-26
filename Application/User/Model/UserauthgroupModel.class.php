<?php
/**
 * 用户角色模型层
 */

namespace User\Model;

use Think\Model;

class UserauthgroupModel extends Model
{
    //2019-3-27 任梦龙：将rule_id移除，到时候页面上审查元素时就看不到,避免泄露信息
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
        ['auth_name', 'checkName', '角色名称已被注册！', 0, 'callback', 3],
        ['auth_name', '2,20', '角色名称长度在2-20字符之间！', 0, 'length', 3],
    );

    //查询该用户内角色名称是否重复
    protected function checkName($name)
    {
        $id = I('request.id', 0);
        $user_id = I('request.user_id', 0);
        if (!$id) {
            $count = D('userauthgroup')->where(['auth_name' => $name, 'user_id' => $user_id])->count();
        } else {
            $count = D('userauthgroup')->where(['auth_name' => $name, 'id' => ['NEQ', $id], 'user_id' => $user_id])->count();
        }
        if ($count) {
            return false;
        }
        return true;
    }

    /**
     * 修改权限id组
     * @param $id :用户角色id
     * @param $rule_id :分配的权限id组
     * @return bool
     */
    public static function editRuleId($id, $rule_id)
    {
        return M('userauthgroup')->where('id = ' . $id)->save(['rule_id' => $rule_id]);
    }

    /**
     * 获取角色列表
     * $id:用户子账号id
     * $user_id：用户id
     */
    public static function getUserGroupList($id = 0, $user_id)
    {
        //读取表前缀
        $pre = C('DB_PREFIX');
        $where = array(
            'status' => 1,
            'del' => 0,
            'user_id' => $user_id,
        );
        if (!$id) {
            $list = M('userauthgroup')->where($where)->order('is_manage DESC,id DESC')->select();
        } else {
            $where = array(
                'a.status' => 1,
                'a.del' => 0,
                'a.user_id' => $user_id,
            );
            $field = "a.*,b.cid";
            $join = 'LEFT JOIN ' . $pre . 'userauthgroupaccess as b ON a.id=b.group_id AND b.cid=' . $id;
            $list = M('userauthgroup')->alias('a')->field($field)->join($join)->where($where)->order('a.is_manage DESC,a.id DESC')->select();
        }
        return $list;
    }

    //2019-3-15 任梦龙：子账号列表应该只有主用户才能有,所以在为子账号分配角色时，直接应该是显示所有的角色列表
    /**
     * 2019-3-15  任梦龙：获取当前这个主用户设置的角色列表
     */
    //2019-3-27 任梦龙：获取这个用户拥有的状态正常且未删除的角色列表
    public static function getAuthGrouplist($user_id)
    {
        $where = [
            'user_id' => $user_id,
            'status' => 1,
//            'del' => 0
        ];
        return M('userauthgroup')->where($where)->select();
    }

    //根据id获取角色记录
    public static function getUseauthgroup($id)
    {
        return M('userauthgroup')->where(['id' => $id])->find();
    }

    /**
     * 根据id删除记录
     * @param $id
     * @param $type_name：字段名称
     * @param $type_val：字段值
     * @return bool
     */
    public static function editUserauthType($id, $type_name, $type_val)
    {
        return M('userauthgroup')->where(['id' => $id])->setField($type_name, $type_val);
    }

    //2019-3-27 任梦龙：根据id获取角色名称
    public static function getUserauthNnme($id)
    {
        return M('userauthgroup')->where('id=' . $id)->getField('auth_name');
    }

    //2019-3-27 任梦龙：根据id获取权限组id
    public static function getUserRuleid($id)
    {
        return M('userauthgroup')->where(['id' => $id])->getField('rule_id');
    }

    //2019-4-10 任梦龙：真删除用户角色
    public static function delUserAuthGroup($id)
    {
        return M('userauthgroup')->where(['id' => $id])->delete();
    }

    public static function saveUserAuthGroup($id, $data)
    {
        return D('userauthgroup')->where(['id' => $id])->save($data);
    }


}