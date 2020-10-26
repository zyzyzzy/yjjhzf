<?php

namespace User\Model;

use Think\Model;

class SecretkeyModel extends Model
{
    private static $str = '0123456789abcdefghijklmnopqrstuvwxyz';
    protected $_validate = array(
//        ['userid', 'require', '用户不能为空!', 1],  2019-3-19 任梦龙:注释掉
        ['memberid', 'require', '商户号不能为空!', 0],
        ['md5str', 'require', 'MD5秘钥不能为空!', 0],
//        ['publickeypath', 'require', '用户公钥不能为空!', 0],
//        ['privatekeypath', 'require', '用户私钥不能为空!', 0],
        ['user_keypath', 'require', '用户密钥不能为空!', 0],   // 2019-1-11 任梦龙:将用户公钥与用户私钥合并为用户密钥
        ['sys_publickeypath', 'require', '系统公钥不能为空!', 0],
        ['sys_privatekeypath', 'require', '系统私钥不能为空!', 0],
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

    public static function createdMemberid($userid, $createtype, $method = '')
    {
        if ($createtype == 1) {
            $memberid = $userid + 10000;
        } elseif ($createtype == 2) {
            $memberid = date("YmdHis") . $userid;
        } elseif ($createtype == 3) {
            $memberid = randpw(8, 'NUMBER') . $userid;
        }
        if ($method == "add") {
            return D('secretkey')->data(['userid' => $userid, 'memberid' => $memberid])->add();
        }
        return D('secretkey')->where("userid='" . $userid . "'")->setField(["memberid" => $memberid]);
    }

    //2019-1-8 任梦龙：添加获取密钥文件内容方法
    public static function userKeyFind($user_id)
    {
        return M('secretkey')->where('userid = ' . $user_id)->find();
    }

    public static function getSecretkeyByMemberid($memberid)
    {
        return D('secretkey')->where(['memberid' => ['eq', $memberid]])->find();
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

    public static function getUserSecret($id)
    {
        return D('secretkey')->where(['id' => $id])->find();
    }

    public static function editUserSecret($id, $data)
    {
        return D('secretkey')->where(['id' => $id])->save($data);
    }

    public static function getUserMd5Key($userid)
    {
        return D('secretkey')->where(['userid' => ['eq', $userid]])->getField('md5str');
    }

    public static function getMemberid($userid)
    {
        return D('secretkey')->where(['userid' => ['eq', $userid]])->getField('memberid');
    }


}

