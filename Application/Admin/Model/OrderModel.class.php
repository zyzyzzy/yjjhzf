<?php
/*
 * 交易记录模型
 */
namespace Admin\Model;

use Think\Model;

class OrderModel extends Model
{
    public static function getSysordernumber($order_id)
    {
        return M('order')->where('id='.$order_id)->getField('sysordernumber');
    }

    //今日充值金额统计 (status>0  已付款)
    public static function getTodaySumMoney()
    {
        return M('orderinfo')->where("to_days(datetime) = to_days(now()) and status>0")->sum('ordermoney');
    }

    public static function getTodaySumMoneytrade()
    {
        return M('orderinfo')->where("to_days(datetime) = to_days(now()) and status>0")->sum('moneytrade');
    }

    //今日充值的总笔数
    public static function getTodayCounts()
    {
        return M('orderinfo')->where("to_days(datetime) = to_days(now())")->count();
    }

    //本月充值总金额
    public static function getMonthsSumMoney()
    {
        return M('orderinfo')->where("DATE_FORMAT(datetime,'%Y%m') = DATE_FORMAT(now(),'%Y%m')  and status>0")->sum('ordermoney');
    }

    ////本月充值总手续费
    public static function getMonthsSumMoneytrade()
    {
        return M('orderinfo')->where("DATE_FORMAT(datetime,'%Y%m') = DATE_FORMAT(now(),'%Y%m')  and status>0")->sum('moneytrade');
    }
    //本月充值总笔数
    public static function getMonthsCounts()
    {
        return M('orderinfo')->where("DATE_FORMAT(datetime,'%Y%m') = DATE_FORMAT(now(),'%Y%m')")->count();
    }

    //本年充值总金额
    public static function getYearSumMoney()
    {
        return M('orderinfo')->where("YEAR(datetime)=YEAR(NOW())  and status>0")->sum('ordermoney');
    }

    //本年充值总手续费
    public static function getYearSumMoneytrade()
    {
        return M('orderinfo')->where("YEAR(datetime)=YEAR(NOW())  and status>0")->sum('moneytrade');
    }

    //今年充值总笔数
    public static function getYearCounts()
    {
        return M('orderinfo')->where("YEAR(datetime)=YEAR(NOW())")->count();
    }

}