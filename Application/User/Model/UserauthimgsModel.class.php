<?php

namespace User\Model;

use Think\Model;

class UserauthimgsModel extends Model
{
    //查询用户认证信息
    public static function getUserAuth($user_id)
    {
        return D('userauthimgs')->where(['user_id' => $user_id])->find();
    }

    public static function saveUserAuth($user_id, $data)
    {
        return D('userauthimgs')->where(['user_id' => $user_id])->save($data);
    }

    public static function addUserAuth($data)
    {
        D('userauthimgs')->add($data);
    }

    public static function getFieldname($user_id,$field_name)
    {
        return D('userauthimgs')->where(['user_id' => $user_id])->getField($field_name);
    }


}