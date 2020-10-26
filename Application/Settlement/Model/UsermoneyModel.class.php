<?php
namespace Settlement\Model;


use Think\Model;

class UsermoneyModel extends Model
{
    public static function getInfo($userid)
    {
        return D('usermoney')->lock(true)->where([
            'userid'=>['eq',$userid]
        ])->find();
    }
}