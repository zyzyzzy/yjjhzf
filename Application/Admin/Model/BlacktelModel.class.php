<?php
/**
 * 手机号黑名单模型
 */

namespace Admin\Model;

use Think\Model;

class BlacktelModel extends Model
{
    //自动验证
    protected $_validate = array(
        ['tel', 'require', '手机号不能为空', 0],
        ['tel','checktel','手机号格式错误!',0,'callback',1],
        ['tel', '', '手机号已存在', 0, 'unique', 1],
    );

    //自动完成
    protected $_auto = array(
        ['date_time', 'YmdHis', 1, 'function'],
    );

    //验证手机号格式
    protected function checktel($tel){
        if(preg_match("/^1[34578]\d{9}$/", $tel)){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 删除手机号
     */
    public static function blackTelDel($id)
    {
        return M('blacktel')->where(['id' => $id])->delete();
    }

    /**
     * 批量删除
     */
    public static function blackTelDelAll($id_str)
    {
        return M('blacktel')->where(['id' => ['IN', $id_str]])->delete();
    }

    /**
     * 根据id查询手机号
     */
    public static function getTelinfo($id)
    {
        return M('blacktel')->where(['id' => $id])->getField('tel');
    }

}