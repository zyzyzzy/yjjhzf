<?php
/**
 * Created by PhpStorm.
 * User: 任梦龙
 * Date: 2019/1/28/028
 * Time: 下午 5:22
 * 判断用户是否存在同一个账号在两处登录
 */

namespace User\Model;

use Think\Model;

class UsersessionidModel extends Model
{
    /**
     * 查询
     */
    public static function getSessionCount($where)
    {
        return M('usersessionid')->where($where)->count();
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
    public static function getSessionId($where)
    {
        return M('usersessionid')->where($where)->getField('session_id');
    }




}