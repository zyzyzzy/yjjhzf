<?php
/**
 *2019-3-6 任梦龙：用户类型模型
 */

namespace User\Model;

use Think\Model;

class UsertypeModel extends Model
{
    /**
     * 查询列表
     */
    public static function selectUserType()
    {
        return M('usertype')->select();
    }
}
