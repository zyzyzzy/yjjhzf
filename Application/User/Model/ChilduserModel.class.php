<?php
/**
 * 用户子账号模型层
 */

namespace User\Model;

use Think\Model;

class ChilduserModel extends Model
{
    protected $_scope = array(
        'default_scope' => array(
            'field' => "id,user_id,child_name,bieming,status,lastlogin_time,same_child",
        ),
    );

    //自动完成
    protected $_auto = array(
        ['lastlogin_time', 'YmdHis', 1, 'function'],
        ['child_pwd', 'md5', 1, 'function'],
        ['manage_pwd', 'md5', 1, 'function'],
    );
    //自动验证
    //2019-4-18 rml：修改
    protected $_validate = array(
        ['child_name', 'require', '子账号名称不能为空', 0],
        ['child_name', 'checkName', '子账号名称已被注册！', 0, 'callback', 3],
        ['child_name', 'patternName', '名称由大小写英文，数字组成，长度在2-20字符之间！', 0, 'callback', 3],
        ['bieming', 'require', '别名不能为空', 0],
        ['bieming', '2,20', '别名长度在2-20个字符之间！', 0, 'length', 3],
        ['child_pwd', 'require', '登录密码不能为空', 0],
        ['child_pwd', 'patternPwd', '登录密码由大小写英文，数字组成，长度在6-20字符之间！', 0, 'callback', 3],
        ['manage_pwd', 'require', '管理密码不能为空', 0],
        ['manage_pwd', 'patternPwd', '管理密码由大小写英文，数字组成，长度在6-20字符之间！', 0, 'callback', 3],
    );

    //验证子账号名称在表中是否重复
    protected function checkName($name)
    {
        $id = I('request.id', '');
        if ($id) {
            $count = D('childuser')->where(['child_name' => $name, 'del' => 0, 'id' => ['NEQ', $id]])->count();
        } else {
            $count = D('childuser')->where(['child_name' => $name, 'del' => 0])->count();
        }
        if ($count) {
            return false;
        }
        $user = D('user')->where(['username' => $name])->count();
        if ($user) {
            return false;
        }
        return true;
    }

    //2019-4-18 rml：修改格式为{2,20}
    protected function patternName($name)
    {
        $pattern = '/^[A-Za-z0-9]{2,20}$/';
        if (!preg_match($pattern, $name)) {
            return false;
        }
        return true;
    }

    //2019-4-18 rml：验证密码格式
    protected function patternPwd($name)
    {
        $pattern = '/^[A-Za-z0-9]{6,20}$/';
        if (!preg_match($pattern, $name)) {
            return false;
        }
        return true;
    }

    /**
     * 2019-3-15 任梦龙：封装根据id获取子账号单条记录
     * @param $id
     * @return mixed
     */
    public static function getChildInfo($id)
    {
        return M('childuser')->where(['id' => $id])->find();
    }

    //2019-1-18 任梦龙：获取子账号的名称
    public static function getChildName($id)
    {
        return D('childuser')->where(['id' => $id])->getField('child_name');
    }

    /**
     * 2019-1-21 任梦龙：将菜单文件存入数据库
     */
    //2019-1-22 任梦龙：修改
    public static function getMenuJson($id, $data)
    {
        return D('childuser')->where(['id' => $id])->save($data);
    }


    /**
     * 2019-2-12 任梦龙：封装查询子账号方法
     */
    //2019-3-27 任梦龙：修改
    public static function getChildUser($where)
    {
        return M('childuser')->where($where)->field('id,user_id,child_name,bieming,child_pwd,status,lastlogin_time,del')->find();
    }

    /**
     * 2019-2-12 任梦龙：封装修改登录时间方法
     */
    public static function saveLoginTime($id)
    {
        M('childuser')->where(['id' => $id])->setField('lastlogin_time', date('Y-m-d H:i:s'));
    }

    /**
     * 2019-3-14 任梦龙：验证登陆密码
     */
    public static function verifyLoginpwd($child_id, $login_pwd)
    {
        $child_pwd = self::getUserLoginpwd($child_id);
        if (md5($login_pwd) == $child_pwd) {
            return true;
        }
        return false;
    }

    /**
     * 2019-3-14 任梦龙：验证登陆密码
     */
    public static function verifyManagepwd($child_id, $manage_pwd)
    {
        $child_manage = self::getUserManagepwd($child_id);
        if (md5($manage_pwd) == $child_manage) {
            return true;
        }
        return false;
    }

    /**
     * 2019-3-19 任梦龙：修改密码(登陆密码与管理密码)
     */
    public static function editUserPwd($id, $pwd_type, $new_pwd)
    {
        return M('childuser')->where(['id' => $id])->setField($pwd_type, $new_pwd);
    }

    /**
     * 2019-3-20 任梦龙：查询该用户下的子账号列表
     */
    public static function getChildUserlist($user_id)
    {
        $where = [
            'user_id' => $user_id,
//            'del' => 0
        ];
        return M('childuser')->field('id,child_name,bieming')->where($where)->select();
    }

    //2019-3-25 任梦龙：根据id获取登录密码
    public static function getUserLoginpwd($child_id)
    {
        return M('childuser')->where(['id' => $child_id])->getField('child_pwd');
    }

    //2019-3-25 任梦龙：根据id获取管理密码
    public static function getUserManagepwd($child_id)
    {
        return M('childuser')->where(['id' => $child_id])->getField('manage_pwd');
    }

    //2019-4-1 任梦龙：根据id获取同一账号设置状态
    public static function getSameChild($child_id)
    {
        return M('childuser')->where(['id' => $child_id])->getField('same_child');
    }
}