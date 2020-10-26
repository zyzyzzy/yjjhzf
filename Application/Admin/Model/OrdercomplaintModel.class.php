<?php
/*
 * user 汪桂芳
 * time 2019/01/23 10:40
 * 订单投诉过程模型
 */
namespace Admin\Model;

use Think\Model;

class OrdercomplaintModel extends Model
{
    //插入数据
    public static function addComplaint($data){
        return M('ordercomplaint')->add($data);
    }

    //根据订单id查询数据
    public static function getComplaint($order_id){
        $list = M('ordercomplaint')->where('order_id='.$order_id)->select();
        foreach ($list as $k=>$v){
            if($v['admin_id']){
                $list[$k]['admin_name'] = M('adminuser')->where('id='.$v['admin_id'])->getField('bieming');
            }
        }
        return $list;
    }

    //获取总条数
    public static function countComplaint($order_id){
        return M('ordercomplaint')->where('order_id='.$order_id)->count();
    }

    //判断是否有投诉记录
    public static function isComplaint($order_id){
        return M('ordercomplaint')->where('order_id='.$order_id.' and change_status=1')->count();
    }
}