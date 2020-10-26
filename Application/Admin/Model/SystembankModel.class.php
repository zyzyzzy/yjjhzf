<?php

namespace Admin\Model;

use Think\Model;

class SystembankModel extends Model
{
    protected $_auto = array(
        array('datetime', 'YmdHis', 1, 'function'),
        array('jiayi', '0'),
        array('jiesuan', '0')
    );

    protected $_validate = array(
        array('bankname', 'require', '银行名称不能为空！', 0),
        array('bankname', 'checkBankname', '银行名称已存在！', 0, 'callback', 3),
        array('bankname', '2,20', '银行名称长度在2-20字符之间！', 0, 'length', 3),
        array('bankcode', 'require', '银行编码不能为空！', 0),
        array('bankcode', 'checkBankCode', '银行编码已存在！', 0, 'callback', 3),
        array('bankcode', '2,10', '银行编码长度在2-10字符之间！', 0, 'length', 3),
    );

    protected function checkBankname($bank_name)
    {
        $id = I('request.id', 0);
        if ($id) {
            $count = D('systembank')->where(['bankname' => $bank_name, 'del' => 0, 'id' => ['NEQ', $id]])->count();
        } else {
            $count = D('systembank')->where(['bankname' => $bank_name, 'del' => 0])->count();
        }
        if ($count) {
            return false;
        }
        return true;

    }

    //2019-4-19 rml:银行编码
    protected function checkBankCode($name)
    {
        $id = I('request.id', 0);
        if ($id) {
            $count = D('systembank')->where(['bankcode' => $name, 'del' => 0, 'id' => ['NEQ', $id]])->count();
        } else {
            $count = D('systembank')->where(['bankcode' => $name, 'del' => 0])->count();
        }
        if ($count) {
            return false;
        }
        return true;

    }


    //2019-03-13汪桂芳添加:根据某个字段判断值是否存在
    public static function getInfoByField($field, $value)
    {
        return M('systembank')->where($field . '="' . $value . '"')->find();
    }

    /*
     * 通过id获取单条系统银行信息
     */
    public static function getBankInfo($id)
    {
        return M('systembank')->where('del = 0 AND id=' . $id)->find();
    }

    /*
    * 查询所有系统银行信息
    */
    public static function getAllBankInfo()
    {
        return M('systembank')->field('id,bankname')->where('del = 0')->select();
    }

    /*
   * 查询所有启用的系统银行信息
   */
    //2019-4-18 rml:修改为数组形式，更加安全
    public static function getAllTrueBankInfo($type)
    {
        return M('systembank')->where(['del' => 0, $type => 1])->select();
    }

    /**
     * 2019-1-11 任梦龙： 修改单条记录
     * @param $id
     * @param $name :字段名
     * @param $val：字段值
     * @return bool
     */
    public static function editInfo($id, $name, $val)
    {
        return M("systembank")->where(['id' => $id])->setField($name, $val);
    }

    //2019-1-11 任梦龙： 批量删除
    public static function delAll($id_str)
    {
        return M("systembank")->where(['id' => ['IN', $id_str]])->setField('del', 1);  //2019-4-19 rml:修改为数组形式
    }
    /****************************************************************/
    //2019-1-14 任梦龙：删除，批量删除 --真实删除数据
    //真实删除单条记录
    public static function delInfo($id)
    {
        return M("systembank")->where(['id' => $id])->delete();
    }

    //真实批量删除记录
    public static function delAllInfo($id_str)
    {
        return M("systembank")->where("id in (" . $id_str . ")")->delete();
    }

    //2019-1-15 任梦龙：恢复单条记录
    public static function regainInfo($id)
    {
        return M("systembank")->where("id=" . $id)->setField('del', 0);
    }

    //2019-1-15 任梦龙：恢复多条记录
    public static function regainAllData($id_str)
    {
        return M("systembank")->where("id in (" . $id_str . ")")->setField('del', 0);
    }
    /****************************************************************/

    /**
     * 2019-3-22 任梦龙：根据id获取名称
     */
    public static function getBankname($id)
    {
        return M("systembank")->where(['id' => $id])->getField('bankname');
    }

    /**
     * 2019-3-26 任梦龙：根据id查询单条记录
     */
    public static function getInfo($id)
    {
        return M('systembank')->where(['id' => $id])->find();
    }

    //2019-4-19 rml：根据id查询出图片路径
    public static function getImgurl($id)
    {
        return M("systembank")->where(['id' => $id])->getField('img_url');
    }
}
