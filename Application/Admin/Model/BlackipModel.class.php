<?php
/**
 * IP黑名单模型
 */

namespace Admin\Model;

use Think\Model;

class BlackipModel extends Model
{
    //自动验证
    protected $_validate = array(
        ['ip', 'require', 'ip不能为空', 1],
        ['ip', 'setIpRule', 'ip格式不正确', 0, 'callback', 1],
        ['ip', '', 'ip已存在', 0, 'unique', 1],
    );

    //自动完成
    protected $_auto = array(
        ['date_time', 'YmdHis', 1, 'function'],
    );

    /**
     * 验证ip格式
     */
    protected function setIpRule($ip)
    {
        if (preg_match("/^(25[0-5]|2[0-4]\d|[0-1]\d{2}|[1-9]?\d)\.(25[0-5]|2[0-4]\d|[0-1]\d{2}|[1-9]?\d)\.(25[0-5]|2[0-4]\d|[0-1]\d{2}|[1-9]?\d)\.(25[0-5]|2[0-4]\d|[0-1]\d{2}|[1-9]?\d)$/", $ip)) {
            return true;
        }
        return false;
    }

    /**
     * 删除ip
     */
    public static function blackIpDel($id)
    {
        return M('blackip')->where(['id' => $id])->delete();
    }

    /**
     * 批量删除
     */
    public static function blackIpDelAll($id_str)
    {
        return M('blackip')->where(['id' => ['IN', $id_str]])->delete();
    }

    /**
     * 根据id查询ip地址
     */
    public static function getIpinfo($id)
    {
        return M('blackip')->where(['id' => $id])->getField('ip');
    }

    /**
     * 2019-3-11 任梦龙：根据登录时获取的ip判断是否在ip黑名单中
     */
    public static function isBlackIp($ip)
    {
        $count = M('blackip')->count();
        if ($count) {
            $find = M('blackip')->where("ip='" . $ip . "'")->field('id')->find();
            if ($find) {
                return false;
            }
            return true;
        }
        return true;
    }

}