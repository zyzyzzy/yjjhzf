<?php

namespace Admin\Model;

use Think\Model;

class PayapiclassModel extends Model
{

    //2019-4-18 rml:验证唯一性，优化
    protected $_validate = array(
        array('classname', 'require', '通道分类名称不能为空！', 0),
        array('classname', 'checkName', '通道分类名称已存在！', 0, 'callback', 3),
        array('classname', '2,20', '通道分类名称长度在2-20字符之间！', 0, 'length', 3),
        array('classbm', 'require', '通道分类编码不能为空！', 0),
        array('classbm', 'checkBm', '通道分类编码已存在！', 0, 'callback', 3),
        array('classbm', '2,20', '通道分类编码长度在2-20字符之间！', 0, 'length', 3),
        array('order_feilv', 'require', '默认交易运营费率不能为空！', 0),
        array('order_feilv', 'checkOrder', '默认交易运营费率不能大于2！', 0, 'callback', 3),
        array('order_min_feilv', 'require', '单笔最低手续费不能为空！', 0),
        array('order_min_feilv', 'checkMinOrder', '单笔最低手续费不能大于9999999999！', 0, 'callback', 3),
    );

    protected function checkName($name)
    {
        $id = I('request.id', 0);
        if ($id) {
            $count = D('payapiclass')->where(['classname' => $name, 'del' => 0, 'id' => ['NEQ', $id]])->count();
        } else {
            $count = D('payapiclass')->where(['classname' => $name, 'del' => 0])->count();
        }
        if ($count) {
            return false;
        }
        return true;
    }

    protected function checkBm($name)
    {
        $id = I('request.id', 0);
        if ($id) {
            $count = D('payapiclass')->where(['classbm' => $name, 'del' => 0, 'id' => ['NEQ', $id]])->count();
        } else {
            $count = D('payapiclass')->where(['classbm' => $name, 'del' => 0])->count();
        }
        if ($count) {
            return false;
        }
        return true;
    }

    protected function checkOrder($order_feilv)
    {
        if($order_feilv > 2){
            return false;
        }
        return true;
    }

    protected function checkMinOrder($order_min_feilv)
    {
        if($order_min_feilv > 9999999999){
            return false;
        }
        return true;
    }

    public static function getClasses()
    {
        return D("payapiclass")->select();
    }

    public static function getUserPayapiAccounts($userid, $payapiid)
    {
        $accountIds = M('money')->where([
            "userid" => ['eq', $userid],
            "payapiid" => ['eq', $payapiid],
        ])->getField('payapiaccountid', true);
        if (count($accountIds) < 1) {
            return [];
        }
        return D('payapiaccount')->where(['id' => ['in', $accountIds]])->select();
    }

    public static function getUserPayapiIds($userid, $payapiclassid)
    {
        return M("userpayapiclass")->where([
            "userid" => ["eq", $userid],
            "payapiclassid" => ["eq", $payapiclassid],
        ])->getField('payapiid', true);
    }

    //2019-1-9 任梦龙：查询分类是否在用户分类表中
    public static function getUserClass($id)
    {
        return M('userpayapiclass')->where('payapiclassid = ' . $id)->count();
    }

    public static function getPayapiClassname($id)
    {
        return D('payapiclass')->where(['id' => ['eq', $id]])->getField('classname');
    }

    /**
     * 2019-2-18 任梦龙：获取通道分类信息
     */
    public static function getPayapiClass($where)
    {
        return D("payapiclass")->where($where)->field('id,classname')->select();
    }

    /**
     * 2019-2-18 任梦龙：修改通道分类状态
     */
    public static function editPayapiClassStatus($id, $status)
    {
        return D("payapiclass")->where("id=" . $id)->setField("status", $status);
    }

    /**
     * 2019-2-25 汪桂芳：通道分类的pc状态
     */
    public static function editPayapiClassPc($id, $pc)
    {
        return D("payapiclass")->where("id=" . $id)->setField("pc", $pc);
    }

    /**
     * 2019-2-25 汪桂芳：通道分类的wap状态
     */
    public static function editPayapiClassWap($id, $wap)
    {
        return D("payapiclass")->where("id=" . $id)->setField("wap", $wap);
    }

    /**
     * 2019-2-18 任梦龙：导出通道分类信息
     */
    public static function exportPayapiClass($where)
    {
        return M('payapiclass')->where($where)->field("classname,classbm,order_feilv,order_min_feilv")->select();
    }

    /**
     * 2019-2-18 任梦龙：获取通道分类单条记录
     */
    public static function getPayapiClassInfo($id)
    {
        return M("payapiclass")->where("id=" . $id)->find();
    }

    /**
     * 2019-2-18 任梦龙：软删除
     * $del_status:删除状态：0=正常；1=已删除
     */
    public static function delPayapiClass($id, $del_status)
    {
        return M("payapiclass")->where("id=" . $id)->setField('del', $del_status);
    }

    //2019-03-14汪桂芳添加:添加记录
    public static function addClassInfo($data)
    {
        return D('payapiclass')->add($data);
    }

    //2019-03-14汪桂芳添加:根据某个字段判断值是否存在
    public static function getInfoByField($field, $value)
    {
        return M('payapiclass')->where($field . '="' . $value . '"')->find();
    }

    //2019-03-14汪桂芳添加:修改信息
    public static function saveInfo($id, $data)
    {
        return M('payapiclass')->where('id=' . $id)->save($data);
    }

    //2019-3-26 任梦龙：将方法移到模型成:查询通道分类的费率
    public static function getPayapiclassFeilv($payapiclassid)
    {
        return M('payapiclass')->where(['id' => $payapiclassid])->getField('order_feilv');
    }
}

