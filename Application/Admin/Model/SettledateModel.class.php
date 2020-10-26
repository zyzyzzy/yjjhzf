<?php

namespace Admin\Model;

use Think\Model;

class SettledateModel extends Model
{
    public static function addInfo($data)
    {
        return M('settledate')->add($data);
    }

    public static function getDateType($id)
    {
        return M('settledate')->where('id=' . $id)->getField('type');
    }

    public static function getDate($id)
    {
        return M('settledate')->where('id=' . $id)->getField('date');
    }

    public static function getCount($date, $type)
    {
        return D('settledate')->where(['user_id' => 0, 'type' => $type, 'date' => $date])->count();
    }

    public static function getSettleDate($type)
    {
        return D('settledate')->where(['user_id' => 0, 'type' => $type])->field('id,date,remarks')->order('id DESC')->select();
    }
}