<?php
namespace Settlement\Model;


use Think\Model;

class SettleconfigModel extends Model
{
    public static function getInfo($user_id)
    {
        return D('settleconfig')->where([
            'user_id'=>['eq',$user_id],
        ])->find();
    }


}