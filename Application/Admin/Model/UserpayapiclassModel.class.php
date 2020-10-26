<?php

namespace Admin\Model;


use Think\Model\RelationModel;

class UserpayapiclassModel extends RelationModel
{
    protected $_link = [
        "user" => [
            'mapping_type' => self::BELONGS_TO,
            'mapping_name' => 'user',
            'foreign_key' => 'userid'
        ],
        "payapi" => [
            'mapping_type' => self::BELONGS_TO,
            'mapping_name' => 'payapi',
            'foreign_key' => 'payapiid'
        ],
    ];

    public static function getUserPayapiClass($userid = null)
    {
        if (!$userid) {
            return M('payapiclass')->select();
        }
        $payapiclassids = D('userpayapiclass')->where("userid=" . $userid)->getField('payapiclassid', true);
        if (count($payapiclassids) < 1) {
            return [];
        }
        return M('payapiclass')->where(["id" => ["in", $payapiclassids]])->select();
    }

    public static function getUserPayapis($userid)
    {
        $payapiIds = D("userpayapiclass")->where('userid=' . $userid)->getField('payapiid', true);
        if (count($payapiIds) > 0) {
            return D("payapi")->where(["id" => ["in", $payapiIds]])->select();
        }
    }

    public static function getIdsByUserid($userid)
    {
        return D("userpayapiclass")->where("userid='" . $userid . "'")->getField('id', true);
    }


    //根据userpayapiaccountid获取用户 通道 账号的组合信息
    public static function getUserPayapiAccountInfo($userpayapiaccountid)
    {
        //查询账户id
        $accountinfo = M('userpayapiaccount')->where('id=' . $userpayapiaccountid)->find();
        $payapiinfo = M('userpayapiclass')->where('id=' . $accountinfo['userpayapiclassid'])->find();
        $info = $payapiinfo;
        $info['accountid'] = $accountinfo['accountid'];
        return $info;
    }

    public static function getMoneyClassList()
    {
        return M('moneytypeclass')->select();
    }

    //2019-02-26汪桂芳添加:查询用户开通的通道分类
    public static function getUserClass($user_id)
    {
        return D('userpayapiclass')->where("userid=" . $user_id)->getField('payapiclassid', true);
    }

    //2019-3-26 任梦龙：查询该用户已经选中了的通道分类
    public static function getCheckUserclass($userid)
    {
        return D("userpayapiclass")->where("userid=" . $userid)->select();
    }

    //2019-3-26 任梦龙：根据用户id和通道分类id查询记录
    public static function getUserpayapiInfo($user_id, $payapiclass_id)
    {
        return M("userpayapiclass")->where('userid=' . $user_id . ' AND payapiclassid=' . $payapiclass_id)->find();
    }

    //2019-3-26 任梦龙：根据id删除记录
    public static function delInfo($id)
    {
        return M('userpayapiclass')->where('id=' . $id)->delete();
    }

    //修改数据
    public static function saveUserclassInfo($id, $data)
    {
        return D('userpayapiclass')->where('id = ' . $id)->save($data);
    }

    //增加数据
    public static function addInfo($data)
    {
        return D('userpayapiclass')->add($data);
    }

    //查询用户在某通道分类下的费率
    public static function getUserPayapiclassFeilv($userid, $payapiclassid)
    {
        $where = [
            'userid' => $userid,
            'payapiclassid' => $payapiclassid,
        ];
        return M('userpayapiclass')->where($where)->getField('order_feilv');
    }

    //2019-3-26 任梦龙：根据id获取单条记录
    public static function getUserPayapiclassInfo($id)
    {
        return D('userpayapiclass')->where('id = ' . $id)->find();
    }

    //2019-4-4 任梦龙：获取用户设置的通道
    public static function getUserpayapi($payapiid)
    {
        return M('userpayapiclass')->where(['payapiid' => $payapiid])->count();
    }

    //获取通道设置的默认账号
    public static function getDefaultAccount($where)
    {
        return M('userpayapiclass')->where($where)->find();
    }

    //删除通道设置的默认账号
    public static function delDefaultAccount($where)
    {
        return M('userpayapiclass')->where($where)->delete();
    }

    //添加数据
    public static function addDefaultAccount($data)
    {
        return M('userpayapiclass')->add($data);
    }

    //修改通道的账号的默认状态值
    public static function editDefaultAccount($where, $data)
    {
        return M('userpayapiclass')->where($where)->save($data);
    }

}