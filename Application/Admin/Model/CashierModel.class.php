<?php
/**
 * Created by PhpStorm.
 * User: 汪桂芳
 * Date: 2019/02/26
 * Time: 上午 10:00
 * 收银台设置模型
 */

namespace Admin\Model;

use Think\Model;

class CashierModel extends Model
{
    //获取用户的收银台设置
    public static function userCashierInfo($user_id)
    {
        return M('cashier')->where('user_id=' . $user_id)->find();
    }

    //修改数据
    public static function saveUserCashierInfo($id, $data)
    {
        if (self::getCount($data['user_id'])) {
            return M('cashier')->where('id=' . $id)->save($data);
        } else {
            return M('cashier')->add($data);
        }
    }

    //修改应用类型
    public static function saveUserCashierType($user_id, $type)
    {
        if (self::getCount($user_id)) {
            return M('cashier')->where('user_id=' . $user_id)->save(['type' => $type]);
        } else {
            return M('cashier')->add(['user_id' => $user_id, 'type' => $type]);
        }
    }

    public static function getCount($user_id)
    {
        return D('cashier')->where(['user_id' => $user_id])->count();
    }

    public static function getCashlogo($user_id)
    {
        return D('cashier')->where(['user_id' => $user_id])->getField('logo');
    }
}