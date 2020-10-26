<?php

namespace Admin\Model;

use Think\Model;

class PayapishangjiaModel extends Model
{
    protected $_validate = array(
        array('shangjianame', 'require', '商家名称不能为空！', 0),
        array('shangjianame', 'checkName', '商家名称已存在！', 0, 'callback', 3),
        array('shangjianame', '2,20', '商家名称长度在2-20字符之间！', 0, 'length', 3),
    );

    //2019-4-18 rml:验证唯一性
    protected function checkName($name)
    {
        $id = I('request.id', 0);
        if ($id) {
            $count = D('payapishangjia')->where(['shangjianame' => $name, 'del' => 0, 'id' => ['NEQ', $id]])->count();
        } else {
            $count = D('payapishangjia')->where(['shangjianame' => $name, 'del' => 0])->count();
        }
        if ($count) {
            return false;
        }
        return true;
    }


    /**
     * 2019-2-18 任梦龙：获取商家列表
     */
    public static function getShangjiaList($where)
    {
        return M('payapishangjia')->where($where)->field('id,shangjianame')->select();
    }

    /**
     * 2019-2-18 任梦龙：获取分类json信息
     */
    public static function getClassJson($id)
    {
        return M('payapishangjia')->where('id=' . $id)->getField('classjson');
    }

    /**
     * 2019-2-18 任梦龙：获取商家名称
     */
    public static function getShangjiaName($id)
    {
        return M('payapishangjia')->where('id=' . $id)->getField('shangjianame');
    }

    /**
     * 2019-2-18 任梦龙：获取单挑记录
     */
    public static function getShangjiaInfo($id)
    {
        return M('payapishangjia')->where('id=' . $id)->find();
    }

    /**
     * 2019-2-19 任梦龙：删除单条记录
     */
    public static function delShangjia($id, $del_status)
    {
        return M('payapishangjia')->where('id=' . $id)->setField('del', $del_status);
    }


}

