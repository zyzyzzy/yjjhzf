<?php
/**
 *2019-4-11 rml：用户可用余额
 */

namespace Admin\Model;

use Think\Model;

class UsermoneyMode extends Model
{
    public static function getCount($user_id)
    {
        return M('usermoney')->lock(true)->where(['userid' => $user_id])->count();
    }

    public static function findInfo($user_id)
    {
        return D('usermoney')->where(['userid' => $user_id])->find();
    }

    public static function saveUsermoney($user_id, $data)
    {
        return D('usermoney')->lock(true)->where(['userid' => $user_id])->save($data);
    }

    public static function addUsermoney($data)
    {
        return D('usermoney')->lock(true)->add($data);
    }

}
