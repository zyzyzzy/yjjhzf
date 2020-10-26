<?php
/**
 * 2019-3-28 任梦龙
 * 用户自助通道账户密钥路径模型
 */

namespace User\Model;

use Think\Model;

class PayapiaccountkeyModel extends Model
{

    /**
     * 添加账户密钥记录
     */
    public static function addaccountKeyPath($data)
    {
        return ('payapiaccountkey')->add($data);
    }

    /**
     * 修改账户密钥记录
     * @param $account_id
     * @param $account_path_name：账户密钥路径字段名称
     * @param $file_path：账户密钥路径
     */
    public static function editaccountKeyPath($account_id, $account_path_name, $file_path)
    {
        return M('payapiaccountkey')->where('payapiaccount_id = ' . $account_id)->setField($account_path_name, $file_path);
    }

    /**
     * 查找账户密钥记录
     * @param $account_id：账户id
     * @return mixed
     */
    public static function findCount($account_id)
    {
        return M('payapiaccountkey')->where('payapiaccount_id = ' . $account_id)->count();
    }

}

