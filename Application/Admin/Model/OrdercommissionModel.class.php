<?php
namespace Admin\Model;

use Think\Model;

class OrdercommissionModel extends Model
{
    //今日提成总金额
    public static function getTodaySumMoney()
    {
        return M('ordercommission')->where("to_days(date_time) = to_days(now())")->sum('tc_money');
    }

    //今日提成总笔数
    public static function getTodayCounts()
    {
        return M('ordercommission')->where("to_days(date_time) = to_days(now())")->count();
    }


    //当月提成总金额
    public static function getMonthsSumMoney()
    {
        return M('ordercommission')->where("DATE_FORMAT(date_time,'%Y%m') = DATE_FORMAT(now(),'%Y%m')")->sum('tc_money');
    }
    //当月提成总笔数
    public static function getMonthsCounts()
    {
        return M('ordercommission')->where("DATE_FORMAT(date_time,'%Y%m') = DATE_FORMAT(now(),'%Y%m')")->count();
    }
    //当年提成总金额
    public static function getYearSumMoney()
    {
        return M('ordercommission')->where("YEAR(date_time) = YEAR(now())")->sum('tc_money');
    }
    //当年提成总笔数
    public static function getYearCounts()
    {
        return M('ordercommission')->where("YEAR(date_time) = YEAR(now())")->count();
    }

    public static function getSummoneyByOrder($ordernumber)
    {
        return M('ordercommission')->where(['sysordernumber'=>['eq',$ordernumber]])->sum('tc_money');
    }
}