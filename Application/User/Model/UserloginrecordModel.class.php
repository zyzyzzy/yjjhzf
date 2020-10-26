<?php
/**
 * Created by PhpStorm.
 * User: 任梦龙
 * Date: 2019/1/22/022
 * Time: 下午 2:21
 * 用户登录记录表模型
 */

namespace User\Model;

use Think\Model;

class UserloginrecordModel extends Model
{
    /**
     * 添加数据
     */
    public static function addUserRecrod($data)
    {
        D('userloginrecord')->add($data);
    }

    //获取用户登录的最新记录
    public static function getLatestrecord($userid)
    {
        $data = D('userloginrecord')->where('userid='.$userid)->order('id DESC')->limit(2)->select();
        return $data[1];
    }

}
