<?php
namespace User\Model;

use Think\Model;
//整Model2019-01-04汪桂芳创建
class SettleconfigModel extends Model
{
    //查询用户的结算配置信息
    public static function getSettleConfigInfo($user_id)
    {
        return M('settleconfig')->where('user_id='.$user_id)->find();
    }

//    public static function getUserSettleconfig($userid)
//    {
//        $settleconfig = static::getSettleConfigInfo($userid);
//        //结算运用系统设置
//        if($settleconfig['user_type']==0){
//            $settleconfig =  static::getSettleConfigInfo(0);
//        }
//        return $settleconfig;
//    }

    public static function getSettleConfig($where)
    {
        return D('settleconfig')->where($where)->find();
    }

    public static function getUserSettleConfig($user_id)
    {
        $user_config = self::getSettleConfig(['user_id' => $user_id, 'user_type' => 1]);
        if ($user_config) {
            $settle_config = $user_config;
        } else {
            $settle_config = self::getSettleConfig(['user_id' => 0, 'user_type' => 0]);
        }
        return $settle_config;
    }
}