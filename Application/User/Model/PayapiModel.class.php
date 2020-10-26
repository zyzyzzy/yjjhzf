<?php

namespace User\Model;

use Think\Model;

class PayapiModel extends Model
{

    //查询通道分类
    public static function getPayapiclassid($id)
    {
        return M('payapi')->where('id=' . $id)->getField('payapiclassid');
    }

    //查询通道名称
    public static function getPayapiName($id)
    {
        return M('payapi')->where('id=' . $id)->getField('zh_payname');
    }

    //2019-3-28 任梦龙：根据id获取记录,字段少
    public static function findPayapiAccount($id)
    {
        return M('payapi')->where(['id' => $id])->field('id,zh_payname')->find();
    }

    public static function getAccountInfo($id)
    {
        return M('payapi')->where(['id' => $id])->find();
    }

    //2019-3-28 任梦龙：获取商家id
    public static function getShangjiaId($id)
    {
        return M('payapi')->where(['id' => $id])->getField('payapishangjiaid');
    }
}

