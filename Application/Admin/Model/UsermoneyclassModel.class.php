<?php
/**
 *2019-3-6 任梦龙：用户的到账方案模型
 */

namespace Admin\Model;

use Think\Model;

class UsermoneyclassModel extends Model
{
    /**
     * 查询列表
     */
    public static function selectUserType()
    {
        return M('usertype')->select();
    }
}
