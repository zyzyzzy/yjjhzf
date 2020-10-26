<?php

namespace UserPay\Model;

use Think\Model;

class UserModel extends Model
{
    //获取用户自助收银状态
    public static function getUserSelfcashStatus($user_id)
    {
        return D('user')->where(['id' => $user_id])->getField('selfcash_status');
    }

    //获取用户自助收银背景图片路径
    public static function getUserSelfcashBack($user_id)
    {
        return M('user')->where(['id' => $user_id])->getField('selfcash_back');
    }

    //获取用户自助收银背景图片路径
    public static function getUserSelfcashQrcode($user_id)
    {
        return M('user')->where(['id' => $user_id])->getField('selfcash_qrcode');
    }

    //存储二维码路径
    public static function setUserSelfcashQrcode($user_id, $imagname)
    {
        return M('user')->where(['id' => $user_id])->setField('selfcash_qrcode', $imagname);
    }

    //通过商户号获取用户id
    public static function getUseridByMemberid($memberid)
    {
        return M('secretkey')->where(['memberid' => $memberid])->getField('userid');
    }

    public static function findUser($usercode)
    {
        return M("user")->where(['usercode' => $usercode])->find();
    }

    public static function getUserid($usercode)
    {
        return M("user")->where(['usercode' => $usercode])->getField('id');
    }

}