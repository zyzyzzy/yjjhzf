<?php

namespace User\Model;

use Think\Model;

class UserModel extends Model
{
    protected $_scope = array(
        // 命名范围normal
        'default_scope' => array(
            'field' => [

                "id"

                , "username"

                , "usertype as type"//2019-01-25汪桂芳修改

                , "getmemberid(id) as memberid"

                , "getusertype(usertype) as usertype"

                , "getsjusername(superiorid) as superiorid"

                , "status"

                , "getuserstatus(status) as statusname"

                , "getuserrengzheng(authentication) as authentication"

                , "regdatetime"

//                ,"getsummoney(id) as money"

                , "getusermoney(id) as money"

                , "getuserfreezemoney(id) as freezemoney"

            ],
        ),

    );

    protected $_auto = array(
        ['status', 1],
        ['authentication', 1],
        ['regdatetime', 'YmdHis', 1, 'function'],
        ['usercode', 'getCode', 1, 'callback'],
    );

    protected function getCode()
    {
        $user_code = random_str(32);
        $count = M('user')->where(['usercode' => $user_code])->count();
        if ($count > 0) {
            $this->getCode();
        }
        return $user_code;
    }

    //查询用户信息
    public static function getUserInfo($userid)
    {
        return D('user')->scope('default_scope')->where(['id' => $userid])->find();
    }

    //查询用户的所有下级用户
    public static function getAllChild($list, $s = [])
    {
        $ids = M('user')->where(['superiorid' => ["in", $list]])->getField('id', true);
        if (count($ids) > 0) {
            //有数据时合并数据加数据的下级
            $s = array_merge($ids, self::getAllChild($ids));
        }
        //没有数据时直接返回
        return $s;
    }


    //查询直属下级个数
    public static function getChildCount($userid)
    {
        return M('user')->where(['superiorid' => $userid])->count();
    }

    public static function getUsername($id)
    {
        return D('user')->where(['id' => $id])->getField('username');
    }

    /**
     * 2019-1-29 任梦龙：查询用户类型
     */
    public static function getUserType($user_id)
    {
        return M('user')->where(['id' => $user_id])->getField('usertype');
    }

    /**
     * 2019-2-12 任梦龙：在登录时，查询用户信息
     */
    public static function findUserInfo($where)
    {
        return M('user')->where($where)->find();
    }

    /**
     * 2019-3-6 任梦龙：根据用户名获取id
     */
    public static function getUserId($user_name)
    {
        return M('user')->where("username='" . $user_name . "'")->getField('id');
    }

    public static function getSumUser()
    {
        return D('user')->where([
            'del' => 0
        ])->count();
    }

    //获取用户自助收银状态
    public static function getUserSelfcashStatus($user_id)
    {
        return D('user')->where('id=' . $user_id)->getField('selfcash_status');
    }
    //上面的方法是汪桂芳2019-3-28 写的方法
    //2019-3-28 任梦龙：根据id获取通道id组
    public static function getPayapiid($id)
    {
        return M('user')->where(['id' => $id])->getField('payapi_id');
    }

    //2019-3-28 任梦龙：根据id获取是否开通自助通道账号的状态
    public static function getSelfPayapi($id)
    {
        return M('user')->where(['id' => $id])->getField('self_payapi');
    }

    //2019-4-1 任梦龙：修改数据
    public static function editUserInfo($user_id, $data)
    {
        return M('user')->where(['id' => $user_id])->save($data);
    }

    //2019-4-1 任梦龙：根据id获取用户同一账号状态
    public static function getSameUser($user_id)
    {
        return M('user')->where(['id' => $user_id])->getField('same_user');
    }

    //获取用户自助收银背景图片路径
    public static function getUserSelfcashBack($user_id)
    {
        return M('user')->where('id=' . $user_id)->getField('selfcash_back');
    }

    //修改用户自助收银背景图片路径
    public static function setUserSelfcashBack($user_id, $back)
    {
        return M('user')->where('id=' . $user_id)->setField('selfcash_back', $back);
    }
}