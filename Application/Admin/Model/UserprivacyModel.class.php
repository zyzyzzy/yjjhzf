<?php
/**
 * Created by PhpStorm.
 * User: 任梦龙
 * Date: 2019/1/21/021
 * Time: 下午 3:27
 * 用户隐私数据模型
 */

namespace Admin\Model;

use Think\Model;

class   UserprivacyModel extends Model
{
    /**
     * 隐私数据列表
     */
    public static function privacyList()
    {
        //2019-3-13 任梦龙：修改为M()方法
        return M('userprivacy')->select();
    }
}