<?php

namespace Admin\Model;

use Think\Model;

class MoneytypeModel extends Model
{
    protected $_auto = array(
        array('datetime', 'YmdHis', 1, 'function'), //新增时赋值当前时间
    );
    protected $_validate = array(   //自动验证
        array('moneytypeclassid', 'require', '到账方案ID不能为空！', 1),
        array('moneytypename', 'require', '冻结方案名称不能为空！', 0),
        array('moneytypename', 'checkMoneyName', '该冻结方案名称已存在!', 0, 'callback', 3),   //判断当前到账方案中冻结方案的名字不能重复
        array('moneytypename', '2,20', '冻结方案名称长度在2-20字符之间！', 0, 'length', 1),
        array('dzsj_day', 'require', '到账天数不能为空!', 0),
        array('jiejiar', 'require', '节假日到账不能为空!', 0),
        array('dzsj_time', 'require', '到账时间不能为空!', 0),
        array('dzbl', 'require', '到账比例不能为空!', 0),
        array('dzbl', 'checkDzbl', '该到账方案中总冻结到账比例不能超过100%!', 0, 'callback', 3),  //2019-3-4 任梦龙：判断到账比例
        array('dzbl', '0.001,1', '到账比例最小0.001，最大不得超过1', 0, 'between', 3),  //2019-3-4 任梦龙：判断到账比例的上下限值

    );
    //2019-4-18 rml：添加字段，优化自动验证
    protected $_scope = array(
        // 命名范围normal
        'default_scope' => array(
            'field' => [
                "id"
                , "moneytypeclassid"
                , "moneytypename"
                , "dzsj_day"
                , "jiejiar"
                , "dzsj_time"
                , "dzbl"
                , "datetime"
            ],
        ),
    );

    protected function checkMoneyName()
    {
        $type_name = I("request.moneytypename");
        $moneyclass_id = I("request.moneytypeclassid");
        $moneytype_id = I("request.id");
        //如果没有$moneytype_id，表示添加，没有则为修改
        if ($moneytype_id) {
            $where = [
                'moneytypeclassid' => $moneyclass_id,
                'moneytypename' => $type_name,
                'id' => ['NEQ', $moneytype_id],
                'del' => 0
            ];
        } else {
            $where = [
                'moneytypeclassid' => $moneyclass_id,
                'moneytypename' => $type_name,
                'del' => 0
            ];
        }
        $count = D('moneytype')->where($where)->count();
        if ($count) {
            return false;
        }
        return true;
    }

    /**
     * 2019-3-4 任梦龙：判断到账比例
     * @param $dzbl
     * @return bool
     */
    protected function checkDzbl($dzbl)
    {
        $moneyclass_id = I("request.moneytypeclassid");  //金额方案id
        $moneytype_id = I("request.id", 0); //冻结方案id
        //如果不存在，则添加，有则修改
        if (!$moneytype_id) {
            $where = [
                'moneytypeclassid' => $moneyclass_id,
                'del' => 0,
            ];
        } else {
            $where = [
                'moneytypeclassid' => $moneyclass_id,
                'id' => array('neq', $moneytype_id),
                'del' => 0
            ];
        }
        $sum_dzbl = D("moneytype")->where($where)->sum("dzbl");
        //计算和是否大于等于1（即要小于1）
        if ($sum_dzbl + $dzbl >= 1) {
            return false;
        } else {
            return true;
        }

    }

    /*
     * 当前方案中到账比例的总和
     * $dzbl：当前冻结方案中准备添加或者修改的到账比例
     * $id：当前冻结方案id
     */
    public static function dzblSum($moneytypeclassid, $dzbl, $id = '')
    {
        //统计数据库中的到账百分比，有id表示修改，没有表示添加
        if ($id) {
            $where = [
                'moneytypeclassid' => $moneytypeclassid,
                'id' => array('neq', $id),
                'del' => 0
            ];
            $sum_dzbl = M("moneytype")->where($where)->sum("dzbl");
        } else {
            $sum_dzbl = M("moneytype")->where("del = 0 AND moneytypeclassid=" . $moneytypeclassid)->sum("dzbl");
        }
        //计算和是否大于等于1（即要小于1）
        if (($sum_dzbl + $dzbl) >= 1) {
            return false;
        } else {
            return true;
        }
    }

    //根据到账方案id得到冻结方案总记录数
    public static function getMoneyTypeCount($id)
    {
        return M('moneytype')->where(['del' => 0, 'moneytypeclassid' => $id])->order('id DESC')->count();
    }

    /*
     * 根据到账方案id得到冻结方案列表数据
     */
    //2019-4-4 任梦龙：修改
    public static function getMoneyType($id)
    {
        return M('moneytype')->where(['del' => 0, 'moneytypeclassid' => $id])->order('id DESC')->select();
    }

    //根据冻结方案id查询单条记录
    public static function getTypeInfo($id)
    {
        return M('moneytype')->where('id = ' . $id)->find();
    }

    //分页
    public static function getMoneyTypePage($moneytypeclassid, $page = 1, $limit = 10)
    {
        return D('moneytype')->where('del = 0 AND moneytypeclassid = ' . $moneytypeclassid)->page($page, $limit)->order('id desc')->select();
    }

    //删除冻结方案
    public static function delMoneyType($id)
    {
        return M('moneytype')->where('id = ' . $id)->setField('del', 1);
    }
}

