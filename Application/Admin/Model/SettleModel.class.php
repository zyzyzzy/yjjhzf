<?php
namespace Admin\Model;

use Think\Model;

class SettleModel extends Model
{
    public static function getInfo($id)
    {
        return D('settle')->find($id);
    }

    public static function getSysordernumber($id)
    {
        return M('settle')->where('id='.$id)->getField('ordernumber');
    }

    //今日提款总金额
    public static function getTodaySumMoney()
    {
        return M('settleinfo')->where("to_days(applytime) = to_days(now()) and status=2")->sum('ordermoney');
    }

    //今日提款总金额
    public static function getTodaySumMoneytrade()
    {
        return M('settleinfo')->where("to_days(applytime) = to_days(now()) and status=2")->sum('moneytrade');
    }
    //今日提款总笔数
    public static function getTodayCounts()
    {
        return M('settleinfo')->where("to_days(applytime) = to_days(now())")->count();
    }

    //本月提款总金额
    public static function getMonthsSumMoney()
    {
        return M('settleinfo')->where("DATE_FORMAT(applytime,'%Y%m') = DATE_FORMAT(now(),'%Y%m') and status=2")->sum('ordermoney');
    }
    //本月提款总手续费
    public static function getMonthsSumMoneytrade()
    {
        return M('settleinfo')->where("DATE_FORMAT(applytime,'%Y%m') = DATE_FORMAT(now(),'%Y%m') and status=2")->sum('moneytrade');
    }

    //本月提款总笔数
    public static function getMonthsCounts()
    {
        return M('settleinfo')->where("DATE_FORMAT(applytime,'%Y%m') = DATE_FORMAT(now(),'%Y%m')")->count();
    }

    //今年提款总金额
    public static function getYearSumMoney()
    {
        return M('settleinfo')->where("YEAR('applytime')=YEAR(now()) and status=2")->sum('ordermoney');
    }

    //本年提款总手续费
    public static function getYearSumMoneytrade()
    {
        return M('settleinfo')->where("YEAR('applytime')=YEAR(now()) and status=2")->sum('moneytrade');
    }

    //今年提款总笔数
    public static function getYearCounts()
    {
        return M('settleinfo')->where("YEAR('applytime')=YEAR(now())")->count();
    }
}