<?php

namespace Admin\Model;

use Think\Model;

class UserbankcardModel extends Model
{
    protected $_scope = array(
        'default_scope' => array(
            'order' => "mr desc",
        ),
    );
    protected $_auto = array(
        array('status', '1'),  // 新增的时候把status字段设置为1
        array('datetime', 'YmdHis', 1, 'function'), //新增时赋值当前时间
        array('bankcode', 'getCode', 1, 'callback'), //2019-03-11汪桂芳添加,自动获取银行编码
    );

    //2019-4-19 rml：验证唯一性,银行卡号，身份证号，手机号，
    protected $_validate = array(
        array('userid', 'require', '用户ID不能为空！', 0),
        array('bankname', 'require', '银行名称不能为空！', 0),
        array('banknumber', 'require', '银行卡号不能为空！', 0),
        array('banknumber', 'checkBankNum', '银行卡号已存在！', 0, 'callback', 3),
        array('bankmyname', 'require', '开户人姓名不能为空！', 0),
        array('bankmyname', '2,20', '开户人姓名长度在2-20字符之间!', 0, 'length', 3),
        array('shenfenzheng', 'require', '身份证号不能为空！', 0),
        array('shenfenzheng', 'checkShenFen', '身份证号已存在！', 0, 'callback', 3),
        array('shenfenzheng', 'checkshenfenzheng', '身份证号格式错误!', 0, 'callback', 3),
        array('tel', 'require', '手机号不能为空！', 0),
        array('tel', 'checkPhone', '手机号已存在！', 0, 'callback', 3),
        array('tel', 'checktel', '手机号格式错误!', 0, 'callback', 3),
    );

    //银行卡号
    protected function checkBankNum($name)
    {
        $id = I('request.id', '');
        if ($id) {
            $count = D('userbankcard')->where(['banknumber' => $name, 'del' => 0, 'id' => ['NEQ', $id]])->count();
        } else {
            $count = D('userbankcard')->where(['banknumber' => $name, 'del' => 0])->count();
        }
        if ($count) {
            return false;
        }
        return true;
    }

    //身份证号
    protected function checkShenFen($name)
    {
        $id = I('request.id', '');
        if ($id) {
            $count = D('userbankcard')->where(['shenfenzheng' => $name, 'del' => 0, 'id' => ['NEQ', $id]])->count();
        } else {
            $count = D('userbankcard')->where(['shenfenzheng' => $name, 'del' => 0])->count();
        }
        if ($count) {
            return false;
        }
        return true;
    }

    //手机号
    protected function checkPhone($name)
    {
        $id = I('request.id', '');
        if ($id) {
            $count = D('userbankcard')->where(['tel' => $name, 'del' => 0, 'id' => ['NEQ', $id]])->count();
        } else {
            $count = D('userbankcard')->where(['tel' => $name, 'del' => 0])->count();
        }
        if ($count) {
            return false;
        }
        return true;
    }


    //2019-03-11汪桂芳添加,获取银行编码
    protected function getCode()
    {
        $bank = $_REQUEST['bankname'];
        $code = M('systembank')->where('bankname="' . $bank . '"')->getField('bankcode');
        return $code;
    }

    protected function checktel($mobile)
    {
        if (preg_match("/^1[34578]\d{9}$/", $mobile)) {
            return true;
        } else {
            return false;
        }
    }

    protected function checkshenfenzheng($mobile)
    {
        if (preg_match("/(^\d{15}$)|(^\d{18}$)|(^\d{17}(\d|X|x)$)/", $mobile)) {
            return true;
        } else {
            return false;
        }
    }

    //2019-3-25 任梦龙：修改用户银行状态
    public static function editStatus($id, $status)
    {
        return M("userbankcard")->where("id=" . $id)->setField("status", $status);
    }

    //2019-3-25 任梦龙：根据id获取用户银行名称

    public static function getUserBankName($id)
    {
        return M("userbankcard")->where("id=" . $id)->getField('bankname');
    }

    //2019-3-25 任梦龙：根据id修改用户银行卡的默认状态
    public static function editMr($id, $mr)
    {
        return M("userbankcard")->where("id=" . $id)->setField("mr", $mr);
    }

    //2019-3-25 任梦龙：除指定的id外，默认状态修改为关闭
    public static function editMrstatus($userid, $id)
    {
        M("userbankcard")->where(['userid' => $userid, 'id' => ['neq', $id]])->setField("mr", 0);
    }

    //2019-3-25 任梦龙：根据id获取单条记录
    public static function getInfo($id)
    {
        return M("userbankcard")->where("id=" . $id)->find();
    }

    //2019-3-25 任梦龙：根据id软删除记录
    public static function delUserBank($id)
    {
        return M("userbankcard")->where("id=" . $id)->setField('del', 1);
    }
}

