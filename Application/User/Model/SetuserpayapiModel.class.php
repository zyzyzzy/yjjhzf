<?php

namespace User\Model;

use Think\Model;

//2019-4-3 任梦龙：用户指定通道模型
class SetuserpayapiModel extends Model
{
    public static function addUserpayapi($data)
    {
        M('setuserpayapi')->add($data);
    }

    public static function delUserpayapi($user_id)
    {
        M('setuserpayapi')->where(['user_id' => $user_id])->delete();
    }

    public static function getUserPayapi($user_id)
    {
        return M('setuserpayapi')->where(['user_id' => $user_id])->getField('user_payapi', true);
    }
}

