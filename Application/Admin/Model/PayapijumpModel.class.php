<?php

namespace Admin\Model;

use Think\Model;

class PayapijumpModel extends Model
{
    /**
     * 2019-02-19 汪桂芳:查询通道广告的排除用户
     */
    public static function getPayapiJumpRemove($id)
    {
        $removes = M('payapijump')->where('payapi_id=' . $id)->order('id desc')->select();
        $remove_users = [];
        foreach ($removes as $k=>$v){
            $remove_users[$k] = $v;
            $remove_users[$k]['user_name'] = M('user')->where('id='.$v['user_id'])->getField('username');
            $remove_users[$k]['member_id'] = M('secretkey')->where('userid='.$v['user_id'])->getField('memberid');
        }
        return $remove_users;
    }

    //数据查询
    public static function getPayapiUserJump($payapi_id,$user_id)
    {
        return M('payapijump')->where('payapi_id=' . $payapi_id . ' and user_id=' . $user_id)->find();
    }

    //数据查询
    public static function getPayapiUserJumpById($id)
    {
        return M('payapijump')->where('id=' . $id)->find();
    }

    //数据删除
    public static function payapiJumpDelete($id)
    {
        return M('payapijump')->where('id=' . $id)->delete();
    }

    //数据添加
    public static function payapiJumpAdd($payapi_id,$user_id)
    {
        $data = [
            'payapi_id'=>$payapi_id,
            'user_id'=>$user_id
        ];
        return M('payapijump')->add($data);
    }
}

