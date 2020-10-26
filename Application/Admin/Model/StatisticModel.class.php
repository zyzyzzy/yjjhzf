<?php
namespace Admin\Model;

use Think\Model;

class StatisticModel extends Model
{
    //根据h(小时) 总金额排序  获取当前时间总金额
    public static function getTodayData($type=1)
    {
        $y = date('Y');
        $m = date('m');
        $d = date('d');
        $sql ="select h, sum(amount) as 'sum_amount' from pay_statistic where ((y='".$y."') and (m='".$m."') and (d='".$d."') and (type='".$type."')) GROUP BY h";
        $data = D('statistic')->query($sql);
        // 取得列的列表
        foreach ($data as $key => $row)
        {
            $order_cz[$key]  = $row['h'];
            $order_lr[$key] = $row['sum_amount'];
        }
        array_multisort($order_cz, SORT_ASC, $order_lr, SORT_DESC, $data);
        return $data;
    }

    //获取y年m月d日 充值/结算总金额
    public static function getTodaySum($y,$m,$d,$type=1)
    {
        if(!$y){
            $y = date('Y');
        }
        if(!$m){
            $m = date('m');
        }
        if(!$d){
            $d = date('d');
        }

        $sql ="select  sum(amount) as 'sum_amount' from pay_statistic where ((y='".$y."') and (m='".$m."') and (d='".$d."') and (type='".$type."')) ";
        $data = D('statistic')->query($sql);
        return $data[0]['sum_amount'];
    }

    //根据通道分类获取当前时间 充值/结算 的总金额
    public static function getTodayStatiticDataByPayapiclass($type=1)
    {
        $y = date('Y');
        $m = date('m');
        $d = date('d');
        $sql ="select  payapiclassid,sum(amount) as 'sum_amount' from pay_statistic where ((y='".$y."') and (m='".$m."') and (d='".$d."') and (type='".$type."')) GROUP BY payapiclassid";
        $data = D('statistic')->query($sql);
        // 取得列的列表
        foreach ($data as $key => $row)
        {
            $order_lr[$key] = $row['sum_amount'];
            $order_cz[$key]  = $row['payapiclassid'];
        }
        array_multisort($order_lr, SORT_DESC, $order_cz, SORT_ASC, $data);
        $result=[];
        foreach($data as $item){
           $result[$item['payapiclassid']]=$item['sum_amount'];
        }
        return $result;
    }

    //获取某个分类下y年m月d日 充值/结算  的总金额
    public static function getTodayByPayapiclass($y,$m,$d,$payapiclassid,$type=1)
    {
        $sql ="select sum(amount) as 'sum_amount' from pay_statistic where ((y='".$y."') and (m='".$m."') and (d='".$d."') and (type='".$type."') and (payapiclassid='".$payapiclassid."'))";
        $data = D('statistic')->query($sql);
        return $data[0]['sum_amount'];
    }

    //根据h(某个小时)分组 获取y年m月d日 充值/结算 总利润
    public static function getTodayProfitByh($type=1)
    {
        $y = date('Y');
        $m = date('m');
        $d = date('d');
        $h = date('h');
        $data = D('statistic')->where([
            "y"=>["eq",$y],
            "m"=>["eq",$m],
            "d"=>["eq",$d],
            "type"=>["eq",$type],
        ])->field('h,sum(profit) as sum_profit')->group('h')->select();
        foreach ($data as $key => $row)
        {
            $order_lr[$key] = $row['h'];
            $order_cz[$key]  = $row['sum_profit'];
        }
        array_multisort($order_lr, SORT_ASC, $order_cz, SORT_ASC, $data);
        return $data;
    }

    //获取y年m月d日 充值/结算的总利润
    public static function getTodaySumProfit($y,$m,$d,$type=1)
    {
        if(!$y){
            $y = date('Y');
        }
        if(!$m){
            $m = date('m');
        }
        if(!$d){
            $d = date('d');
        }

        return $data = D('statistic')->where([
            "y"=>["eq",$y],
            "m"=>["eq",$m],
            "d"=>["eq",$d],
            "type"=>["eq",$type],
        ])->sum('profit');
    }

