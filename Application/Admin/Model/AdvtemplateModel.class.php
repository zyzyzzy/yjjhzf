<?php

/**
 * 广告模板模型
 */

namespace Admin\Model;

use Think\Model;

class AdvtemplateModel extends Model
{
    //2019-4-18 rml :自动验证
    protected $_validate = array(
        ['title', 'require', '标题不能为空', 0],
        ['title', '', '标题已存在！', 0, 'unique', 3],
        ['title', '2,100', '标题长度在2-100字符之间！', 0, 'length', 3],
        ['pc_template_name', 'require', 'PC端模板文件名不能为空', 0],
        ['pc_template_name', '', 'PC端模板文件名已存在！', 0, 'unique', 3],
        ['pc_template_name', '2,100', 'PC端模板文件名长度在2-100字符之间！', 0, 'length', 3],
        ['wap_template_name', 'require', 'WAP端模板文件名不能为空', 0],
        ['wap_template_name', '', 'WAP端模板文件名已存在！', 0, 'unique', 3],
        ['wap_template_name', '2,100', 'WAP端模板文件名长度在2-100字符之间！', 0, 'length', 3],
    );

    //获取模板列表
    public static function getAdvTemplateList()
    {
        return M('advtemplate')->select();
    }

    //查找单条记录
    public static function getOneTemplate($id)
    {
        return M('advtemplate')->where('id=' . $id)->find();
    }

    //查找模板名称
    public static function getTemplateName($id)
    {
        return M('advtemplate')->where('id=' . $id)->getField('title');
    }

    //删除数据
    public static function delTemplate($id)
    {
        $info = self::getOneTemplate($id);
        $res = M('advtemplate')->where('id = ' . $id)->delete();
        if ($res) {
            //删除图片
            unlink($info['pc_img_name']);
            unlink($info['wap_img_name']);
        }
        return $res;
    }

    //批量删除
    public static function delAllTemplate($idstr)
    {
        $where['id'] = ['in', $idstr];
        $all = M("advtemplate")->where($where)->select();
        $res = M("advtemplate")->where($where)->delete();
        if ($res) {
            foreach ($all as $k => $v) {
                unlink($v['pc_img_name']);
                unlink($v['wap_img_name']);
            }
        }
        return $res;
    }

    //添加数据
    public static function addTemplate($data)
    {
        return M('advtemplate')->add($data);
    }

    //修改数据
    public static function saveTemplate($id, $data)
    {
        return M('advtemplate')->where('id=' . $id)->save($data);
    }

    //修改默认状态
    public static function editTemplateDefault($id, $default)
    {
        $res = M("advtemplate")->where("id=" . $id)->setField("default", $default);
        if ($res) {
            if ($default == 1) {
                //将该类型的其他改为非默认
                M("advtemplate")->where(['id' => ['NEQ', $id]])->setField("default", 0);
            }
        }
        return $res;
    }

    //查询文件名是否存在
    //2019-4-18 rml:修改为等查询
    public static function checkImgName($img_name)
    {
        $where = [
            'pc_img_name' => $img_name,
            'wap_img_name' => $img_name,
            '_logic' => 'OR',  //代表OR查询
        ];
        return M('advtemplate')->where($where)->select();
    }

    //查询是否有用户或通道在应用某模板
    public static function checkUse($id)
    {
        $res1 = M('payapi')->where('jump_type=1 and adv_templateid=' . $id)->select();
        $res2 = M("advtemplate")->where("id=" . $id)->getField('default');
        if ($res1 || $res2) {
            return false;
        } else {
            return true;
        }
    }
}

