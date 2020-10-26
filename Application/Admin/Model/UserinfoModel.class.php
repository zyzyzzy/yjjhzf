<?php

namespace Admin\Model;

use Think\Model;

class UserinfoModel extends Model
{
    protected $_auto = array(
        ['sex', 0], //0 男,  1女
    );

    protected $_validate = array(   //自动验证
        ['website_name', 'require', '企业名称不能为空', 0],
        ['business_number', 'require', '工商注册号不能为空', 0],
        ['business_number', '', '工商注册号已被注册', 1, 'unique', 3],
        ['address', 'require', '公司地址不能为空', 0],
        ['register_time', 'require', '成立时间不能为空', 0],
        ['legal_person', 'require', '法人代表不能为空', 0],
        ['contacts', 'require', '联系人姓名不能为空', 0],
        ['document_type', 'require', '证件类型不能为空', 0],
        ['document_number', 'require', '证件号码不能为空', 0],
        ['document_number', '', '证件号码已经存在', 0, 'unique', 3],
        ['sex', 'require', '性别不能为空', 0],
        ['birthday', 'require', '生日不能为空', 0],
        ['phone_number', 'require', '手机号不能为空', 0],
        ['phone_number', '', '手机号已经存在', 0, 'unique', 3],
        ['phone_number', 'checktel', '手机号格式错误!', 0, 'callback', 3],
        ['email', 'require', '电子邮箱不能为空', 0],
        ['email', 'email', '电子邮箱格式错误', 0],
        ['email', '', '电子邮箱已注册', 0, 'unique', 3],
        ['qq_number', 'require', 'QQ号不能为空', 0],
        ['qq_number', '', 'QQ号已被注册', 0, 'unique', 3],
        ['qq_number', 'checkqq', 'QQ号格式错误!', 0, 'callback', 3],
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
}