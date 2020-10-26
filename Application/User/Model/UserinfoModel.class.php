<?php

namespace User\Model;

use Think\Model;

//整Model2018-12-27汪桂芳创建
class UserinfoModel extends Model
{
    //0 男,  1女
    protected $_auto = array(
        ['sex', 0],
    );

    protected $_validate = array(
        ['website_name', 'require', '企业名称不能为空', 1],
        ['business_number', 'require', '工商注册号不能为空', 1],
        ['business_number', '', '工商注册号已被注册', 1, 'unique', 3],
        ['address', 'require', '公司地址不能为空', 1],
        ['register_time', 'require', '成立时间不能为空', 1],
        ['legal_person', 'require', '法人代表不能为空', 1],
        ['contacts', 'require', '联系人姓名不能为空', 1],
        ['document_type', 'require', '证件类型不能为空', 1],
        ['document_number', 'require', '证件号码不能为空', 1],
        ['document_number', '', '证件号码已经存在', 1, 'unique', 3],
        ['sex', 'require', '性别不能为空', 1],
        ['birthday', 'require', '生日不能为空', 1],
        ['phone_number', 'require', '手机号不能为空', 1],
        ['phone_number', '', '手机号已经存在', 1, 'unique', 3],
        ['phone_number', 'checktel', '手机号格式错误!', 1, 'callback', 3],
        ['email', 'require', '电子邮箱不能为空', 1],
        ['email', 'email', '电子邮箱格式错误', 1],
        ['email', '', '电子邮箱已注册', 1, 'unique', 3],
        ['qq_number', 'require', 'QQ号不能为空', 1],
        ['qq_number', '', 'QQ号已被注册', 1, 'unique', 3],
        ['qq_number', 'checkqq', 'QQ号格式错误!', 1, 'callback', 3],
    );

    protected function checktel($mobile)
    {
        if (preg_match("/^1[34578]\d{9}$/", $mobile)) {
            return true;
        } else {
            return false;
        }
    }

    protected function checkqq($qq)
    {
        if (preg_match("/^[1-9]{5,14}$/", $qq)) {
            return true;
        }
        return false;
    }

    public static function getUserinfo($userid)
    {
        return D('userinfo')->where('userid=' . $userid)->find();
    }

    //2019-4-2 任梦龙：根据邮箱查询记录
    public static function getUserid($email)
    {
        return M('userinfo')->where(['email' => $email])->getField('userid');
    }

    //2019-4-2 任梦龙:记录发送邮箱时间
    public static function editEamiltime($user_id)
    {
        M('userinfo')->where(['userid' => $user_id])->setField('email_time', date('Y-m-d H:i:s'));
    }

    //2019-4-2 任梦龙:获取发送邮箱时间
    public static function getEamiltime($user_id)
    {
        return M('userinfo')->where(['userid' => $user_id])->getField('email_time');
    }


}