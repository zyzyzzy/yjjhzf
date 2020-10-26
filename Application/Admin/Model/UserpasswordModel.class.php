<?php

namespace Admin\Model;

use Think\Model;

class UserpasswordModel extends Model
{

    //2019-3-8 任梦龙：将支付密码修改为管理密码
    //2019-4-22 rml：验证密码格式
    protected $_validate = array(
        ['loginpassword', 'require', '用户登录密码不能为空', 0],
        ['loginpassword', 'patternPwd', '用户登录密码由大小写英文，数字组成，长度在6-20字符之间！', 0,'callback',3],
        ['manage_pwd', 'require', '用户管理密码不能为空', 0],
        ['manage_pwd', 'patternPwd', '用户管理密码由大小写英文，数字组成，长度在6-20字符之间！', 0,'callback',3],
        ['userid', 'require', '用户信息有误', 0],
    );

    //2019-3-8 任梦龙：自动完成,在新增时将登录密码和管理密码MD5加密处理
    protected $_auto = array(
        ['loginpassword', 'md5', 1, 'function'],
        ['manage_pwd', 'md5', 1, 'function'],
    );

    protected function patternPwd($name)
    {
        $pattern = '/^[A-Za-z0-9]{6,20}$/';
        if (!preg_match($pattern, $name)) {
            return false;
        }
        return true;
    }


    public static function getUserPassword($userid)
    {
        return D('userpassword')->where("userid='" . $userid . "'")->find();
    }

    public static function verify($userid, $loginpassword, $paypassword)
    {
        $userpassword = static::getUserPassword($userid);
        if (md5($loginpassword) == $userpassword['loginpassword'] && md5($paypassword) == $userpassword['paypassword']) {
            return true;
        }
        return false;
    }

    public static function editPassword($userid, $loginpassword, $paypassword)
    {
        return D('userpassword')->where("userid='" . $userid . "'")->setField([
            'loginpassword' => md5($loginpassword),
            'paypassword' => md5($paypassword),
        ]);
    }

    /**
     * 2019-3-14 任梦龙：修改登录密码
     */
    public static function saveLoginPwd($user_id, $data)
    {
        return M('userpassword')->where('userid=' . $user_id)->save($data);
    }

    //2019-3-25 任梦龙：删除用户密码记录
    public static function delUserpwd($user_id)
    {
        M('userpassword')->where('userid=' . $user_id)->delete();
    }
}

