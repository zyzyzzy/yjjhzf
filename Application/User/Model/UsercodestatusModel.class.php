<?php
/**
 *2019-3-6 任梦龙：邀请码状态模型
 */

namespace User\Model;

use Think\Model;

class UsercodestatusModel extends Model
{
    /**
     * 查询列表
     */
    public static function codeStatusList()
    {
        return M('usercodestatus')->select();
    }

    /**
     * 查询邀请码可使用和禁止使用的状态
     */
    public static function selectInviteStatus()
    {
        $where['id'] = ['in','1,4'];
        return D('usercodestatus')->where($where)->select();
    }
}
