<?php
/**
 * Created by PhpStorm.
 * User: 汪桂芳
 * Date: 2019/02/12
 * Time: 15:10
 * 用户统计模板模型
 */

namespace User\Model;

use Think\Model;

class DatatemplateModel extends Model
{
    //查找所有记录
    public static function getAllTemplate()
    {
        return M('datatemplate')->where('admin_user="user" and del=0')->select();
    }
}