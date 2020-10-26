<?php

namespace Admin\Model;

use Think\Model;

class UserModel extends Model
{

    protected $_scope = array(
        // 命名范围normal
        'default_scope' => array(
            'field' => [

                "id"

                , "username"

                , "bieming"

                , "getmemberid(id) as memberid"

                , "getusertype(usertype) as usertype"

                , "getsjusername(superiorid) as superiorid"

                , "status"

                , "order"

                , "test_status"

                , "getuserrengzheng(authentication) as authentication"

                , "regdatetime"

//                ,"getsummoney(id) as money"

                , "getusermoney(id) as money"

                , "getuserfreezemoney(id) as freezemoney"

            ],
        ),

    );

    protected $_auto = array(
        ['regdatetime', 'YmdHis', 1, 'function'],
        ['usercode', 'getCode', 1, 'callback'],
    );

    protected $_validate = array(
        ['username', 'require', '用户名不能为空', 0],
        ['username', 'checkName', '用户名称已被注册', 0, 'callback', 3],
        ['username', 'patternName', '用户名称由大小写英文，数字组成，长度在2-20字符之间！', 0, 'callback', 3],
        ['bieming', 'require', '别名不能为空', 0],
        ['bieming', '2,20', '别名长度在2-20字符之间！', 0, 'length', 3],
        ['usertype', 'require', '用户类型不能为空', 0],
        ['status', 'require', '用户状态必填', 0],
        ['authentication', 'require', '认证状态必填', 0],
        ['version_id', 'require', '请选择接口版本', 0],
    );


    protected function checkName($name)
    {
        $count_user = D('user')->where(['username' => $name, 'del' => 0])->count();
        if ($count_user) {
            return false;
        }
        return true;
    }

    //验证用户名称格式：英文，数字
    protected function patternName($name)
    {
        $pattern = '/^[A-Za-z0-9]{2,20}$/';
        if (!preg_match($pattern, $name)) {
            return false;
        }
        return true;
    }

    protected function getCode()
    {
        $user_code = random_str(32);
        $count = M('user')->where(['usercode' => $user_code, 'del' => 0])->count();
        if ($count > 0) {
            $this->getCode();
        }
        return $user_code;
    }

    /**
     * @param $userid
     * @return mixed
     */
    public static function getBackCard($userid)
    {
        return D('bankcard')->where("userid='" . $userid . "'")->find();
    }

    /**
     * @param $userid
     * @return mixed
     */
    public static function getAuthPicture($userid)
    {
        return D('authpicture')->where("userid='" . $userid . "'")->find();
    }

    /**
     * @param $id
     * @return mixed
     */
    public static function getInfo($id)
    {
        return D('user')->find($id);
    }

    //用户名或商户号搜索
    public static function getUserByName($user_name)
    {
        $pre = C('DB_PREFIX');
        if ($user_name <> "") {
            $where = "(" . $pre . "user.username like '%" . $user_name . "%' or " . $pre . "secretkey.memberid like '%" . $user_name . "%')";
        }
        return M('user')->join('LEFT JOIN ' . $pre . 'secretkey ON ' . $pre . 'user.id=' . $pre . 'secretkey.userid')->where($where)
            ->field($pre . 'user.id as userid,' . $pre . 'user.username,' . $pre . 'secretkey.memberid')->select();
    }

    //获取交易接口版本id组
    public static function getVersionId($id)
    {
        return M('user')->where('id=' . $id)->getField('version_id');
    }

    //2019-3-25 任梦龙：获取结算接口版本id组
    public static function getSettleVersion($id)
    {
        return M('user')->where('id=' . $id)->getField('settle_version');
    }

    //修改交易接口版本
    public static function editVersionid($id, $version_id)
    {
        return D('user')->where('id=' . $id)->setField('version_id', $version_id);
    }

    //2019-3-25 任梦龙：修改结算接口版本
    public static function editSettleVer($id, $settle_version)
    {
        return D('user')->where('id=' . $id)->setField('settle_version', $settle_version);
    }

    //获取未删除用户的id和用户名
    public static function selectUser($where)
    {
        return M('user')->where($where)->field('id,username')->select();
    }

    //根据用户名获取id
    public static function getUserId($user_name)
    {
        return M('user')->where("username='" . $user_name . "'")->getField('id');
    }

    //根据id获取用户名
    public static function getUserName($id)
    {
        return M('user')->where('id=' . $id)->getField('username');
    }

    //判断某个交易版本是否被用户使用
    public static function checkVersionUser($version_id)
    {
        $all = M('user')->where(['version_id' => ['neq', '']])->select();
        if ($all) {
            foreach ($all as $k => $v) {
                $arr = explode(',', $v['version_id']);
                if (in_array($version_id, $arr)) {
                    return false;
                }
            }
            //2019-4-18 rml：如果循环出无结果，则返回
            return true;
        }
        return true;
    }

    //判断某个结算版本是否被用户使用
    public static function checkSettleVersionUser($version_id)
    {
        $all = M('user')->where(['settle_version' => ['neq', '']])->select();
        if ($all) {
            foreach ($all as $k => $v) {
                $arr = explode(',', $v['settle_version']);
                if (in_array($version_id, $arr)) {
                    return false;
                }
            }
            //2019-4-18 rml：如果循环出无结果，则返回
            return true;
        }
        return true;
    }

    //2019-3-25 任梦龙：删除用户记录
    public static function delUser($id)
    {
        M('user')->where('id=' . $id)->delete();
    }

    //2019-03-29汪桂芳:获取用户自助收银状态
    public static function getUserSelfcashStatus($user_id)
    {
        return D('user')->where('id=' . $user_id)->getField('selfcash_status');
    }

    //2019-03-29汪桂芳:修改用户自助收银状态
    public static function setUserSelfcashStatus($user_id, $status)
    {
        return D('user')->where('id=' . $user_id)->setField('selfcash_status', $status);
    }

    //获取用户自助收银背景图片路径
    public static function getUserSelfcashQrcode($user_id)
    {
        return M('user')->where('id=' . $user_id)->getField('selfcash_qrcode');
    }

    //存储二维码路径
    public static function setUserSelfcashQrcode($user_id, $imagname)
    {
        return M('user')->where('id=' . $user_id)->setField('selfcash_qrcode', $imagname);
    }

    //获取用户的usercode
    public static function getUserCode($user_id)
    {
        return M('user')->where('id=' . $user_id)->getField('usercode');
    }

    //2019-4-1 任梦龙：根据id获取同一账号设置状态
    public static function getSameUser($user_id)
    {
        return M('user')->where(['id' => $user_id])->getField('same_user');
    }

    //2019-4-1 任梦龙：修改数据
    public static function editUserInfo($user_id, $data)
    {
        return D('user')->where(['id' => $user_id])->save($data);
    }

    public static function getAuthentication($user_id)
    {
        return D('user')->where(['id' => $user_id])->getField('authentication');
    }

    public static function getPayapiId($user_id)
    {
        return M('user')->where(['id' => $user_id])->getField('payapi_id');
    }

    public static function getSelfPayapi($user_id)
    {
        return M('user')->where(['id' => $user_id])->getField('self_payapi');
    }
}
