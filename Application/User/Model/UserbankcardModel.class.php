<?php

namespace User\Model;

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

    protected $_validate = array(   //自动验证
        array('userid', 'require', '用户ID不能为空！', 1),
        array('bankname', 'require', '银行名称不能为空！', 0),
        array('banknumber', 'require', '银行卡号不能为空！', 0),
        array('banknumber', 'checkBankNum', '银行卡号已存在', 0, 'callback', 3),
        array('bankmyname', 'require', '开户人姓名不能为空！', 0),
        array('bankmyname', '2,20', '开户人姓名长度在2-20字符之间!', 0, 'length', 3),
        array('shenfenzheng', 'require', '身份证号不能为空！', 0),
        array('shenfenzheng', 'checkShenfen', '身份证号已存在', 0, 'callback', 3),
        array('shenfenzheng', 'checkshenfenzheng', '身份证号格式错误!', 0, 'callback', 3),
        array('tel', 'require', '手机号不能为空！', 0),
        array('tel', 'checktel', '手机号格式错误!', 0, 'callback', 3),
        array('tel', 'checkPhone', '手机号已存在', 0, 'callback', 3),


    );

    //获取银行编码
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

    //查询用户所有启用的银行卡
    public static function getUserBanks($user_id)
    {
        $where = [
            'userid' => $user_id,
            'status' => 1,
            'del' => 0,
        ];
        return D('userbankcard')->where($where)->field('id,bankname,banknumber,mr')->select();
    }

    //查询银行名称
    public static function getUserBankname($id)
    {
        return D('userbankcard')->where('id=' . $id)->getField('bankname');
    }

    public static function findUserBank($id)
    {
        return D('userbankcard')->where('id=' . $id)->find();
    }

    public static function saveUserBank($id, $data)
    {
        return D('userbankcard')->where('id=' . $id)->save($data);
    }

    public static function editMrstatus($userid, $id)
    {
        return D('userbankcard')->where(['userid' => $userid, 'id' => ['NEQ', $id]])->setField('mr', 0);
    }
}

