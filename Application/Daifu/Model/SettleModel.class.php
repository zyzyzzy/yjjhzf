<?php
namespace Daifu\Model;


use Think\Model;

class SettleModel extends Model
{
    public static function checkRepeat($ordernumber,$userid)
    {
       return D('settle')->where([
            'userordernumber' => ['eq', $ordernumber],
            'userid' => ['eq', $userid],
        ])->count();

    }

    public static function getCountSettleByUser($userid)
    {
        $settleIds = D('settle')->where("userid='".$userid."' and to_days(applytime) = to_days(now())")->getField('id', true);
        return count($settleIds);
    }

    public static function getSumMoneyByUser($userid)
    {
        return  D('settle')->where("userid='".$userid."' and to_days(applytime) = to_days(now())")->sum('ordermoney');
    }

    public function getInfoByOrdernumber($ordernumber)
    {
        return  D('settle')->where([
            'ordernumber' => ['eq', $ordernumber],
        ])->find();
    }

    public static function getInfoByUserordernumber($userordernumber)
    {
        return  D('settle')->where([
            'userordernumber' => ['eq', $userordernumber],
        ])->find();
    }

     public static function updateSettleStatus($sysordernumber,$status,$remarks='')
     {
         $find = D('settle')->where(['ordernumber'=>array('eq',$sysordernumber)])->find();
         D('settle')->where([
             'ordernumber'=>array('eq',$sysordernumber)
         ])->save([
             'status'=> $status,
             'dealtime' => date('Y-m-d H:i:s')
         ]);
         D('settlestatuslist')->add([
             'userid' => $find['userid'],
             'sysordernumber' => $sysordernumber,
             'userordernumber' => $find['userordernumber'],
             'datetime' => date('Y-m-d H:i:s'),
             'oldstatus' => $find['status'],
             'newstatus' => $status,
             'remarks' => $remarks,
         ]);
         return true;
     }
}