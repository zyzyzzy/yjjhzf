<?php
namespace Pay\Model;

use Think\Model;

class PayapijumpModel extends Model
{
    //判断通道是否需要跳转到广告页面
    public static function getPayapiUser($payapi_id,$user_id)
    {
        return M('payapijump')->where('payapi_id='.$payapi_id.' and user_id='.$user_id)->find();
    }
}