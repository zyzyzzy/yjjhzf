<?php

namespace Admin\Model;

use Think\Model;

class UsercommissionsetModel extends Model
{
    //查询用户数据
    public static function getCommission($user_id)
    {
        return M('usercommissionset')->where('user_id='.$user_id)->find();
    }

    //修改记录
    public static function saveInfo($user_id,$data)
    {
        return M('usercommissionset')->where('user_id='.$user_id)->save($data);
    }

    //添加记录
    public static function addInfo($data){
        return M('usercommissionset')->add($data);
    }
}