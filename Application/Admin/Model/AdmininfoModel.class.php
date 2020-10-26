<?php

namespace Admin\Model;

use Think\Model;

class AdmininfoModel extends Model
{
    //2019-4-22 rml：优化模型
    protected $_auto = array(
        ['update_time', 'YmdHis', 3, 'function'], // 对update_time字段在新增或更新的时候写入当前时间戳
    );

    //自动验证
    protected $_validate = array(
        ['sex', 'require', '性别不能为空', 0],
        ['phone', 'require', '手机号不能为空', 0],
        ['phone', 'checktel', '手机号格式错误!', 0, 'callback', 3],
        ['phone', '', '手机号已注册', 0, 'unique', 3],
        ['email', 'require', '电子邮箱不能为空', 0],
        ['email', 'email', '电子邮箱格式错误', 0],
        ['email', '', '电子邮箱已注册', 0, 'unique', 3],
        ['qq_number', 'require', 'QQ号不能为空', 0],
        ['qq_number', '', 'QQ号已注册', 0, 'unique', 3],
    );

    protected function checktel($mobile)
    {
        if (preg_match("/^1[34578]\d{9}$/", $mobile)) {
            return true;
        } else {
            return false;
        }
    }

    public static function getAdmininfo($admin_id)
    {
        return D('admininfo')->where(['admin_id' => $admin_id])->find();
    }

    /**
     * 2019-2-15 任梦龙：查询记录
     */
    public static function getCount($admin_id)
    {
        return D('admininfo')->where(['admin_id' => $admin_id])->count();
    }

    /**2019-2-15 任梦龙：获取邮箱
     */
    public static function getEmail($admin_id)
    {
        return D('admininfo')->where(['admin_id' => $admin_id])->getField('email');
    }

}