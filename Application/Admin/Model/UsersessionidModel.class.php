<?php
/**
 * Created by PhpStorm.
 * User: 任梦龙
 * Date: 2019/2/15
 * Time: 下午 2:30
 * 判断管理员是否存在同一个账号在两处登录
 */

namespace Admin\Model;

use Think\Model;

class UsersessionidModel extends Model
{
    /**
     * 查询
     */
    //2019-4-1 任梦龙：修改
    public static function getSessionCount($where)
    {
        return M('usersessionid')->where($where)->find();
    }

    /**
     * 增加
     */
    public static function addSessionId($data)
    {
        M('usersessionid')->add($data);
    }

    /**
     * 修改
     */
    public static function editSessionid($where, $data)
    {
        M('usersessionid')->where($where)->save($data);
    }

    /**
     * 获取session_id 的值
     */
    //2019-4-1 任梦龙：修改
    public static function getSessionId($admin_id)
    {
        return M('usersessionid')->where(['admin_id' => $admin_id, 'user_id' => 0, 'child_id' => 0])->getField('session_id');
    }


}