<?php
/**
 * Created by PhpStorm.
 * User: 任梦龙
 * Date: 2019/1/25/025
 * Time: 上午 10:13
 * ip白名单表模型
 */

namespace User\Model;

use Think\Model;

class IpaccesslistModel extends Model
{
    //自动验证
    protected $_validate = array(
        ['ip', 'require', 'ip不能为空', 1],
        ['ip', 'setIpRule', 'ip格式不正确', 0, 'callback', 1],
    );

    //自动完成
    //2019-3-27 任梦龙：修改
    protected $_auto = array(
        ['date_time', 'YmdHis', 1, 'function'],
    );


    /**
     * 验证ip格式
     */
    protected function setIpRule($ip)
    {
        if (preg_match("/^(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$/", $ip)) {
            return true;
        }
        return false;
    }

    /**
     * 删除ip
     */
    public static function delIp($id)
    {
        return D('ipaccesslist')->where('id=' . $id)->delete();
    }

    /**
     * 查找ip
     */
    public static function getIpList($where)
    {
        return D('ipaccesslist')->where($where)->getField('ip', true);
    }

    //2019-3-27 任梦龙：根据id查询单个Ip
    public static function getChilduserIp($id)
    {
        return D('ipaccesslist')->where(['id' => $id])->getField('ip');
    }
}