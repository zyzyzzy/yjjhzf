<?php

namespace User\Model;

use Think\Model;

class PayapiclassModel extends Model
{

    //查询通道分类名称
    public static function getPayapiclassid($id)
    {
        return M('payapiclass')->where(['id' => $id])->getField('classname');
    }

    //通过通道id获取通道分类名
    public static function getClassnameBypayapiid($payapiid)
    {
        $payapiclassid = PayapiModel::getPayapiclassid($payapiid);
        return PayapiclassModel::getPayapiclassid($payapiclassid);
    }

    public static function getOrderFeilv($id)
    {
        return M('payapiclass')->where(['id' => $id])->getField('order_feilv');
    }

    public static function getOrderMinFeilv($id)
    {
        return M('payapiclass')->where(['id' => $id])->getField('order_min_feilv');
    }

    public static function getPayapiClassList()
    {
        return D('payapiclass')->field('id,classname')->select();
    }

    public static function getPayapiclassBm($id)
    {
        return M('payapiclass')->where(['id' => $id])->getField('classbm');
    }
}

