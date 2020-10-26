<?php

namespace Admin\Model;

use Think\Model;

class TongdaozhanghaoModel extends Model
{


    protected $_validate = array(   //自动验证

        array('money', 'checkaccountsummoney', '设置金额超过账号总额!', 1, 'callback', 2),
        array('money', 'checkaccountmoney', '超过最大可设置金额!', 1, 'callback', 2),


    );

    protected function checkaccountsummoney($amount)
    {
        $payapiaccountid = M("tongdaozhanghao")->where("id=" . I("request.id"))->getField("payapiaccountid");
        $summoney = M("payapiaccount")->where("id=" . $payapiaccountid)->getField("money");
        if ($summoney < $amount) {
            return false;
        } else {
            return true;
        }
    }

    protected function checkaccountmoney($amount)
    {
        $payapiaccountid = M("tongdaozhanghao")->where("id=" . I("request.id"))->getField("payapiaccountid");
        $summoney = M("payapiaccount")->where("id=" . $payapiaccountid)->getField("money");
        $sumamount = M("tongdaozhanghao")->where("payapiaccountid=" . $payapiaccountid . " and userid=0 and (id <> " . I("request.id") . ")")->sum("money");
        if ($summoney < $amount + $sumamount) {
            return false;
        } else {
            return true;
        }
    }

    //2019-4-4 任梦龙：判断通道账号表中有没有账号在使用
    public static function isExistAccount($payapiaccountid)
    {
        return M('tongdaozhanghao')->where(['payapiaccountid' => $payapiaccountid])->count();  //通道账号的数量
    }

    //2019-4-4 任梦龙：获取每日交易金额
    public static function getTongdaoMoney($id)
    {
        return M('tongdaozhanghao')->where(['id' => $id])->getField('money');
    }

    public static function editTongdao($id, $save)
    {
        return M('tongdaozhanghao')->where(['id' => $id])->save($save);
    }

    //2019-4-4 任梦龙：判断通道下有没有账号
    public static function getTongdaoZhanghao($payapiid)
    {
        return M('tongdaozhanghao')->where(['payapiid' => $payapiid,'userid'=> 0])->count();
    }

    //删除通道的账号
    public static function delTongdaoZhanghao($where)
    {
        return M("tongdaozhanghao")->where($where)->delete();
    }

    //添加通道的账号
    public static function addTongdaoZhanghao($data)
    {
        return M("tongdaozhanghao")->add($data);
    }

    //获取在通道的账号中被选择的账号
    public static function getCheckAccount($where)
    {
        return M("tongdaozhanghao")->where($where)->getField('payapiaccountid', true);
    }


}

