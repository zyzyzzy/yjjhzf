<?php
namespace Admin\Model;

use Think\Model;

class SuccessrateModel extends Model
{
    /**
     * 获取用户某天的成功率
     * @param $userid 用户id
     * @param string $d 天
     * @param string $m 月份
     * @param string $y 年
     * @param int $type 订单类型
     * @return int|string
     */
    public static function getUserSuccessrateByDay($userid, $d = '', $m = '', $y = '', $type = 1)
    {
        if (!$d) {
            $d = date('d');
        }
        if (!$m) {
            $m = date('m');
        }
        if (!$y) {
            $y = date('Y');
        }
        $successcount = D('successrate')->where([
            "y" => ["eq", $y],
            "m" => ["eq", $m],
            "d" => ["eq", $d],
            "userid" => ["eq", $userid],
            "type" => ["eq", $type],
        ])->sum('successcount');
        $count = D('successrate')->where([
            "y" => ["eq", $y],
            "m" => ["eq", $m],
            "d" => ["eq", $d],
            "userid" => ["eq", $userid],
            "type" => ["eq", $type],
        ])->sum('count');
        if (!$successcount) {
            return 0;
        }
        return sprintf('%.4f', $successcount / $count) * 100;
    }

    /**
     * 获取所有用户某天的成功率
     * @param string $d 天
     * @param string $m 月份
     * @param string $y 年
     * @param int $type 订单类型
     * @return int|string
     */
    public static function getSuccessrateByDay($d = '', $m = '', $y = '', $type = 1)
    {
        if (!$d) {
            $d = date('d');
        }
        if (!$m) {
            $m = date('m');
        }
        if (!$y) {
            $y = date('Y');
        }
        $successcount = D('successrate')->where([
            "y" => ["eq", $y],
            "m" => ["eq", $m],
            "d" => ["eq", $d],
            "type" => ["eq", $type],
        ])->sum('successcount');
        $count = D('successrate')->where([
            "y" => ["eq", $y],
            "m" => ["eq", $m],
            "d" => ["eq", $d],
            "type" => ["eq", $type],
        ])->sum('count');
        if (!$successcount) {
            return 0;
        }
        return sprintf('%.4f', $successcount / $count) * 100;
    }

    public static function getUserTodaySuccessrate($userid, $type = 1)
    {
        $y = date('Y');
        $m = date('m');
        $d = date('d');
        $h = date('H');
        $data = D('successrate')->where([
            "y"=>["eq",$y],
            "m"=>["eq",$m],
            "d"=>["eq",$d],
            "h"=>["ELT",$h],
            "userid"=>["eq",$userid],
            "type"=>["eq",$type],
        ])->field('sum(count) as count,sum(successcount) as successcount,h')->group('h')->select();
        return array_column($data,NULL,'h');
    }

    public static function getTodaySumCount($y = '', $m = '', $d = '', $type = 1)
    {
        if (!$d) {
            $d = date('d');
        }
        if (!$m) {
            $m = date('m');
        }
        if (!$y) {
            $y = date('Y');
        }
        return  D('successrate')->where([
            "y" => ["eq", $y],
            "m" => ["eq", $m],
            "d" => ["eq", $d],
            "type" => ["eq", $type],
        ])->sum('count');
    }

    public static function getSumCount($type = 1)
    {
        return  D('successrate')->where([
            "type" => ["eq", $type],
        ])->sum('count');
    }

    public static function getSumSuccessCount($type = 1)
    {
        return  D('successrate')->where([
            "type" => ["eq", $type],
        ])->sum('successcount');
    }

    public static function getInfoByUser($userid,$payapiid,$payapiclassid,$type=1)
    {
        $d = date('d');
        $m = date('m');
        $y = date('Y');
        $h = date('H');
        return D('successrate')->where([
            'y'=>['eq',$y],
            'm'=>['eq',$m],
            'd'=>['eq',$d],
            'h'=>['eq',$h],
            'userid'=>['eq',$userid],
            'payapiid'=>['eq',$payapiid],
            'payapiclassid'=>['eq',$payapiclassid],
            'type'=>['eq',$type],
        ])->find();
    }

    public static function getInfo($where)
    {
        return D('successrate')->where($where)->find();
    }


    public static function countInc($id)
    {
        return D('successrate')->where([
            'id'=>['eq',$id],
        ])->setInc('count');
    }

    public static function successcountInc($id)
    {
        return D('successrate')->where([
            'id'=>['eq',$id],
        ])->setInc('successcount');
    }

    public static function addSuccessrate($userid,$payapiid,$payapiclassid,$type=1)
    {
        $d = date('d');
        $m = date('m');
        $y = date('Y');
        $h = date('H');
        return D('successrate')->add([
            'y'=>$y,
            'm'=>$m,
            'd'=>$d,
            'h'=>$h,
            'userid'=>$userid,
            'payapiid'=>$payapiid,
            'payapiclassid'=>$payapiclassid,
            'type'=>$type,
            'count'=>1,
            'successcount'=>0,
        ]);
    }

}