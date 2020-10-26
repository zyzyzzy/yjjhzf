<?php

namespace Admin\Model;

use Think\Model;

class PayapiaccountloopsModel extends Model
{


    protected $_auto = array(
        array('status', 0),
        array('datetime_ks', '00:00:00'),
        array('datetime_js', '23:59:59'),
        array('money_ks', 0),
        array('money_js', 999999999)
    );

    //2019-4-4 任梦龙：删除轮询条件数据
    public static function delAccountloop($payapiaccountid)
    {
        M('payapiaccountloops')->where(['payapiaccountid' => $payapiaccountid])->setField('del', 1);
    }

    //2019-4-4 任梦龙：查询单条记录
    public static function getLoopsinfo($payapiaccountid)
    {
        return M('payapiaccountloops')->where(['payapiaccountid' => $payapiaccountid])->find();
    }

    //添加记录
    public static function addLoopsinfo($payapiaccountid)
    {

        M('payapiaccountloops')->add([
            'payapiaccountid' => $payapiaccountid,
            'status' => 0,
            'datetime_ks' => '00:00:00',
            'datetime_js' => '23:59:59',
            'money_ks' => 0,
            'money_js' => 99999999,
        ]);
    }


}

