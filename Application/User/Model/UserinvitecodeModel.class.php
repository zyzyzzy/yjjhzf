<?php

namespace User\Model;

use Think\Model;

class UserinvitecodeModel extends Model
{
    protected $_scope = array(
        // 命名范围normal
        //2019-3-6 任梦龙：修改字段名 pid  admin_id  user_id
        'default_scope' => array(
            'field' => [
                "id"
                , "pid"
                , "invite_code"
                , "make_id"
                , "type"
                , "user_id"
                , "create_time"
                , "over_time"
                , "use_time"
                , "reg_type"
                , "status"
                , "del"
            ],
        ),
    );
    //自动完成
    protected $_auto = array(
        ['create_time', 'YmdHis', 1, 'function'],
    );
    //自动验证
    protected $_validate = array(
        ['invite_code', 'require', '邀请码不能为空', 0],  //2019-4-19 rml：修改
        ['invite_code', 'checkName', '邀请码已被注册', 0, 'callback', 3],
        ['over_time', 'require', '过期时间不能为空', 0],
        ['over_time', 'checkTime', '过期时间不得小于当前时间', 0, 'callback', 3],
        ['reg_type', 'require', '请选择注册类型', 0],
        ['status', 'require', '请选择使用状态', 0],
    );

    protected function checkTime($over_time)
    {
        if (strtotime($over_time) < time()) {
            return false;
        } else {
            return true;
        }
    }

    //2019-4-15 rml：判断是否有重复的
    protected function checkName($name)
    {
        $id = I('request.id', 0);
        if (!$id) {
            $count = D('userinvitecode')->where(['invite_code' => $name, 'del' => 0])->count();
        } else {
            $count = D('userinvitecode')->where(['invite_code' => $name, 'del' => 0, 'id' => ['NEQ', $id]])->count();
        }
        if ($count) {
            return false;
        }
        return true;
    }


    /*
     * 通过用户id从邀请码表中获取一条数据
     */

    public static function getOneInfo($id)
    {
        return D('userinvitecode')->where('del=0')->find($id);
    }


    /*
     * 刷新页面时，通过当前时间和设置的过期时间的对比，判断邀请码是否过期
     * 如果小于等于当前时间，则status更新为已过期
     */

    public static function compareTime()
    {
        $over_time = D('userinvitecode')->field(['over_time', 'id'])->where('del=0')->select();
        foreach ($over_time as $val) {
            if (strtotime($val['over_time']) <= time()) {
                D('userinvitecode')->where(['id' => ['EQ', $val['id']]])->save(['status' => 2]);
            }
        }
    }

    /**
     * 2019-3-7 任梦龙：删除记录（单条或多条）
     */
    public static function delInviteCode($where)
    {
        return M('userinvitecode')->where($where)->setField('del', 1);
    }

    /**
     * 2019-3-7 任梦龙：根据id获取邀请码
     */
    public static function getInviteCode($id)
    {
        return M('userinvitecode')->where('id=' . $id)->getField('invite_code');
    }


}
