<?php

namespace Admin\Model;

use Think\Model;

class MoneytypeModel extends Model{



    protected $_auto = array(

        array('datetime','YmdHis',1,'function'), //新增时赋值当前时间

    );



    protected  $_validate = array(   //自动验证

        array('moneytypeclassid','require','余额方案ID不能为空！',1),

        array('moneytypename','require','余额名称不能为空！',1),

        array('moneytypename','','余额名称已存在！',1,'unique',3),

        array('moneytypename','2,20','余额名称最短2个汉字，最长20个汉字！',1,'length',1),

        array('type','require','余额类型不能为空！',1),

        array('dzsj_day','moneytypecheck','暂时冻结类型到账天数不能为空!',1,'callback',3),

        array('jiejiar','moneytypecheck','暂时冻结类型节假日到账不能为空!',1,'callback',3),

        array('dzsj_time','moneytypecheck','暂时冻结类型到账时间不能为空!',1,'callback',3),

        array('dzbl','dzblcheck','总到账比较不能超过100%',1,'callback',3),

    );



    protected $_scope = array(

        //命名范围

        'default' => array(

            'order' => 'datetime desc'

        )

    );





   protected function moneytypecheck($str){

        $type = I("request.type");

       // echo("[".$str."]");

        if($type == 1){

            if($str == "" || $str == 0){

                return false;

            }else{

                return true;

            }

        }else{

            return true;

        }

    }



    protected function dzblcheck($str){

       $moneytypeclassid = I("request.moneytypeclassid");
       $id = I("request.id");

       $sum = M("moneytype")->where("moneytypeclassid=".$moneytypeclassid)->sum("dzbl");
       $idstr = M("moneytype")->where("id=".$id)->getField("dzbl");

       if($sum + $str - $idstr >1){

           return false;

       }else{

           return true;

       }

    }

}

