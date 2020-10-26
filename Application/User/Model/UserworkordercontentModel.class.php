<?php
/**
 * Created by PhpStorm.
 * User: 汪桂芳
 * Date: 2019/01/18
 * Time: 上午 11:21
 * 用户工单沟通记录模型
 */

namespace User\Model;

use Think\Model;

class UserworkordercontentModel extends Model
{
   //查询某条工单的沟通记录
    public static function getContent($work_id)
    {
        $old_list = D('userworkordercontent')->where('workorder_id='.$work_id)->order('datetime asc')->select();
        $list = [];
        foreach ($old_list as $k=>$v){
            $list[$v['id']] = $v;
        }
        if($list){
            foreach ($list as $k=>$v){
                //查询用户名
                if($v['child_id']>0){
                    $list[$k]['user_name'] = M('childuser')->where('id='.$v['child_id'])->getField('bieming');
                }else{
                    $list[$k]['user_name'] = M('user')->where('id='.$v['user_id'])->getField('bieming');
                }

                $list[$k]['content'] = html_entity_decode($v['content']);

                //判断是否为敏感信息
                if($v['sensitive']==1 && $v['superior_id']){
                    $list[$v['superior_id']]['sensitive_content'] = $list[$k]['content'];
                    unset($list[$k]);
                }

            }
        }
        return $list;
    }

    //将用户的追问信息改为已读
    public static function changeRead($work_id){
        $where = [
            'workorder_id'=>$work_id,
            'admin_user'=>'admin'
        ];
        $save = [
            'read'=>1
        ];
        return D('userworkordercontent')->where($where)->save($save);
    }

    //添加回复记录
    public static function addContent($data){
        return D('userworkordercontent')->add($data);
    }

    //查询工单id
    public static function getWorkOrderid($id)
    {
        return D('userworkordercontent')->where('id='.$id)->getField('workorder_id');
    }
}