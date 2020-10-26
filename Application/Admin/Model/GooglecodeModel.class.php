<?php

namespace Admin\Model;

use Think\Model;

class GooglecodeModel extends Model
{
    //查询用户的二次验证数据
    public static function getInfo($type,$user_id)
    {
        $where = [
            'admin_user' =>$type,
            'userid'=>$user_id
        ];
        return M('googlecode')->where($where)->find();
    }

    //开通用户的二次验证,即添加记录
    public static function addInfo($type,$user_id)
    {
        $secret = self::createSecret();
        $data = [
            'admin_user' =>$type,
            'userid'=>$user_id,
            'secret'=>$secret,
            'status'=>0
        ];
        return M('googlecode')->add($data);
    }

    //关闭用户的二次验证,即删除记录
    public static function delInfo($type,$user_id)
    {
        $where = [
            'admin_user' =>$type,
            'userid'=>$user_id
        ];
        return M('googlecode')->where($where)->delete();
    }

    //生成用户的二次验证的密钥
    public function createSecret()
    {
        $secret = create_secret();
        $count = M('googlecode')->where('secret="'.$secret.'"')->count();
        if($count>=1){
            $this->createSecret();
        }
        return $secret;
    }

    //查询用户的密钥
    public static function getSecret($type,$user_id)
    {
        $where = [
            'admin_user' =>$type,
            'userid'=>$user_id
        ];
        return M('googlecode')->where($where)->getField('secret');
    }

    //修改用户的状态
    public static function saveStatus($type,$user_id)
    {
        $where = [
            'admin_user' =>$type,
            'userid'=>$user_id
        ];
        return M('googlecode')->where($where)->save(['status'=>1]);
    }

    //生成用户扫描的二维码
    public static function createSrc($username,$secret,$website='易吉支付系统')
    {

        return create_googlecode_qr('管理员 : '.$username,$secret,C('WEBSITE_NAME'));
    }

    //验证用户输入的验证码
    public static function verifyCode($secret,$code){
        return verifcode_googlecode($secret ,$code);
    }
}