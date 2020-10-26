<?php

namespace Pay\Model;

use Think\Model;

class OrderModel extends Model{

    protected $_auto = array(
        array('datetime','YmdHis',1,'function'), //新增时赋值当前时间
        array('status',0),  //状态为默认初始值
    );
    protected  $_validate = array(   //自动验证

        array('userordernumber','require','用户订单号不能为空！',1),
        array('sysordernumber','require','系统订单号不能为空！',1),
        array('sysordernumber','','系统订单号已存在！',1,'unique',1),
        array('shangjiaid','require','商家ID不能为空！',1),
        array('payapiid','require','通道ID不能为空！',1),
        array('payapiaccountid','require','通道账户ID不能为空！',1),
        array('memberid','require','商户号不能为空！',1),
        array('memberid','memberidcheck','商户号不存在',1,'callback',1),
        array('userid','require','用户ID不能为空！',1),
        array('userid','useridcheck','用户ID不存在',1,'callback',1),
        array('callbackurl','url','同步回调地址不是网址',0,'regex',1),
        array('notifyurl','require','异步回调地址不能为空！',1),
        array('notifyurl','url','异步回调地址不是网址',0,'regex',1),
    );

   protected  function useridcheck($userid){
       $count = M("user")->where("id=".$userid)->count();
       if($count <= 0){
           return false;
       }else{
           return true;
       }
   }

    protected  function memberidcheck($memberid){
        $count = M("secretkey")->where("memberid='".$memberid."'")->count();
        if($count <= 0){
            return false;
        }else{
            return true;
        }
    }

    public static function getUserLatestOrder($userid,$limit=15)
    {
        return D('orderinfo')->where([
            'userid'=>['eq',$userid]
        ])->order('orderid DESC')->limit($limit)->select();
    }
    public static function getOrderinfo($id)
    {
        return D('orderinfo')->where([
            'orderid'=>['eq',$id]
        ])->find();
    }
}

