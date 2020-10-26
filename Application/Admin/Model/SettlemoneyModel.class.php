<?php
namespace Admin\Model;

use Think\Model;

class SettlemoneyModel extends Model
{
    public static function getInfo($ordernumber)
    {
        return D('settlemoney')->where(["ordernumber"=>["eq",$ordernumber]])->find();
    }
}