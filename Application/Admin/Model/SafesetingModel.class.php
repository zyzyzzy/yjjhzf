<?php
/**
 * 2019-3-5 任梦龙
 * 风控设置模型
 */
namespace Admin\Model;

use Think\Model;

class SafesetingModel extends Model
{
//自动验证
    protected $_validate = array(
        ['ip', 'require', 'ip不能为空', 0],
        ['ip', 'setIpRule', 'ip格式不正确', 0, 'callback', 1],
        ['ip', '', 'ip已存在', 0, 'unique', 1],
        ['domain', 'require', '域名不能为空', 0],
        ['domain', '', '域名已存在', 0, 'unique', 1],
        ['tel', 'require', '手机号不能为空', 0],
        ['tel','checktel','手机号格式错误!',0,'callback',1],
        ['tel', '', '手机号已存在', 0, 'unique', 1],
        ['idcard', 'require', '身份证号不能为空', 0],
        ['idcard', '', '身份证号已存在', 0, 'unique', 1],
        ['idcard','checkIdCard','身份证号格式错误!',0,'callback',1],
        ['banknum', 'require', '银行卡号不能为空', 0],
        ['banknum', '', '银行卡号存在', 0, 'unique', 1],
    );

    //自动完成
    protected $_auto = array(
        ['datetime', 'YmdHis', 3, 'function'],
    );

    //验证ip格式
    protected function setIpRule($ip)
    {
        if (preg_match("/^(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$/", $ip)) {
            return true;
        }
        return false;
    }

    //验证手机号格式
    protected function checktel($tel){
        if(preg_match("/^1[34578]\d{9}$/", $tel)){
            return true;
        }else{
            return false;
        }
    }

    //验证身份证格式
    protected function checkIdCard($idcard){
        if(preg_match("/(^\d{15}$)|(^\d{18}$)|(^\d{17}(\d|X|x)$)/", $idcard)){
            return true;
        }else{
            return false;
        }
    }
}