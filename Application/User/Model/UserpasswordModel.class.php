<?php
/**
 * 2019-3-14 任梦龙：用户后台
 * 用户密码模型
 */

namespace User\Model;

use Think\Model;

class UserpasswordModel extends Model
{
    /**
     * 验证登陆密码
     */
    public static function verifyLoginpwd($user_id, $login_pwd)
    {
        $user_login = self::getLoginPwd($user_id);
        if (md5($login_pwd) == $user_login) {
            return true;
        }
        return false;
    }

    /**
     * 验证登陆密码
     */
    public static function verifyManagepwd($user_id, $manage_pwd)
    {
        $user_manage = self::getManagePwd($user_id);
        if (md5($manage_pwd) == $user_manage) {
            return true;
        }
        return false;
    }

    /**
     * 2019-3-18 任梦龙：根据用户id获取管理密码
     */
    public static function getManagePwd($user_id)
    {
        return M('userpassword')->where('userid=' . $user_id)->getField('manage_pwd');
    }

    /**
     * 2019-3-19 任梦龙：根据用户id获取登陆密码
     */
    public static function getLoginPwd($user_id)
    {
        return M('userpassword')->where('userid=' . $user_id)->getField('loginpassword');
    }

    /**
     * 2019-3-19 任梦龙:修改密码：登陆密码和管理密码
     */
    public static function editUserpwd($user_id, $data)
    {
        return M('userpassword')->where('userid=' . $user_id)->save($data);
    }


}

