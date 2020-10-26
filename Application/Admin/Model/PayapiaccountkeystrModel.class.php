<?php

namespace Admin\Model;

use Think\Model;

/**
 * 2019-3-12 rml
 * 账户密钥文件模型
 */
class PayapiaccountkeystrModel extends Model
{
    //2019-8-27 rml:密钥可以为空，去掉密钥的自动验证条件

    //查找单条记录
    public static function getkeyInfo($payapi_account_id)
    {
        return M('payapiaccountkeystr')->where('del = 0 AND payapi_account_id = ' . $payapi_account_id)->find();
    }

    //2019-4-1 任梦龙：修改账号密钥数据
    public static function edutAccountKey($id, $data)
    {
        return M('payapiaccountkeystr')->where(['id' => $id])->save($data);
    }

    //2019-4-4 任梦龙：添加数据
    public static function addAccountKey($data)
    {
        M('payapiaccountkeystr')->add($data);
    }


}

