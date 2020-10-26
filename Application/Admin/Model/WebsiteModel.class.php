<?php

namespace Admin\Model;

use Think\Model;

class WebsiteModel extends Model
{
    //2019-4-19 rml：优化
    protected $_validate = [
        ['websitename', 'require', '网站名称不能为空！', 0],
        ['tel', 'require', '客服电话不能为空！', 0],
        ['qq', 'require', '客服QQ不能为空！', 0],
        ['register_number', 'require', '备案号不能为空！', 0],
        ['statistic_code', 'require', '统计代码不能为空！', 0],
        ['all_valve', 'number', '数据格式', 0],
        ['viwe_valve', 'number', '格式错误', 0],
        ['api_valve', 'number', '格式错误', 0],
        ['reg_valve', 'number', '格式错误', 0],
        ['home_domain', 'require', '前台域名不能为空！', 0],
        ['admin_domian', 'require', '后台域名不能为空！', 0],
        ['pay_domain', 'require', '支付域名不能为空！', 0],
        ['back_domain', 'require', '回调域名不能为空！', 0],
        ['home_http', 'require', '传输协议不能为空！', 0],
        ['admin_http', 'require', '传输协议不能为空！', 0],
        ['pay_http', 'require', '传输协议不能为空！', 0],
        ['back_http', 'require', '传输协议不能为空！', 0],
        ['commission_level', 'require', '提成等级不能为空！', 0],
        ['commission_level', 'verifyZhengshu', '提成等级需为大于0的正整数,范围在1-100之间！', 0, 'callback', 3],
        ['login_count', 'require', '登录次数不得为空！', 0],
        ['login_count', 'loginCount', '登录次数需为大于0的正整数,范围在3-100之间！', 0, 'callback', 3],
        ['set_time', 'require', '请选择等待时间！', 0],
    ];

    public static function getWebsite()
    {
        return D('website')->order('id DESC')->find();
    }

    public static function verifyAdminPassword($admin_password)
    {
        if (md5($admin_password) == C("ADMIN_PWD")) {
            return true;
        }
        return false;
    }

    protected function verifyZhengshu($name)
    {
        if (!preg_match("/^[1-9]{1,}[\d]*$/", $name)) {
            return false;
        }
        if (intval($name) > 100) {
            return false;
        }
        return true;
    }

    protected function loginCount($login_count)
    {
        if (!preg_match("/^[1-9]{1,}[\d]*$/", $login_count)) {
            return false;
        }
        if (intval($login_count) < 3 && intval($login_count) > 100) {
            return false;
        }
        return true;
    }
}