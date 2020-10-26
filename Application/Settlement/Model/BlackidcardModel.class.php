<?php
namespace Settlement\Model;

use Think\Model;

class BlackidcardModel extends Model
{
    public static function getIdcard($idcard)
    {
        return D('blackidcard')->where([
            "idcard"=>['eq',$idcard]
        ])->find();
    }
}