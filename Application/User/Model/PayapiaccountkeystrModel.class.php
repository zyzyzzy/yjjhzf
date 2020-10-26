<?php
/**
 * 2019-3-28 任梦龙
 * 用户自助通道账户密钥文件模型
 */

namespace User\Model;

use Think\Model;

class PayapiaccountkeystrModel extends Model
{
    protected $_validate = array(   //自动验证
        //2019-3-5 任梦龙：md5密钥可以为空
//        ['md5keystr', 'require', 'MD5密钥不能为空!', 0],
        ['publickeystr', 'require', '配置公钥不能为空!', 0],
        ['privatekeystr', 'require', '配置私钥不能为空!', 0],
        ['upstream_keystr', 'require', '上游密钥不能为空!', 0],
    );

    //查找单条记录
    public static function getkeyInfo($where)
    {
        return M('payapiaccountkeystr')->where($where)->find();
    }

    /**
     * 2019-3-12 任梦龙：根据账户密钥表id修改密钥内容
     */
    public static function editkeyContent($id, $file_name, $contents)
    {
        return M('payapiaccountkeystr')->where('id = ' . $id)->setField($file_name, $contents);
    }

    //2019-4-3 任梦龙：根据id修改数据
    public static function editUsersecret($id, $data)
    {
        return M('payapiaccountkeystr')->where(['id' => $id])->save($data);
    }


}

