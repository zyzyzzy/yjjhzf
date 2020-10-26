<?php
/**
 * 发布公告模型
 */

namespace Admin\Model;

use Think\Model;

class UsernoticeModel extends Model
{
    protected $_scope = array(
        // 命名范围normal
        'default_scope' => array(
            'field' => [
                "id"
                , "user_id"
                , "notice"
                , "date_time"
                , "title"
            ],
        ),
    );
    //自动验证
    protected $_validate = array(
        ['title', 'require', '公告标题不能为空', 0],
        ['title', '2,100', '公告标题长度在2-100字符之间', 0,'length',3],
        ['notice', 'require', '公告内容不能为空', 0],
    );

    //自动完成
    protected $_auto = array(
        ['date_time', 'YmdHis', 1, 'function'],
        ['num', 'getNum', 1, 'callback'],   //新增时写入公告编号
    );

    protected function getNum()
    {
        $num = randpw(16, 'MIX');
        return $num;
    }

    //根据id查询记录
    public static function getNotice($id)
    {
        return M('usernotice')->where('id=' . $id)->find();
    }

    //根据id删除记录
    public static function deleteNotice($id)
    {
        return M('usernotice')->where('id=' . $id)->setField('del',1);
    }

    //批量删除记录
    public static function delAll($id_str)
    {
        return M('usernotice')->where(['id'=>['in',$id_str]])->setField('del',1);
    }

    //根据id查询公告编码
    public static function getNoticeNum($id)
    {
        return M('usernotice')->where('id=' . $id)->getField('num');
    }
}
