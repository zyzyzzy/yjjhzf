<?php
/**
 * 2019-3-12 任梦龙
 * 账户密钥路径模型
 */
namespace Admin\Model;

use Think\Model;

class PayapiaccountkeyModel extends Model
{

    /**
     * 添加账户密钥记录
     */
    public static function addaccountKeyPath($data)
    {
        M('payapiaccountkey')->add($data);
    }

    /**
     * 修改账户密钥记录
     * @param $account_id
     * @param $account_path_name：账户密钥路径字段名称
     * @param $file_path：账户密钥路径
     */
    //2019-1-8 任梦龙：修改账户密钥文件路径
    public static function editaccountKeyPath($account_id, $account_path_name, $file_path)
    {
        M('payapiaccountkey')->where('payapiaccount_id = ' . $account_id)->setField($account_path_name, $file_path);
    }

    /**
     * 查找账户密钥记录
     * @param $account_id：账户id
     * @return mixed
     */
    //2019-1-8 任梦龙：查看账户密钥路径表中的记录
    public static function findCount($account_id)
    {
        return M('payapiaccountkey')->where('payapiaccount_id = ' . $account_id)->count();
    }

}

