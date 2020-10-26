<?php

/**
 * 扫码模板模型
 */

namespace Admin\Model;

use Think\Model;

class QrcodetemplateModel extends Model
{
    protected $_scope = array(
        // 命名范围normal
        'default_scope' => array(
            'field' => [
                "id"
                , "img_name"
                , "template_name"
                , "title"
                , "default"
                , "getpayapiclassname(payapiclass_id) as payapiclass_name"
            ],
        ),
    );

    //2019-4-18 rml :自动验证
    protected $_validate = array(
        ['payapiclass_id', 'require', '请选择通道分类', 0],
        ['title', 'require', '标题不能为空', 0],
        ['title', '', '标题已存在！', 0, 'unique', 3],
        ['title', '2,100', '标题长度在2-100字符之间！', 0, 'length', 3],
        ['template_name', 'require', '前台模板文件名称不能为空', 0],
        ['template_name', '', '前台模板文件名称已存在！', 0, 'unique', 3],
        ['template_name', '2,100', 'PC端模板文件名长度在2-100字符之间！', 0, 'length', 3],
        ['img_name', 'require', '图片未上传', 0],
        ['img_name', '', '图片名称已存在！', 0, 'unique', 3],
    );

    //获取模板列表
    public static function getQrcodeList()
    {
        return M('qrcodetemplate')->select();
    }

    //获取某分类模板列表
    public static function getClassQrcodeList($payapiclass_id)
    {
        return M('qrcodetemplate')->where('payapiclass_id='.$payapiclass_id)->select();
    }

    //获取某分类的默认模板
    public static function getClassDefaultQrcode($payapiclass_id)
    {
        return M('qrcodetemplate')->where('payapiclass_id='.$payapiclass_id.' and default=1')->find();
    }

    //查找单条记录
    public static function getOneTemplate($id)
    {
        return M('qrcodetemplate')->where('id='.$id)->find();
    }

    //查找模板名称
    public static function getTemplateName($id)
    {
        return M('qrcodetemplate')->where('id='.$id)->getField('title');
    }

    //删除数据
    public static function delTemplate($id)
    {
        $info = self::getOneTemplate($id);
        $res = M('qrcodetemplate')->where('id = '.$id)->delete();
        if($res){
            //删除图片
            unlink($info['img_name']);
        }
        return $res;
    }

    //批量删除
    public static function delAllTemplate($idstr)
    {
        $where['id']=['in',$idstr];
        $all = M("qrcodetemplate")->where($where)->select();
        $res = M("qrcodetemplate")->where($where)->delete();
        if($res){
            foreach ($all as $k=>$v){
                unlink($v['img_name']);
            }
        }
        return $res;
    }

    //添加数据
    public static function addTemplate($data)
    {
        return M('qrcodetemplate')->add($data);
    }

    //修改数据
    public static function saveTemplate($id,$data)
    {
        return M('qrcodetemplate')->where('id='.$id)->save($data);
    }

    //修改默认状态
    public static function editTemplateDefault($id, $default)
    {
        $res = M("qrcodetemplate")->where("id=" . $id)->setField("default", $default);
        if($res){
            if($default==1){
                //将该类型的其他改为非默认
                $payapiclass_id = M("qrcodetemplate")->where("id=".$id)->getField("payapiclass_id");
                M("qrcodetemplate")->where("payapiclass_id=".$payapiclass_id." and id <> ".$id)->setField("default",0);
            }
        }
        return $res;
    }

    //查询是否有用户或通道在应用某模板
    public static function checkUserPayapi($id)
    {
        $res1 = M('payapi')->where('qrcodeid='.$id)->select();
        $res2 = M('userpayapiclass')->where('qrcode=0 and qrcode_template_id='.$id)->select();
        $res3 = M("qrcodetemplate")->where("id=" . $id)->getField('default');
        if($res1 || $res2 || $res3){
            return false;
        }else{
            return true;
        }
    }
}

