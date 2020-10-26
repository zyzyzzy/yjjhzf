<?php

namespace Admin\Model;

use Think\Model;

class DatatemplateModel extends Model
{
    //查找单条记录
    public static function getOneTemplate($id)
    {
        return M('datatemplate')->where('id='.$id)->find();
    }
    //查找管理员所有记录
    public static function getAllTemplate()
    {
        return M('datatemplate')->where('admin_user="admin" and del=0')->select();
    }

    //删除数据
    public static function delTemplate($id)
    {
        $info = self::getOneTemplate($id);
        $res = M('datatemplate')->where('id = '.$id)->delete();
        if($res){
            //删除图片
            unlink($info['img_name']);
            //删除应用此模板的数据
            M('datatemplateuser')->where('datatemplate_id='.$id)->delete();
        }
        return $res;
    }

    //批量删除
    public static function delAllTemplate($idstr)
    {
        $where['id']=['in',$idstr];
        $all = M("datatemplate")->where($where)->select();
        $res = M("datatemplate")->where($where)->delete();
        if($res){
            foreach ($all as $k=>$v){
                unlink($v['img_name']);
            }
            //删除应用此模板的数据
            $wh['datatemplate_id']=['in',$idstr];
            M('datatemplateuser')->where($wh)->delete();
        }
        return $res;
    }

    //添加数据
    public static function addTemplate($data)
    {
        return M('datatemplate')->add($data);
    }

    //修改数据
    public static function saveTemplate($id,$data)
    {
        return M('datatemplate')->where('id='.$id)->save($data);
    }

    //获取模板标题
    public static function getTemplateName($id)
    {
        return M('datatemplate')->where('id='.$id)->getField('title');
    }
}