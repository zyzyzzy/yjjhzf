<?php

namespace Unfreeze\Model;

use Think\Model;
//2019-02-22汪桂芳创建
class UsermoneyModel extends Model
{
    //查询用户金额
    public static function getUsermoney($user_id)
    {
        return M('usermoney')->lock(true)->where('userid='.$user_id)->find();
    }

    //修改用户金额
    public static function saveUsermoney($freeze_order,$user_money)
    {
        $data = [
            'money'=>$user_money['money']+$freeze_order['freeze_money'],
            'freezemoney'=>$user_money['freezemoney']-$freeze_order['freeze_money']
        ];
        return M('usermoney')->lock(true)->where('userid='.$freeze_order['user_id'])->save($data);
    }
}