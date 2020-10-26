<?php

namespace Admin\Model;

use Think\Model;

class MoneychangeModel extends Model
{

    protected $_scope = array(
        // 命名范围normal
        'default_scope' => array(
            'field' => [

                "id"

                , "oldmoney"

                , "changemoney"

                , "nowmoney"

                , "getsjusername(userid) as username"

                , "getpayapiname(payapiid) as payapiname"

                , "getaccountname(accountid) as accountname"

                , "datetime"

                , "transid"

                , "orderid"

                , "changetype"

                , "getsjusername(tcuserid) as tcusername"

                , "tcdengji"

                , "remarks"
            ],
        ),

    );


    //2019-03-04汪桂芳添加
    public static function getInfoBySysordernumber($transid)
    {
        return M('moneychange')->where('transid="' . $transid . '" and changetype=4')->find();
    }

    //2019-03-04汪桂芳添加
    public static function getInfoByChangetype($transid, $changetype)
    {
        return M('moneychange')->where('transid="' . $transid . '" and changetype=' . $changetype)->find();
    }
}
