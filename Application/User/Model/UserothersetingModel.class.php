<?php
/**
 * Created by PhpStorm.
 * User: 任梦龙
 * Date: 2019/1/28
 * Time: 上午 11:21
 * 用户其他设置表的模型--针对用户的
 */

namespace User\Model;

use Think\Model;

class UserothersetingModel extends Model
{
    /**
     * 查询数据
     */
    //2019-2-12 任梦龙：修改查询方法,修改模型方法名称
    public static function getLoginCount()
    {
        return M('userotherseting')->field('login_count,set_time,set_name')->order('id DESC')->limit(1)->select();
    }




}