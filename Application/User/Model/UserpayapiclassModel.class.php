<?php

namespace User\Model;

use Think\Model;

//整Model2018-12-28汪桂芳创建
class UserpayapiclassModel extends Model
{
    protected $_scope = array(
        // 命名范围normal
        'default_scope' => array(
            'field' => [

                'id'

                , 'payapiclassid'

                , 'order_feilv'

                , 'order_min_feilv'

                , "getpayapiclassname(payapiclassid) as payapiclassname"

                , "getpayapistatus(payapiid) as status"

//                ,"getpayapiname(payapiid) as payapiname"

            ],
        ),

    );

    //查询用户信息
    public static function getUserPayapi($userid)
    {
        return D('userpayapiclass')->scope('default_scope')->where('userid=' . $userid)->select();
    }

    public static function getUserpayapis($userid)
    {
        return D('userpayapiclass')
            ->where('userid=' . $userid)
            ->join('__PAYAPICLASS__ on __USERPAYAPICLASS__.payapiclassid=__PAYAPICLASS__.id')
            ->select();
    }

    //2019-7-28 rml:重新获取用户费率，因为用户的费率和分类的费率字段名称一致，所以需要取别名
    public static function getUserpayapiList($userid)
    {
        return D('userpayapiclass')->alias('a')
            ->where('userid=' . $userid)
            ->join('__PAYAPICLASS__ b on a.payapiclassid=b.id')
            ->field('b.img_url,b.classname,b.order_feilv as class_feilv,a.order_feilv as user_feilv')
            ->select();
    }


    public static function getUserPayapiClass($user_id)
    {
        return M('userpayapiclass')->where(['userid' => $user_id])->select();
    }

    public static function getOrderFeilv($where)
    {
        return M('userpayapiclass')->where($where)->getField('order_feilv');
    }


}