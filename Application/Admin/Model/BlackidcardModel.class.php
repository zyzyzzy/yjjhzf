<?php
/**
 * 身份证黑名单模型
 */

namespace Admin\Model;

use Think\Model;

class BlackidcardModel extends Model
{
    //自动验证
    protected $_validate = array(
        ['idcard', 'require', '身份证号不能为空', 0],
        ['idcard', '', '身份证号已存在', 0, 'unique', 3],
        ['idcard','checkIdCard','身份证号格式错误!',0,'callback',3],
    );

    //自动完成
    protected $_auto = array(
        ['date_time', 'YmdHis', 1, 'function'],
    );

    //验证身份证格式
    protected function checkIdCard($idcard){
        if(preg_match("/\d{17}[0-9Xx]|\d{15}/", $idcard)){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 删除身份证
     */
    public static function blackIdCardDel($id)
    {
        return M('blackidcard')->where(['id' => $id])->delete();
    }

    /**
     * 批量删除
     */
    public static function blackIdCardDelAll($id_str)
    {
        return M('blackidcard')->where(['id' => ['IN', $id_str]])->delete();
    }

    /**
     * 根据id查询身份证
     */
    public static function getIdCardinfo($id)
    {
        return M('blackidcard')->where(['id' => $id])->getField('idcard');
    }

}