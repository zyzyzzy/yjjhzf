<?php
/**
 * Created by PhpStorm.
 * User: 任梦龙
 * Date: 2019/3/12
 * Time: 下午 1:23
 * 用户密钥路径表模型
 */

namespace Admin\Model;

use Think\Model;

class UsersecretkeypathModel extends Model
{
    /**
     * 添加用户密钥记录
     */
    public static function addUserKeyPath($data)
    {
        M('usersecretkeypath')->add($data);
    }

    /**
     * 修改用户密钥记录
     * @param $user_id
     * @param $user_path_name：用户密钥路径字段名称
     * @param $file_path：用户密钥路径
     */
    //2019-1-8 任梦龙：修改用户密钥文件路径
    public static function editUserKeyPath($user_id, $user_path_name, $file_path)
    {
        M('usersecretkeypath')->where('user_id = ' . $user_id)->setField($user_path_name, $file_path);
    }

    /**
     * 查找用户密钥记录
     * @param $user_id：用户id
     * @return mixed
     */
    //2019-1-8 任梦龙：查看用户密钥路径表中的记录
    public static function findCount($user_id)
    {
        return M('usersecretkeypath')->where('user_id = ' . $user_id)->count();
    }


}