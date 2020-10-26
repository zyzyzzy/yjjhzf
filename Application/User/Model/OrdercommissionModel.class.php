<?php
namespace User\Model;

use Think\Model;

class OrdercommissionModel extends Model
{
    //查询某个字段
    public static function getCommissionField($sysordernumber,$filed)
    {
        return M('ordercommission')->where('sysordernumber="' . $sysordernumber.'"')->getField($filed);
    }

}