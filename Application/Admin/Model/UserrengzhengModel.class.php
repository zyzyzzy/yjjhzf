<?php
/**
 *2019-3-8 任梦龙：用户认证模型
 */

namespace Admin\Model;

use Think\Model;

class UserrengzhengModel extends Model
{
    /**
     * 查询列表
     */
    public static function selectRengZheng()
    {
        return M('userrengzheng')->select();
    }

    public static function getRenzhenName($id)
    {
        return M('userrengzheng')->where(['id' => $id])->getField('userrengzhengname');
    }
}
