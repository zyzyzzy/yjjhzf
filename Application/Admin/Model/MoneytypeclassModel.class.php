<?php

namespace Admin\Model;

use Think\Model;

class MoneytypeclassModel extends Model
{
    //2019-4-18 rml：添加字段，优化自动验证
    protected $_scope = array(
        // 命名范围normal
        'default_scope' => array(
            'field' => [
                "id"
                , "moneyclassname"
                , "datetime"
            ],
        ),
    );

    //2019-4-18 rml：优化
    protected $_validate = array(
        array('moneyclassname', 'require', '方案名称不能为空！', 0),
        array('moneyclassname', 'checkName', '方案名称已存在！', 0, 'callback', 3),
        array('moneyclassname', '2,10', '方案名称长度在2-20字符之间！', 0, 'length', 3),
    );

    //验证方案名称唯一性
    protected function checkName($name)
    {
        $id = I('request.id', '');
        if ($id) {
            $count = D('moneytypeclass')->where(['moneyclassname' => $name, 'del' => 0, 'id' => ['NEQ', $id]])->count();
        } else {
            $count = D('moneytypeclass')->where(['moneyclassname' => $name, 'del' => 0])->count();
        }
        if ($count) {
            return false;
        }
        return true;
    }

    protected $_auto = array(
        array('datetime', 'YmdHis', 1, 'function'),
    );

    public static function getMoneyClassInfo($id)
    {
        return M('moneytypeclass')->where('id = ' . $id)->find();
    }

    public static function getMoneyClassList()
    {
        return M('moneytypeclass')->where('del = 0')->field('id,moneyclassname')->order('id DESC')->select();
    }

    //判断到账方案是否在账号和用户账号中
    public static function useMoneyClass($id)
    {
        $payapi_count = M('payapiaccount')->where('del = 0 AND moneytypeclassid = ' . $id)->count();
        $user_count = M('usermoneyclass')->where('moneytypeclass_id = ' . $id)->count();
        if ($payapi_count > 0 || $user_count > 0) {
            return true;
        }
        return false;
    }

    //软删除单个到账方案
    public static function delMoneyClass($id)
    {
        return M('moneytypeclass')->where('id = ' . $id)->setField('del', 1);
    }

    //批量软删除到账方案
    public static function delAllMoneyClass($id_arr)
    {
        $where['id'] = ['in', $id_arr];
        return M("moneytypeclass")->where($where)->setField('del', 1);
    }


    //$id:到账方案id  判断是否删除冻结方案
    public static function delMoneyType($id)
    {
        $money_type = M('moneytype')->where('moneytypeclassid = ' . $id)->count();
        if ($money_type > 0) {
            M("moneytype")->where("moneytypeclassid=" . $id)->setField('del', 1);
        }
    }

    //根据id查询名称
    public static function getMoneyClassName($id)
    {
        return M('moneytypeclass')->where(['id' => $id])->getField('moneyclassname');
    }


}

