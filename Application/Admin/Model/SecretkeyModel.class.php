<?php

namespace Admin\Model;

use Think\Model;

class SecretkeyModel extends Model
{
    //2019-8-27 rml:密钥可以为空，去掉密钥的自动验证条件
    private static $str = '0123456789abcdefghijklmnopqrstuvwxyz';
    protected $_validate = array(   //自动验证
        ['userid', 'require', '用户不能为空!', 1],
        ['memberid', 'require', '商户号不能为空!', 0],
    );

    public static function createdMd5key($length = 18, $bool = 'true')
    {
        $str = str_shuffle(static::$str . date('s'));
        if ($length > 35) {
            $length = 20;
        }
        if (!$bool) {
            return substr($str, 0, $length);
        }
        return strtoupper(substr($str, 0, $length));
    }

    //2019-18 任梦龙：由于分解了页面，此方法没有用了
//    public static function editRsakey($userid, $sys_publickeypath, $sys_privatekeypath, $publickeypath, $privatekeypath)
//    {
//        return D('secretkey')->where("userid='" . $userid . "'")->setField([
//            "sys_publickeypath" => $sys_publickeypath,
//            "sys_privatekeypath" => $sys_privatekeypath,
//            "publickeypath" => $publickeypath,
//            "privatekeypath" => $privatekeypath,
//        ]);
//    }

    //2019-3-25 任梦龙：用户商户号生成规律
    public static function createdMemberid($userid, $createtype)
    {
        if ($createtype == 1) {
            $memberid = $userid + 10000;
        } elseif ($createtype == 2) {
            $memberid = date("YmdHis") . $userid;
        } elseif ($createtype == 3) {
            $memberid = randpw(8, 'NUMBER') . $userid;
        }
        return D('secretkey')->where("userid=" . $userid)->setField('memberid', $memberid);
    }

    //2019-1-8 任梦龙：添加获取密钥文件内容方法
    public static function userKeyFind($user_id)
    {
        return M('secretkey')->where('userid = ' . $user_id)->find();
    }

    //2019-1-8 任梦龙：添加修改用户密钥文件中对应的密钥内容
    public static function editkeyContent($user_id, $file_name, $contents)
    {
        M('Secretkey')->where('userid = ' . $user_id)->setField($file_name, $contents);
    }


    public static function getSecretkeyByMemberid($memberid)
    {
        return D('secretkey')->where(['memberid' => ['eq', $memberid]])->find();
    }

    public static function getUserMd5Key($userid)
    {
        return D('secretkey')->where(['userid' => ['eq', $userid]])->getField('md5str');
    }

    public static function getUserPubKey($userid)
    {
        $user_keypath = D('secretkey')->where(['userid' => ['eq', $userid]])->getField('user_keypath');
        return "-----BEGIN PUBLIC KEY-----\n" .
            wordwrap($user_keypath, 64, "\n", true) .
            "\n-----END PUBLIC KEY-----";

    }

    public static function getUserPriKey($userid)
    {
        $priKey = D('secretkey')->where(['userid' => ['eq', $userid]])->getField('sys_privatekeypath');
        return "-----BEGIN RSA PRIVATE KEY-----\n" .
            wordwrap($priKey, 64, "\n", true) .
            "\n-----END RSA PRIVATE KEY-----";
    }

    /**
     * 2019-3-12 任梦龙：根据id修改对应的密钥内容
     */
    public static function editSecretKey($id, $file_name, $contents)
    {
        return M('secretkey')->where('id=' . $id)->setField($file_name, $contents);
    }

    //2019-4-1 任梦龙：同时修改密钥内容和路径
    public static function editUserSecret($id, $data)
    {
        return M('secretkey')->where(['id' => $id])->save($data);
    }


}

