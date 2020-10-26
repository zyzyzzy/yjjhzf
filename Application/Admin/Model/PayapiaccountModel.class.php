<?php

namespace Admin\Model;

use http\Env\Request;
use Think\Model;

class PayapiaccountModel extends Model
{

    protected $_scope = array(
        // 命名范围normal
        'default_scope' => array(
            'field' => "
            id,
            bieming,
            money,
            memberid,
            cbfeilv,
            account,
            getpayapishangjianame(payapishangjiaid) as `payapishangjianame`,
            getmoneytypeclassname(moneytypeclassid) as 'moneytypeclassname',
            getsjusername(owner_id) as 'owner_name',
            commission_rate,
            type,
            moneytypeclassid,
            status,
            min_money,
            max_money,
            oddment,
            submiturl,
            notifyurl,
            callbackurl, 
            user_id",  //2019-3-28 根据user_id判断是系统还是用户设置的账号
        ),

    );

    //2019-4-18 rml:优化
    protected $_validate = array(
        array('payapishangjiaid', 'require', '通道商家不能为空！', 0),
        array('bieming', 'require', '通道账号名称不能为空！', 0),
        array('bieming', 'checkName', '通道账号名称已存在！', 0, 'callback', 3),
        array('moneytypeclassid', 'require', '到账方案不能为空！', 0),
        //2019-3-1 任梦龙：修改，到账方案可以为空，如果为空，表示100%到账
//        array('moneytypeclassid', 'setMoneyClass', '该到账方案还未设置冻结方案，请设置！', 0, 'callback'),
        array('moneytypeclassid', 'setDzbl', '该到账方案中冻结方案的到账比例的和需小于1，请检查！', 0, 'callback', 3),
        //2019-4-2 任梦龙：由于添加了商家配置文件,所以不能再这样,直接给一个通用的
        //2019-4-3 任梦龙：修改，商户号和账号名可以不填写
//        array('memberid', 'require', '信息填写不完整,请检查！', 0),
//        array('account', 'require', '信息填写不完整,请检查！', 0),
        array('submiturl', 'require', '提交地址不能为空！', 0),
        array('notifyurl', 'require', '异步回调地址不能为空！', 0),
        array('callbackurl', 'require', '同步回调地址不能为空！', 0),

    );

    protected $_auto = array(
        array('md5keystr', 'delhh', 2, 'callback'),
        array('md5keystr', '', 2, 'ignore'),
        array('publickeystr', 'delhh', 2, 'callback'),
        array('publickeystr', '', 2, 'ignore'),
        array('privatekeystr', 'delhh', 2, 'callback'),
        array('privatekeystr', '', 2, 'ignore'),
        array('sys_publickeystr', 'delhh', 2, 'callback'),
        array('sys_publickeystr', '', 2, 'ignore'),
        array('sys_privatekeystr', 'delhh', 2, 'callback'),
        array('sys_privatekeystr', '', 2, 'ignore'),
    );

    //2019-4-4  任梦龙：判断到账方案中冻结方案的比例
    protected function setDzbl($id)
    {
        $dzbl = M('moneytype')->where('del = 0 AND moneytypeclassid = ' . $id)->sum('dzbl');
        if ($dzbl >= 1) {
            return false;
        }
        return true;
    }

    //2019-4-18 rml：验证唯一性,未删除，系统的
    protected function checkName($name)
    {
        $id = I('request.id', 0);
        if ($id) {
            $count = D('payapiaccount')->where(['bieming' => $name, 'del' => 0, 'user_id' => 0, 'id' => ['NEQ', $id]])->count();
        } else {
            $count = D('payapiaccount')->where(['bieming' => $name, 'del' => 0, 'user_id' => 0])->count();
        }
        if ($count) {
            return false;
        }
        return true;
    }


    protected function delhh($str)
    {

        $qian = array(" ", "　", "\t", "\n", "\r");
        $str = str_replace("\n", '', $str);
        // $str = str_replace("+","%",$str);
        return $str;
    }

    public static function getAccounts()
    {
        return D('payapiaccount')->select();
    }

    /**
     * 修改单笔最小最大金额
     * @param $table_name
     * @param $id
     * @param $field_name
     * @param $edit_data
     * @return bool
     */
    public static function editMinMaxMoney($table_name, $id, $field_name, $edit_data)
    {
        $res = D($table_name)->where('id = ' . $id)->setField($field_name, $edit_data);
        if ($res) {
            return true;
        }
        return false;

    }

    //查找账号某个字段 2018-12-29汪桂芳添加
    public static function getAccountField($id, $field)
    {
        return D('payapiaccount')->where('id=' . $id)->getField($field);
    }

    //2019-1-9 任梦龙：添加回调函数的判断
    public function setMoneyClass($id)
    {
        $count = M('moneytype')->where('del = 0 AND moneytypeclassid = ' . $id)->count();
        if ($count <= 0) {
            return false;
        }
        return true;
    }


    public static function getInfo($id)
    {
        return D('payapiaccount')->where(['id' => ['eq', $id]])->find();
    }


    /**
     * 2019-2-19 任梦龙：获取商家下的账号id
     */
    public static function getAccountidList($where)
    {
        return M('payapiaccount')->where($where)->field('id')->select(); //商家下的账号
    }

    /**
     * 2019-3-14 任梦龙：根据账号id获取账号名称
     */
    public static function getAccountName($id)
    {
        return M('payapiaccount')->where(['id' => $id])->getField('bieming');
    }

    //2019-3-28 任梦龙：修改删除状态
    public static function delPayapiAccount($user_id)
    {
        return M('payapiaccount')->where(['user_id' => $user_id])->setField('del', 1);
    }

    //2019-4-4 任梦龙：修改删除状态
    public static function changeAccountType($id, $type_name, $type_val)
    {
        return M('payapiaccount')->where(['id' => $id])->setField($type_name, $type_val);
    }

    //2019-4-4 任梦龙：修改数据
    public static function editPayapiAccount($account_id, $data)
    {
        return M('payapiaccount')->where(['id' => $account_id])->save($data);
    }


}

