<?php
namespace Daifu\Model;

use Think\Model;

class BlackbanknumModel extends Model
{
    public static function getBanks($banknum)
    {
        return D('blackbanknum')->where([
            "bank_num"=>['eq',$banknum]
        ])->find();
    }
}