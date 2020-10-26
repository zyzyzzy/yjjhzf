<?php

/**
 * 管理员列表模型
 */

namespace Admin\Model;

use Think\Model;

class AdminuserModel extends Model
{
    protected $_scope = array(
        // 命名范围normal
        'default_scope' => array(
            'field' => [
                "id"
                , "user_name"
                , "bieming"
                , "lastlogin_time"
                , "status"
                , "del"
                , 'manage_status'
                , 'type'
                , 'super_admin'
                , 'same_admin'
                , 'privacy_id'
            ],
        ),
    );
    //自动完成
    protected $_auto = array(
        ['lastlogin_time', 'YmdHis', 1, 'function'],
        ['manage_pwd', 'md5', 1, 'function'],
        ['password', 'md5', 1, 'function'],
    );

    //自动验证
    protected $_validate = array(
        ['user_name', 'require', '管理员名称不能为空', 0],
        ['user_name', 'checkName', '管理员名称已被注册！', 0, 'callback', 3],
        ['user_name', 'patternName', '名称由大小写英文，数字组成，长度在6-20字符之间！', 0, 'callback', 3],
        ['bieming', 'require', '别名不能为空', 0],
//        ['bieming', '', '别名已存在！', 0, 'unique', 3],
        ['bieming', '2,20', '管理员别名最短2个字符，最长20个字符！', 0, 'length', 3],
        ['password', 'require', '密码不能为空', 0],
        ['password', '6,18', '密码长度最短6个字符，最长18个字符！', 0, 'length', 3],
        ['repassword', 'password', '确认密码不正确', 0, 'confirm'],
    );

    //模型判断管理员名称是否已经存在
    protected function checkName($name)
    {
        //通过是否有id判断是添加操作还是编辑操作  I('request.') ：可以获取所有的参数
        $id = I('request.id', '');
        if ($id) {
            $count = D('adminuser')->where(['user_name' => $name, 'del' => 0, 'id' => ['NEQ', $id]])->count();
        } else {
            $count = D('adminuser')->where(['user_name' => $name, 'del' => 0])->count();
        }
        if ($count) {
            return false;
        }
        return true;
    }

    //2019-4-17 rml：验证管理员名称格式：英文，数字
    protected function patternName($name)
    {
        $pattern = '/^[A-Za-z0-9]{6,20}$/';
        if (!preg_match($pattern, $name)) {
            return false;
        }
        return true;
    }



    /**
     * 根据管理员名和密码查询管理员
     */
    //2019-3-22 任梦龙：只读取指定的字段值
    public static function findUser($name, $pwd)
    {
        $where = array(
            'user_name' => $name,
            'password' => md5($pwd),
        );
//        return D('adminuser')->where($where)->field('id,user_name,bieming,lastlogin_time,super_admin,privacy_id,type,manage_status,status,del')->find();
        return D('adminuser')->where($where)->find();
    }

    /*
     * 根据管理员名称查询管理员
     * $user_name：管理员名称
     */
    public static function findAdminUserByName($user_name)
    {
        $where = array(
            'user_name' => $user_name,
            'status' => 1,
            'del' => 0
        );
        return M('adminuser')->where($where)->find();
    }

    /**
     * 根据管理员id，更新管理员登录时间
     */
    public static function updateLoginTime($id)
    {

        return M('adminuser')->where(['id' => $id])->setField('lastlogin_time', date('Y-m-d H:i:s'));
    }

    /********************************************************/
    //2019-1-14 任梦龙：添加真实删除数据方法
    public static function delActualAdmin($id)
    {
        return M('adminuser')->where(['id' => $id])->delete();
    }

    public static function delAllInfo($id_str)
    {
        return M("adminuser")->where(['id' => ['IN', $id_str]])->delete();
    }

    //2019-1-15 任梦龙：添加恢复单条记录方法
    public static function regainInfo($id)
    {
        return M('adminuser')->where(['id' => $id])->setField('del', 0);
    }

    public static function regainAllData($id_str)
    {
        return M("adminuser")->where(['id' => ['IN', $id_str]])->setField('del', 0);
    }


    /********************************************************/

    /**
     * 2019-1-21 任梦龙：查找管理员是否已经有分配隐私
     */
    public static function findPrivacyId($id)
    {
        return M('adminuser')->where(['id' => $id])->getField('privacy_id');
    }

    /**
     * 2019-2-13 任梦龙：获取密码
     */
    public static function getPassword($id)
    {
        return D('adminuser')->where(['id' => $id])->getField('password');
    }

    /**
     * 2019-2-13 任梦龙：修改密码
     */
    public static function editPassword($id, $data)
    {
        return D('adminuser')->where(['id' => $id])->save($data);
    }

    /**
     * 2019-2-14 任梦龙：获取账号名称
     */
    public static function getAdminName($id)
    {
        return D('adminuser')->where(['id' => $id])->getField('user_name');
    }

    /**
     * 2019-2-14 任梦龙：获取账号名称和别名
     */
    public static function getAdmin($id)
    {
        return D('adminuser')->where(['id' => $id])->field('user_name,bieming')->find();
    }

    /**
     * 2019-2-15 任梦龙：获取爱码农的管理员列表
     */
    public static function getAmnAdmin($where)
    {
        return D('adminuser')->where($where)->field('id,user_name')->select();
    }

    /**
     * 2019-2-26 任梦龙：获取单条记录
     */
    public static function getAdminiInfo($where)
    {
        return M('adminuser')->where($where)->find();
    }

    /**
     * 2019-2-27 任梦龙：根据id获取管理密码
     */
    public static function getManagePwd($id)
    {
        return M('adminuser')->where(['id' => $id])->getField('manage_pwd');
    }


    /**
     * 2019-3-6 任梦龙：根据管理员名称获取id
     */
    public static function getAdminId($admin_name)
    {
        return M('adminuser')->where(['user_name' => $admin_name])->getField('id');
    }

    /**
     * 2019-3-13 任梦龙：分页处理
     */
    public static function getPageLimit($where, $page, $limit)
    {
        $datalist = D('adminuser')->scope('default_scope')->where($where)->page($page, $limit)->order('id DESC')->select();
        return $datalist;
    }

    /**
     * 2019-3-13 任梦龙：获取中记录数(可以有条件)
     */
    public static function getCount($where)
    {
        return M('adminuser')->where($where)->count();
    }

    //2019-4-1 任梦龙：根据id获取同一账号设置的状态
    public static function getSameAdmin($id)
    {
        return M('adminuser')->where(['id' => $id])->getField('same_admin');
    }

    //2019-4-10 任梦龙：根据id获取管理密码的状态
    public static function getManageStatus($id)
    {
        return M('adminuser')->where(['id' => $id])->getField('manage_status');
    }

    public static function getAll()
    {
        return D('adminuser')->order('id DESC')->select();
    }

    //2019-8-27 rml:手机端：获取分页数据
    public static function getDatasPage($start = 0, $pagesize = 5, $where)
    {
        return D('adminuser')->where($where)->field('id,user_name,bieming,lastlogin_time')->order('id DESC')->limit($start, $pagesize)->select();
    }


}