    //获取y年m月 充值/结算的总利润
    public static function getMonthsSumProfit($y,$m,$type=1)
    {
        if(!$y){
            $y = date('Y');
        }
        if(!$m){
            $m = date('m');
        }
        return $data = D('statistic')->where([
            "y"=>["eq",$y],
            "m"=>["eq",$m],
            "type"=>["eq",$type],
        ])->sum('profit');
    }

    //获取y年 充值/结算的总利润
    public static function getYearSumProfit($y,$type=1)
    {
        if(!$y){
            $y = date('Y');
        }

        return $data = D('statistic')->where([
            "y"=>["eq",$y],
            "type"=>["eq",$type],
        ])->sum('profit');
    }


    //统计y年m月d日 充值/结算的总利润
    public static function getProfitByPayapiclass($y,$m,$d,$type=1)
    {
        if(!$y){
            $y = date('Y');
        }
        if(!$m){
            $m = date('m');
        }
        if(!$d){
            $d = date('d');
        }

        return $data = D('statistic')->where([
            "y"=>["eq",$y],
            "m"=>["eq",$m],
            "d"=>["eq",$d],
            "type"=>["eq",$type],
        ])->field('payapiclassid,sum(profit) as sum_profit')->group('payapiclassid')->select();

    }

    //根据用户分组获取y年m月d日的总利润,并排序
    public static function getMaxProfitByUser($y,$m,$d,$type=1,$num=10)
    {
        if(!$y){
            $y = date('Y');
        }
        if(!$m){
            $m = date('m');
        }
        if(!$d){
            $d = date('d');
        }

        return $data = D('statistic')->where([
            "y"=>["eq",$y],
            "m"=>["eq",$m],
            "d"=>["eq",$d],
            "type"=>["eq",$type],
        ])->field('userid,sum(profit) as sum_profit')
            ->group('userid')->order('sum_profit DESC')->limit($num)->select();

    }

    //获取某个用户y年m月d日总 结算/支付 金额
    public static function getTodaySumByUser($userid,$y,$m,$d,$type=1)
    {
        if(!$y){
            $y = date('Y');
        }
        if(!$m){
            $m = date('m');
        }
        if(!$d){
            $d = date('d');
        }

        $sql ="select  sum(amount) as 'sum_amount' from pay_statistic where ((y='".$y."') and (m='".$m."') and (d='".$d."') and (type='".$type."') and (userid='".$userid."')) ";
        $data = D('statistic')->query($sql);
        return $data[0]['sum_amount'];
    }

    //用户最近(7天)充值金额统计
    public static function getUserLastSumAmount($userid)
    {
        $date = getLatestDate();
        $data = [];
        foreach ($date as $key => $value) {
            $data['date'][] = $value['m'] . "/" . $value['d'];
            $sum_amount = StatisticModel::getTodaySumByUser($userid, $value['y'], $value['m'], $value['d']);
            $data['sum_amount'][] = $sum_amount?$sum_amount:0;
//            if($sum_amount){
//                $data['sum_amount'][] =$sum_amount;
//            }
        }
        return $data;
    }

    //总利润
    public static function getSumProfit()
    {
        return D('statistic')->sum('profit');
    }

    //充值总金额
    public static function getSumAmount($type=1)
    {
        return D('statistic')->where([
            "type" => ["eq", $type],
        ])->sum('amount');
    }

    public static function getInfoByUser($where)
    {
        return D('statistic')->where($where)->find();
    }

    public static function editInfo($where,$data)
    {
        return D('statistic')->where($where)->save($data);
    }

    //获取y年m月d日 充值/提现 最多的前 $limit 个用户
    public static function getMaxMoneyByUser($y,$m,$d,$type=1,$limit=10)
    {
        if(!$y){
            $y = date('Y');
        }
        if(!$m){
            $m = date('m');
        }
        if(!$d){
            $d = date('d');
        }

        return $data = D('statistic')->where([
            "y"=>["eq",$y],
            "m"=>["eq",$m],
            "d"=>["eq",$d],
            "type"=>["eq",$type],
        ])->field('userid,sum(amount) as sum_amount')
            ->group('userid')->order('sum_amount DESC')->limit($limit)->select();
    }
}