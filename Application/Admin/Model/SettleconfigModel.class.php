<?php

namespace Admin\Model;

use Think\Model;

class SettleconfigModel extends Model
{
    public static function getInfoByDaifuid($daifu_id)
    {
        return D('settleconfig')->where('daifu_id=' . $daifu_id)->select();
    }

    //查询系统结算设置的开关
    public static function getSysSettleStatus()
    {
        return M('settleconfig')->where('user_id=0')->getField('status');
    }

    //修改系统结算设置的开关
    public static function editSysSettleStatus($status)
    {
        return M('settleconfig')->where('user_id=0')->setField('status', $status);
    }

    //2019-4-18 rml:根据条件查询记录
    public static function getSettleConfig($where)
    {
        return M('settleconfig')->where($where)->find();
    }

    public static function editSettleConfig($where,$data)
    {
        return D('settleconfig')->where($where)->save($data);
    }

    public static function addSettleConfig($data)
    {
        return D('settleconfig')->add($data);
    }

    //2019-4-18 rml:将用户模型数据移到系统
    //查询用户的结算配置信息
    public static function getSettleConfigInfo($user_id)
    {
        return M('settleconfig')->where('user_id=' . $user_id)->find();
    }

    public static function getUserSettleconfig($userid)
    {
        $settleconfig = static::getSettleConfigInfo($userid);
        //结算运用系统设置
        if ($settleconfig['user_type'] == 0) {
            $settleconfig = static::getSettleConfigInfo(0);
        }
        return $settleconfig;
    }
}