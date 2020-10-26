<?php

namespace User\Model;

use Think\Model;

//整Model2019-03-15汪桂芳创建
class SettleModel extends Model
{
    //获取用户订单号
    //2019-5-8 rml:对于随机数而言，时间加8为随机数一般不会重复.
    public static function getUserOrdernumber($user_id)
    {
        $user_ordernumber = date('YmdHis') . randpw(8, 'NUMBER');
//        $res = M('settle')->where('userid=' . $user_id . ' and userordernumber="' . $user_ordernumber . '"')->select();
//        if ($res) {
//            return self::getUserOrdernumber($user_id);
//        }
        return $user_ordernumber;
    }

    //最新结算订单
    public static function getUserLatestSettle($userid, $limit = 15)
    {
        return D('settleinfo')->where([
            'userid' => ['eq', $userid]
        ])->order('settleid DESC')->limit($limit)->select();
    }

    //获取可用余额
    public static function getSumOrderMoney($where)
    {
        return D('settle')->where($where)->sum('ordermoney');
    }

    //获取结算剩余次数
    public static function getOrderCount($where)
    {
        return D('settle')->where($where)->count();
    }

}