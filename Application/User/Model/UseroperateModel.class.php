<?php
namespace User\Model;

use Think\Model;
//整Model2018-12-27汪桂芳创建
class UseroperateModel extends Model
{
    /**
     * 添加操作记录
     * @param $userid
     * @return mixed
     */
    public static function addOperateRecord($userid,$content)
    {
        $data = [
            'userid'=>$userid,
            'userip'=>getIp(),
            'operatedatetime'=>date('Y-m-d H:i:s'),
            'content'=>$content,
        ];
        return D('useroperaterecord')->add($data);
    }

    //2019-1-16 任梦龙：修改用户操作记录方法
    public static function addUserOperateRecord($userid,$content,$type)
    {
        $data = [
            'userid'=>$userid,
            'userip'=>getIp(),
            'operatedatetime'=>date('Y-m-d H:i:s'),
            'content'=>$content,
            'type'=>$type,  //2019-3-15 任梦龙：类型：0=主用户；1=子账号
        ];
        return D('useroperaterecord')->add($data);
    }

}