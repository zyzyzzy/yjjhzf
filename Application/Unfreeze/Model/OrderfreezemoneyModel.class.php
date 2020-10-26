<?php

namespace Unfreeze\Model;

use Think\Model;
//2019-02-22汪桂芳创建
class OrderfreezemoneyModel extends Model
{
    //查询冻结订单
    public static function getOrderfreezemoney($freezeordernumber)
    {
        return M('orderfreezemoney')->lock(true)->where('freezeordernumber='.$freezeordernumber)->find();
    }

    //修改冻结订单记录
    public static function saveOrderfreezemoney($freezeordernumber)
    {
        $data = [
            'actual_time'=>date('Y-m-d H:i:s'),
            'unfreeze'=>1,//已解冻
            'unfreeze_type'=>1//自动解冻
        ];
        return M('orderfreezemoney')->lock(true)->where('freezeordernumber='.$freezeordernumber)->save($data);
    }
}