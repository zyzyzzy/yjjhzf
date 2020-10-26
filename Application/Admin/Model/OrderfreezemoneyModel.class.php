<?php
namespace Admin\Model;

use Think\Model\RelationModel;

class OrderfreezemoneyModel extends RelationModel
{
    protected $_link =[
        "manualunfreeze" => [
            'mapping_type' => self::HAS_MANY,
            'mapping_name' => 'manualunfreeze',
            'foreign_key' => 'freezemoney_id'
        ],
        "delayunfreeze" => [
            'mapping_type' => self::HAS_MANY,
            'mapping_name' => 'delayunfreeze',
            'foreign_key' => 'freezemoney_id'
        ],


    ];

    //查找单条记录
    public static function getInfo($id)
    {
        return  M('orderfreezemoney')->where('id='.$id)->find();
    }

    //查询冻结订单号
    public static function getFreezeordernumber($id)
    {
        return  M('orderfreezemoney')->where('id='.$id)->getField('freezeordernumber');
    }

    //查询预计解冻时间
    public static function getExpectTime($id)
    {
        return  M('orderfreezemoney')->where('id='.$id)->getField('expect_time');
    }

    //查询订单号相关信息
    public static function getOrdernumber($id)
    {
        return M('orderfreezemoney')->where('id='.$id)->field('freezeordernumber,sysordernumber')->find();
    }
}