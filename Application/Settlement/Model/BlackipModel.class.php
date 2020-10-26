<?php
namespace Settlement\Model;

use Think\Model;

class BlackipModel extends Model
{
    public static function getIp($ip)
    {
        return D('blackip')->where([
            "ip"=>['eq',$ip]
        ])->count();
    }
}