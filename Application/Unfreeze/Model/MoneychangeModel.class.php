<?php

namespace Unfreeze\Model;

use Think\Model;
//2019-02-22汪桂芳创建
class MoneychangeModel extends Model
{
    //添加资金变动记录
    public static function addMoneychange($freeze_order,$user_money)
    {
        $data = [
            'userid'=>$freeze_order['user_id'],
            'oldmoney'=>$user_money['money'],
            'changemoney'=>$freeze_order['freeze_money'],
            'nowmoney'=>$user_money['money']+$freeze_order['freeze_money'],
            'datetime'=>date('Y-m-d H:i:s'),
            'transid'=>$freeze_order['sysordernumber'],
            'changetype'=>3,//unfreeze解冻
            'remarks'=>'冻结订单'.$freeze_order['freezeordernumber'].'自动解冻成功'
        ];
        return M('moneychange')->lock(true)->add($data);
    }
}