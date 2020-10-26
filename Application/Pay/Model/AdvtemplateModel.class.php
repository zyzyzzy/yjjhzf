<?php
namespace Pay\Model;

use Think\Model;

class AdvtemplateModel extends Model
{
    //查询默认模板
    public static function getDefaultAdv()
    {
        return M('advtemplate')->where('`default`=1')->getField('id');
    }

    //查询模板信息
    public static function getAdvInfo($id)
    {
        return M('advtemplate')->where('id='.$id)->find();
    }
}