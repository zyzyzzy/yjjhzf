<?php

namespace Admin\Model;

use Think\Model;

class PayapiModel extends Model
{
    protected $_scope = array(
        // 命名范围normal
        'default_scope' => array(
            'field' => "id,zh_payname,en_payname,bieming_zh_payname,bieming_en_payname,daifuid,status,payapiclassid,getpayapishangjianame(payapishangjiaid) as `payapishangjiaid`,getpayapiclassname(payapiclassid) as `payapiclassname`",
        ),
    );

    //2019-4-18 rml：优化，验证唯一性
    protected $_validate = array(
        array('payapishangjiaid', 'require', '通道商家不能为空！', 0),
        array('payapiclassid', 'require', '通道分类不能为空！', 0),
        array('zh_payname', 'require', '原通道名称不能为空！', 0),
        array('zh_payname', 'checkName', '原通道名称已存在！', 0, 'callback', 3),
        array('zh_payname', '2,20', '原通道名称名称最短2个汉字，最长20个汉字！', 0, 'length', 1),

        array('en_payname', 'require', '通道编码不能为空！', 0),
        array('en_payname', 'checkBianma', '通道编码已存在！', 0, 'callback', 3),
        array('en_payname', '2,20', '通道编码最短2个字符，最长20个字符！', 0, 'length', 1),

        array('bieming_zh_payname', 'require', '自定义通道名称不能为空！', 0),
        array('bieming_zh_payname', 'checkSelfName', '自定义通道名称已存在！', 0, 'callback', 3),
        array('bieming_zh_payname', '2,20', '自定义通道名称最短2个汉字，最长20个汉字！', 0, 'length', 1),

        array('bieming_en_payname', 'require', '自定义通道编码不能为空！', 0),
        array('bieming_en_payname', 'checkSelfBm', '自定义通道编码已存在！', 0, 'callback', 3),
        array('bieming_en_payname', '2,20', '自定义通道编码最短2个字符，最长20个字符！', 0, 'length', 1),
    );

    //验证原通道名称唯一性
    protected function checkName($name)
    {
        $id = I('request.id', '');
        if ($id) {
            $count = D('payapi')->where(['zh_payname' => $name, 'del' => 0, 'id' => ['NEQ', $id]])->count();
        } else {
            $count = D('payapi')->where(['zh_payname' => $name, 'del' => 0])->count();
        }
        if ($count) {
            return false;
        }
        return true;
    }

    //验证通道编码唯一性
    protected function checkBianma($name)
    {
        $id = I('request.id', '');
        if ($id) {
            $count = D('payapi')->where(['en_payname' => $name, 'del' => 0, 'id' => ['NEQ', $id]])->count();
        } else {
            $count = D('payapi')->where(['en_payname' => $name, 'del' => 0])->count();
        }
        if ($count) {
            return false;
        }
        return true;
    }

    //验证自定义通道名称唯一性
    protected function checkSelfName($name)
    {
        $id = I('request.id', '');
        if ($id) {
            $count = D('payapi')->where(['bieming_zh_payname' => $name, 'del' => 0, 'id' => ['NEQ', $id]])->count();
        } else {
            $count = D('payapi')->where(['bieming_zh_payname' => $name, 'del' => 0])->count();
        }
        if ($count) {
            return false;
        }
        return true;
    }

    //验证自定义通道编码唯一性
    protected function checkSelfBm($name)
    {
        $id = I('request.id', '');
        if ($id) {
            $count = D('payapi')->where(['bieming_en_payname' => $name, 'del' => 0, 'id' => ['NEQ', $id]])->count();
        } else {
            $count = D('payapi')->where(['bieming_en_payname' => $name, 'del' => 0])->count();
        }
        if ($count) {
            return false;
        }
        return true;
    }


    public static function getUserPayapis($userid, $payapiclassid)
    {
        $userPayapiIds = M("userpayapiclass")->where([
            "userid" => ['eq', $userid],
            "payapiclassid" => ["eq", $payapiclassid]
        ])->getField("payapiid");
        if (count($userPayapiIds) < 1) {
            return [];
        }
        return M("payapi")->where(["id" => ['in', $userPayapiIds]])->select();
    }

    /**
     * 2019-2-18 任梦龙：更改状态
     */
    public static function editPayapiStatus($id, $status)
    {
        return M("payapi")->where("id=" . $id)->setField("status", $status);
    }

    /**
     * 2019-2-18 任梦龙：查询单挑记录
     */
    public static function getPayapiInfo($id)
    {
        return M("payapi")->where("id=" . $id)->find();
    }

    /**
     * 2019-2-18 任梦龙：软删除
     */
    public static function delPayapi($id)
    {
        return M("payapi")->where("id=" . $id)->setField('del', 1);
    }

    /**
     * 2019-2-18 任梦龙:获取模板
     */
    public static function getQrcode($id)
    {
        return M('payapi')->where('id=' . $id)->getField('qrcodeid');
    }

    /**
     * 2019-2-18 任梦龙:修改单条记录
     */
    public static function savePayapiInfo($id, $data)
    {
        return M('payapi')->where('id=' . $id)->save($data);
    }

    /**
     * 2019-2-19 任梦龙：获取商家下的交易通道
     */
    //2019-3-28 任梦龙：修改
    public static function getPayapiidList($where)
    {
        return M('payapi')->where($where)->field('id,zh_payname')->select();  //商家下的交易通道
    }

    /**
     * 2019-02-19 汪桂芳:查询通道分类
     */
    public static function getPayapiclassid($id)
    {
        return M('payapi')->where('id=' . $id)->getField('payapiclassid');
    }

    /**
     * 2019-02-20 汪桂芳:查询通道跳转设置
     */
    public static function getPayapiJump($id)
    {
        return M('payapi')->where('id=' . $id)->getField('jump_type');
    }

    /**
     * 2019-02-20 汪桂芳:通道跳转设置
     */
    public static function setPayapiJump($id, $jump)
    {
        return M('payapi')->where('id=' . $id)->setField('jump_type', $jump);
    }

    /**
     * 2019-03-20 汪桂芳:查询通道名称
     */
    public static function getPayapiName($id)
    {
        return M('payapi')->where('id=' . $id)->getField('zh_payname');
    }

    /**
     * 2019-04-03 汪桂芳:查询通道的广告模板
     */
    public static function getPayapiAdvTempalte($id)
    {
        return M('payapi')->where('id=' . $id)->getField('adv_templateid');
    }

    /**
     * 2019-04-03 汪桂芳:设置通道的广告模板
     */
    public static function setPayapiAdvTempalte($id, $adv_templateid)
    {
        return M('payapi')->where('id=' . $id)->setField('adv_templateid', $adv_templateid);
    }

    //2019-4-4 任梦龙：根据id获取字段值
    public static function getPayapiField($id, $field_name)
    {
        return M('payapi')->where(['id' => $id])->getField($field_name);
    }
}

