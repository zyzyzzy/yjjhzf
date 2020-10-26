<?php
namespace Pay\Model;

use Think\Model;

class PayapiModel extends Model
{
    //判断通道是否需要跳转到广告页面
    public static function getPayapiInfo($id)
    {
        return M('payapi')->where('id='.$id)->field('jump_type,adv_templateid')->find();
    }
}