<?php
/**
 * Created by PhpStorm.
 * User: 任梦龙
 * Date: 2019/2/22/022
 * Time: 下午 1:23
 * 修改密钥记录表模型
 */

namespace Admin\Model;

use Think\Model;

class SecretkeyrecordModel extends Model
{
    /**
     * 添加记录数据
     */
    public static function addSecretRecord($data)
    {
        D('secretkeyrecord')->add($data);
    }
}