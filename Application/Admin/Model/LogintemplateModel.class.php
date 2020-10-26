<?php

namespace Admin\Model;

use Think\Model;

class LogintemplateModel extends Model
{
    //2019-4-23 rml:添加自动添加与自动验证
    protected $_auto = array(
        array('date_time', 'YmdHis', 1, 'function'),
//        array('default', 0),
    );

    protected $_validate = array(
        array('type', 'require', '请选择登录类型！', 0),
        array('temp_name', 'require', '模板文件名不能为空！', 0),
        array('temp_name', '2,20', '模板文件名长度在2-20字符之间！', 0, 'length', 3),
        array('temp_name', 'checkName', '模板文件名已存在！', 0, 'callback', 3),
    );

    protected function checkName($name)
    {
        $id = I('request.id', 0);
        $type = I('request.type');
        if ($id) {
            $count = D('logintemplate')->where(['temp_name' => $name, 'type' => $type, 'id' => ['NEQ', $id]])->count();
        } else {
            $count = D('logintemplate')->where(['temp_name' => $name, 'type' => $type])->count();
        }
        if ($count) {
            return false;
        }
        return true;

    }


    /**
     * 查询总记录数
     */
    public static function getCount($where)
    {
        return M('logintemplate')->where($where)->count();
    }

    /**
     * 数据分页
     */
    public static function getPageData($where, $page, $limit)
    {
        return M('logintemplate')->where($where)->page($page, $limit)->order('id DESC')->select();
    }

    /**
     * 查询单条记录
     */
    public static function getTempInfo($id)
    {
        return M('logintemplate')->where(['id' => $id])->find();
    }

    /**
     * 查询多条记录:只获取id,模板文件名和模板图片路径
     */
    public static function getMuiltinfo($id_str)
    {
        return M('logintemplate')->field('id,img_path')->where(['id' => ['IN', $id_str]])->select();
    }

    /**
     * 删除单条记录
     */
    public static function delTemplate($id)
    {
        return M('logintemplate')->where(['id' => $id])->delete();
    }

    /**
     * 批量删除
     */
    public static function delAllTemplate($id_str)
    {
        return M("logintemplate")->where(['id' => ['IN', $id_str]])->delete();
    }

    /**
     * 修改默认模板
     */
    public static function editTemplateDefault($id, $default)
    {
        return M("logintemplate")->where(['id' => $id])->setField("default", $default);
    }

    /**
     * 2019-3-11 任梦龙：根据自定义条件查询信息
     */
    public static function getTempName($where)
    {
        return M('logintemplate')->where($where)->field('temp_name,img_path')->find();
    }


}