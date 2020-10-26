<?php
namespace Settlement\Model;

use Think\Model;

class BlackdomainModel extends Model
{
    public static function getDomain($domain)
    {
        return D('blackdomain')->where(['domain'=>['eq',$domain]])->find();
    }

    //2019-5-8 rml：修改
    public static function checkDomain($domain)
    {
        $all_domain = D('blackdomain')->getField('domain',true);
        $res = false;
        foreach ($all_domain as $v){
            if(strpos($domain,$v) !== false){
                $res = true;
                break;
            }
        }
        return $res;
    }
}