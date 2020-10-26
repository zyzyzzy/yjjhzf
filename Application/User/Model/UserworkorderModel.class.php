<?php
/**
 * 用户工单模型
 */

namespace User\Model;

use Think\Model;

class UserworkorderModel extends Model
{
    protected $_scope = array(
        // 命名范围normal
        'default_scope' => array(
            'field' => [
                'id'
                , 'user_id'
                , 'title'
                , 'content'
                , 'status'
                , 'date_time'
                , 'update_time'
                , 'del'

            ],
        ),
    );
    //自动验证
    protected $_validate = array(
        array('title', 'require', '标题不能为空！', 0),
        array('content', 'require', '内容不能为空！', 0),
    );
    //自动完成
    protected $_auto = array(
        array('date_time', 'YmdHis', 1, 'function'),  //新增时写入时间戳
        array('work_num', 'getWorkNum', 1, 'callback'),  //新增时写入工单编号

    );

    //2019-5-6 rml:去掉再次判断，避免死掉
    public function getWorkNum()
    {
        $num = randpw(16,'MIX');
        $res = self::getWorkByNum($num);
//        if($res){
//            self::getWorkNum();
//        }
        return $num;
    }

    //查找单条记录
    public static function getWorkInfo($id)
    {
        return M('userworkorder')->where('id = ' . $id)->find();
    }

    //通过工单编号查询数据
    public static function getWorkByNum($work_num)
    {
        return M('userworkorder')->where('work_num = "' . $work_num . '"')->find();
    }

    //2019-02-27汪桂芳添加
    //通过工单id查询工单编号
    public static function getWorkNumById($id)
    {
        return M('userworkorder')->where('id = ' .$id)->getField('work_num');
    }


    //软删除单个用户工单记录
    public static function delUserWork($id)
    {
        return M('userworkorder')->where('id = ' . $id)->setField('user_del', 1);
    }

    //批量删除
    public static function delAllWork($id_str)
    {
        $where['id'] = ['in',$id_str];
        return M('userworkorder')->where($where)->setField('user_del', 1);
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

    /**
     * 工单数据分页
     * @param string $type user == 用户 ; admin == 系统
     * @param $where
     * @return array
     */
    public static function workOrderPage($where)
    {
        $count = M('userworkorder')->where($where)->count();
        $list = M('userworkorder')->page(I('post.page', '1'), I('post.limit', '10'))
            ->where($where)->order('date_time DESC')->select();   //用户工单列表
        foreach ($list as $key => $val) {
            $list[$key]['content'] = html_entity_decode($val['content']);
            //判断是否有未读消息
            $wh = [
                'workorder_id'=>$val['id'],
                'admin_user'=>'admin',
                'read'=>0
            ];
            $all = M('userworkordercontent')->where($wh)->select();
            if($all){
                $list[$key]['read'] = 1;
            }else{
                $list[$key]['read'] = 0;
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

    //查找工单编号
    public static function getWorkNumber($id)
    {
        return M('userworkorder')->where('id = ' . $id)->getField('work_num');
    }
}