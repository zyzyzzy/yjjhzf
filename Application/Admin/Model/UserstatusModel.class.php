<?php
/**
 *2019-3-8 任梦龙：用户状态模型
 */

namespace Admin\Model;

use Think\Model;

class UserstatusModel extends Model
{
    /**
     * 查询列表
     */
    public static function selectUserStatus()
    {
        return M('userstatus')->select();
    }
}
