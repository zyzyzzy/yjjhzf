<?php

namespace User\Model;

use Think\Model;

class PayapiaccountModel extends Model
{
    //2019-4-2 任梦龙：添加
    protected $_validate = array(
        array('bieming', 'require', '通道账号名称不能为空！', 0),
        array('bieming', 'checkName', '通道账号名称已存在！', 0, 'callback', 3),
        array('bieming', '2,20', '通道账号名称长度在2-20字符之间！', 0, 'length', 3),  //2019-4-19 rml：添加
        array('user_payapiid', 'require', '请选择通道', 0),
//        array('payapishangjiaid', 'require', '通道商家不能为空！', 0),
//        array('moneytypeclassid', 'require', '到账方案不能为空！', 0),
//        array('moneytypeclassid', 'setDzbl', '该到账方案的到账比例的和需小于1，请检查！', 0, 'callback'),

        //2019-4-3 任梦龙：修改，商户号和账号名可以不填写
//        array('memberid', 'require', '信息填写不完整,请检查！', 0),
//        array('account', 'require', '信息填写不完整,请检查！', 0),
        array('submiturl', 'require', '跳转地址不能为空！', 0),
        array('notifyurl', 'require', '异步回调地址不能为空！', 0),
        array('callbackurl', 'require', '同步回调地址不能为空！', 0),
    );

    //2019-4-16 rml:自助账号名称不能重复
    protected function checkName($name)
    {
        $id = I('request.id', 0);
        if (!$id) {
            $count = D('payapiaccount')->where(['bieming' => $name, 'del' => 0])->count();
        } else {
            $count = D('payapiaccount')->where(['bieming' => $name, 'del' => 0, 'id' => ['NEQ', $id]])->count();
        }
        if ($count) {
            return false;
        }
        return true;
    }

    //根据id获取账号名称
    public static function getBieming($id)
    {
        return M('payapiaccount')->where(['id' => $id])->getField('bieming');
    }

    //根据id获取单条记录
    public static function getuserPayapiaccount($id)
    {
        return M('payapiaccount')->where(['id' => $id])->find();
    }

    //删除(改变删除状态)
    public static function delUserPayapiAccount($id)
    {
        return M('payapiaccount')->where(['id' => $id])->setField('del', 1);
    }

    //改变状态
    //2019-3-29 任梦龙：修改
    public static function changeStatus($id, $type, $val)
    {
        return M('payapiaccount')->where(['id' => $id])->setField($type, $val);
    }

    //2019-3-29 任梦龙：获取通道id
    public static function getUserPayapiid($id)
    {
        return M('payapiaccount')->where(['id' => $id])->getField('user_payapiid');
    }

    //2019-3-29 任梦龙：查询这个用户在这个通道上有没有其它账号的默认状态的
    public static function findUserAccountid($where)
    {
        return M('payapiaccount')->where($where)->getField('id');
    }

    //2019-3-29 任梦龙：修改数据
    public static function editUserAccount($id, $data)
    {
        return M('payapiaccount')->where(['id' => $id])->save($data);
    }
}

