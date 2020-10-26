<?php
namespace Settlement\Model;

use Think\Model;

class BlacktelModel extends Model
{
    public static function getTel($tel)
    {
        return D('blacktel')->where("tel='".$tel."'")->find();
    }
}