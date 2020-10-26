<?php
/**
 * Created by PhpStorm.
 * User: 任梦龙
 * Date: 2019/1/10/010
 * Time: 上午 11:15
 * 用户工单模型
 */

namespace Admin\Model;

use Think\Model;

class UserworkorderModel extends Model
{
    //查找单条记录
    public static function getWorkInfo($id)
    {
        return M('userworkorder')->where('id = ' . $id)->find();
    }

    //查找工单编号
    public static function getWorkNum($id)
    {
        return M('userworkorder')->where('id = ' . $id)->getField('work_num');
    }

    //通过工单编号查询数据
    public static function getWorkByNum($work_num)
    {
        return M('userworkorder')->where('work_num = "' . $work_num . '"')->find();
    }


    //软删除单个用户工单记录
    public static function delUserWork($id)
    {
        $res = M('userworkorder')->where('id = ' . $id)->delete();
        if($res){
            //删除此工单的沟通记录
            M('userworkordercontent')->where('workorder_id = ' . $id)->delete();
        }
        return $res;
    }

    //批量删除
    public static function delAllWork($id_str)
    {
        $where['id'] = ['in',$id_str];
        $res = M('userworkorder')->where($where)->delete();
        if($res){
            //删除此工单的沟通记录
            $wh['workorder_id'] = ['in',$id_str];
            M('userworkordercontent')->where($wh)->delete();
        }
        return $res;
    }

    //修改工单状态
    public static function changeUserWork($id, $status)
    {
        $data['status'] = $status;
        if($status==3){
            $data['update_time'] = date('Y-m-d H:i:s');
        }
        return M('userworkorder')->where('id = ' . $id)->save($data);
    }

    //将用户的工单改为已读
    public static function changeRead($work_id){
        $where = [
            'id'=>$work_id,
        ];
        $save = [
            'read'=>1
        ];
        return D('userworkorder')->where($where)->save($save);
    }

    /**
     * 工单数据分页
     * @param string $type user == 用户 ; admin == 系统
     * @param $where
     * @return array
     */
    public static function workOrderPage($where)
    {
        $count = M('userworkorder')->where($where)->count();
        $list = M('userworkorder')->join('LEFT JOIN '.C('DB_PREFIX').'user ON '.C('DB_PREFIX').'user.id = '.C('DB_PREFIX').'userworkorder.user_id')->where($where)
            ->order('date_time DESC')->page(I('post.page', '1'), I('post.limit', '10'))->field(C('DB_PREFIX').'userworkorder.*,'.C('DB_PREFIX').'user.username')->select();   //用户工单列表
        if($list){
            foreach ($list as $key => $val) {
                $list[$key]['content'] = html_entity_decode($val['content']);
                //判断是否有未读消息
                $wh = [
                    'workorder_id'=>$val['id'],
                    'admin_user'=>'user',
                    'read'=>0
                ];
                $all = M('userworkordercontent')->where($wh)->select();
                if($all || $val['read']==0){
                    $list[$key]['read'] = 1;
                }else{
                    $list[$key]['read'] = 0;
                }
                //判断是否能添加帮助文档
                $help = UserworkorderhelpModel::getInfo($val['id']);
                if(!$help && $val['status']==3){
                    $list[$key]['help'] = 1;
                }else{
                    $list[$key]['help'] = 0;
                }
                //判断是否能删除
                if(!$help){
                    $list[$key]['del'] = 1;
                }else{
                    $list[$key]['del'] = 0;
                }
            }
        }
        $return_arr = [
            'code' => 0,
            'msg' => '数据加载成功', //响应结果
            'count' => $count, //总页数
            'data' => $list
        ];
        return $return_arr;

    }

}