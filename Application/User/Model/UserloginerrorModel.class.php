<?php
/**
 * Created by PhpStorm.
 * User: 任梦龙
 * Date: 2019/1/23/023
 * Time: 下午 2:51
 * 用户登录错误信息模型
 */

namespace User\Model;

use Think\Model;

class UserloginerrorModel extends Model
{
    /**
     * 添加错误信息
     */
    public static function addErrorMsg($data)
    {
        D('userloginerror')->add($data);
    }


    /**
     * 2019-1-28 任梦龙：添加查询记录数
     */
    public static function getCount($where)
    {
        return M('userloginerror')->where($where)->count();
    }

}