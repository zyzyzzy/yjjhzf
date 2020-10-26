<?php

namespace User\Model;

use Think\Model;

//整Model2019-01-04汪桂芳创建
class DaifuModel extends Model
{
    //查询代付通道信息
    public static function getDaifuInfo($id)
    {
        return M('daifu')->where('id=' . $id)->find();
    }

    public static function getDaifuName($id)
    {
        return D('daifu')->where('id=' . $id)->getField('zh_payname');
    }

}