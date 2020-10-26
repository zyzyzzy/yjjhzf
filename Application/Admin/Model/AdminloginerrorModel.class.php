<?php
/**
 * Created by PhpStorm.
 * User: 任梦龙
 * Date: 2019/2/15
 * Time: 下午 3:30
 * 管理员登录错误信息模型
 */

namespace Admin\Model;

use Think\Model;

class AdminloginerrorModel extends Model
{
    /**
     * 添加错误信息
     */
    public static function addErrorMsg($data)
    {
        D('adminloginerror')->add($data);
    }


    /**
     * 添加查询记录数
     */
    public static function getCount($where)
    {
        return M('adminloginerror')->where($where)->count();
    }

}