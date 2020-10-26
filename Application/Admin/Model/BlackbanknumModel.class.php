<?php
/**
 * Created by PhpStorm.
 * User: 任梦龙
 * Date: 2019/03/05
 * Time: 下午4:20
 * 银行卡号黑名单模型
 */

namespace Admin\Model;

use Think\Model;

class BlackbanknumModel extends Model
{
    //自动验证
    protected $_validate = array(
        ['bank_num', 'require', '银行卡号不能为空', 0],
        ['bank_num', '', '银行卡号已存在', 0, 'unique', 1],
    );

    //自动完成
    protected $_auto = array(
        ['date_time', 'YmdHis', 1, 'function'],
    );

    /**
     * 删除银行卡号
     */
    public static function blackBankNumDel($id)
    {
        return M('blackbanknum')->where(['id' => $id])->delete();
    }

    /**
     * 批量删除
     */
    public static function blackBankNumDelAll($id_str)
    {
        return M('blackbanknum')->where(['id' => ['IN', $id_str]])->delete();
    }

    /**
     * 根据id查询银行卡号
     */
    public static function getBankNuminfo($id)
    {
        return M('blackbanknum')->where(['id' => $id])->getField('bank_num');
    }

}