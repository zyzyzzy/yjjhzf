<?php
/**
 * Created by PhpStorm.
 * User: 任梦龙
 * Date: 2019/2/18/018
 * Time: 下午 3:11
 * 通道商家与通道分类关系表的模型
 */

namespace Admin\Model;

use Think\Model;

class PayapishangjiapayapiclassModel extends Model
{
    public static function changeStatus($class_id, $status)
    {
        M('payapishangjiapayapiclass')->where('payapiclassid=' . $class_id)->setField('status', $status);
    }

    /**
     * 修改商家--分类关系表的删除状态
     * @param $payapiclassid：分类id
     * @param $del_status：0 =正常；1=已删除
     */
    public static function delShangjiaAndClass($payapiclassid, $del_status)
    {
        M('payapishangjiapayapiclass')->where("payapiclassid=" . $payapiclassid)->setField('del', $del_status);
    }


    /**
     * 2019-2-19 任梦龙：根据商家id来软删除 商家--分类关系
     * @param $payapishangjiaid ：商家id
     * @param $del_status ：0 =正常；1=已删除
     */
    public static function delToShangjiaId($payapishangjiaid, $del_status)
    {
        M('payapishangjiapayapiclass')->where("payapishangjiaid=" . $payapishangjiaid)->setField('del', $del_status);
    }




    /**
     * 获取记录
     */
    public static function getShangjiaClassInfo($payapishangjiaid, $payapiclassid)
    {
        return M("payapishangjiapayapiclass")
            ->where("payapishangjiaid=" . $payapishangjiaid . " and payapiclassid=" . $payapiclassid)
            ->find();
    }

    /**
     * 修改状态
     */
    public static function editShangjiaClassStatus($payapishangjiaid, $payapiclassid, $status)
    {
        return M("payapishangjiapayapiclass")
            ->where("payapishangjiaid=" . $payapishangjiaid . " and payapiclassid=" . $payapiclassid)
            ->setField('status', $status);
    }
}