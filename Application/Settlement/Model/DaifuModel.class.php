<?php
namespace Settlement\Model;

use Think\Model;

class DaifuModel extends Model
{
    public static function getInfo($id)
    {
        return D('daifu')->find($id);
    }
}