<?php
/**
 * Created by PhpStorm.
 * User: 任梦龙
 * Date: 2019/03/05
 * Time: 下午2:40
 * 域名黑名单模型
 */

namespace Admin\Model;

use Think\Model;

class BlackdomainModel extends Model
{
    //自动验证
    protected $_validate = array(
        ['domain', 'require', '域名不能为空', 0],
        ['domain', '', '域名已存在', 0, 'unique', 1],
        ['domain', 'checkUrl', '域名格式不正确', 0, 'callback', 1],     //2019-3-6 任梦龙：判断域名格式
    );

    //自动完成
    protected $_auto = array(
        ['date_time', 'YmdHis', 1, 'function'],
    );

    /**
     * 2019-3-6 任梦龙：判断域名格式
     */
    protected function checkUrl($url)
    {
        $str = "/^http(s?):\/\/(?:[A-za-z0-9-]+\.)+[A-za-z]{2,4}(?:[\/\?#][\/=\?%\-&~`@[\]\':+!\.#\w]*)?$/";
        if (!preg_match($str, $url)) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * 删除域名
     */
    public static function blackDomainDel($id)
    {
        return M('blackdomain')->where(['id' => $id])->delete();
    }

    /**
     * 批量删除
     */
    public static function blackDomainDelAll($id_str)
    {
        return M('blackdomain')->where(['id' => ['IN', $id_str]])->delete();
    }

    /**
     * 根据id查询域名地址
     */
    public static function getDomaininfo($id)
    {
        return M('blackdomain')->where(['id' => $id])->getField('domain');
    }

}