<?php

namespace Admin\Model;

use Think\Model;

//2019-3-26 任梦龙：用户的通道账号表
class UserpayapiaccountModel extends Model
{
    //根据用户通道分类分类id删除数据
    public static function delUseraccount($userpayapiclassid)
    {
        return M("userpayapiaccount")->where("userpayapiclassid=" . $userpayapiclassid)->delete();
    }

    //添加数据
    public static function addUseraccount($data)
    {
        M("userpayapiaccount")->add($data);
    }

    //2019-4-4 任梦龙：判断用户是否拥有账号
    public static function isExistUserAccount($accountid)
    {
        return M('userpayapiaccount')->where(['accountid' => $accountid])->count();
    }
}