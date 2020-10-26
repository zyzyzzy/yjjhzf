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

class DatatemplateuserModel extends Model
{
    //查找用户所选的统计模板
    public static function getAllTemplate($user_id)
    {
        $where = [
            'admin_user'=>'user',
            'user_id'=>$user_id
        ];
        return M('datatemplateuser')->where($where)->select();
    }

    //判断某模板是否存在
    public static function getOneTemplate($user_id,$template_id)
    {
        $where = [
            'admin_user'=>'user',
            'user_id'=>$user_id,
            'datatemplate_id'=>$template_id
        ];
        return M('datatemplateuser')->where($where)->find();
    }

    //添加数据
    public static function addTemplate($user_id,$template_id)
    {
        $add = [
            'admin_user'=>'user',
            'user_id'=>$user_id,
            'datatemplate_id'=>$template_id
        ];
        return M('datatemplateuser')->add($add);
    }

    //删除数据
    public static function deleteTemplate($user_id,$template_id)
    {
        $del = [
            'admin_user'=>'user',
            'user_id'=>$user_id,
            'datatemplate_id'=>['in',$template_id]
        ];
        return M('datatemplateuser')->where($del)->delete();
    }
}