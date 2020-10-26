<?php

namespace Admin\Model;

use Think\Model;

//2019-02-26 任梦龙创建管理员操作记录模型
class AdminoperaterecordModel extends Model
{
    /**
     * 存入数据
     */
    public static function addLoginTemp($data)
    {
        M('adminoperaterecord')->add($data);
    }



}