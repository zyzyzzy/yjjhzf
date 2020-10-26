<?php

namespace User\Model;

use Think\Model;

//整Model2019-01-04汪桂芳创建
class SettledateModel extends Model
{
    //查询所有的节假日
    public static function getAllHoliday()
    {
        $where = [
            'user_id' => 0,
            'type' => 2
        ];
        return D('settledate')->where($where)->getField('date', true);
    }

    //查询所有排除的日期
    public static function getAllRemove()
    {
        $where = [
            'user_id' => 0,
            'type' => 1
        ];
        return D('settledate')->where($where)->getField('date', true);
    }

    public static function getDateCount($date, $type)
    {
        return D('settledate')->where(['user_id' => 0, 'type' => $type, 'date' => $date])->count();
    }
}