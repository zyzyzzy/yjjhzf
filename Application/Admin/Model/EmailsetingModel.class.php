<?php

namespace Admin\Model;

use Think\Model;

class EmailsetingModel extends Model
{
    //自动验证
    protected $_validate = array(
        ['smtp', 'require', 'SMTP地址不能为空', 1],
        ['dk', 'require', '端口不能为空', 1],
        ['em_code', 'require', '邮箱授权码不能为空', '', '', 1], //只在新增时判断不为空
        ['user_name', 'require', '用户名不能为空', 1],
        ['email', 'require', '用户邮箱不能为空', 1],
        ['email', 'email', '邮箱格式错误', 1],   //tp自带的邮箱验证规则
        ['email','','邮箱已经存在！',0,'unique',3], //2019-3-18 任梦龙:验证email字段是否唯一
        ['receive_email', 'require', '收件人邮箱不能为空', 1],
        ['receive_email', 'email', '收件人邮箱格式错误', 1],   //tp自带的邮箱验证规则
    );
    //自动完成
    protected $_auto = array(
        array('em_code', '', 2, 'ignore'),  //这一规则表示某个字段在编辑时如果留空的话则忽略
    );

    public static function getEmails()
    {
        return D('emailseting')->select();
    }

    //2019-3-18 任梦龙：查询中记录
    public static function getCount($where)
    {
        return D('emailseting')->where($where)->count();
    }

    public static function loadEmails($where, $page, $limit)
    {
        return D('emailseting')->where($where)->page($page, $limit)->select();
    }

    public static function getInfo($id)
    {
        return D('emailseting')->where("id='" . $id . "'")->find();
    }

    //2019-3-18 任梦龙：根据id删除邮箱
    public static function delEmail($id)
    {
        return D('emailseting')->where("id=" . $id)->delete();
    }

    //2019-3-18 任梦龙：根据id获取邮箱地址
    public static function getEmailName($id)
    {
        return D('emailseting')->where("id=" . $id)->getField('email');
    }
}