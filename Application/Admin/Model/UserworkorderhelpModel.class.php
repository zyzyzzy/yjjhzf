<?php
/**
 * Created by PhpStorm.
 * User: 汪桂芳
 * Date: 2019/01/23
 * Time: 15:00
 * 用户工单帮助文档
 */

namespace Admin\Model;

use Think\Model;

class UserworkorderhelpModel extends Model
{
    //添加记录
    public static function addInfo($data)
    {
        return M('userworkorderhelp')->add($data);
    }

    //查询单条记录
    public static function getInfo($workorder_id)
    {
        return M('userworkorderhelp')->where('workorder_id='.$workorder_id)->find();
    }

    //查询单条记录
    public static function getInfoById($id)
    {
        return M('userworkorderhelp')->where('id='.$id)->find();
    }

    //编辑记录
    public static function editInfo($id,$data)
    {
        return M('userworkorderhelp')->where('id='.$id)->save($data);
    }

    //删除记录
    public static function delInfo($id)
    {
        return M('userworkorderhelp')->where('id='.$id)->delete();
    }

    //批量删除
    public static function delAllHelp($id_str)
    {
        $where['id'] = ['in',$id_str];
        return M('userworkorderhelp')->where($where)->delete();
    }

    /**
     * 帮助文档数据分页
     * @param $where
     * @return array
     */
    public static function workOrderPage($where)
    {
        $count = M('userworkorderhelp')->where($where)->count();
        $list = M('userworkorderhelp')->where($where)->order('date_time DESC')->page(I('post.page', '1'), I('post.limit', '10'))->select();   //用户工单列表
        if($list){
            foreach ($list as $key => $val) {
                $content = html_entity_decode($val['content']);
                if(mb_strlen($content)>45){
                    $list[$key]['content'] = mb_substr($content,0,45).'...';
                }else{
                    $list[$key]['content'] = $content;
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

    public static function getWorkNum()
    {
        $num = randpw(16,'MIX');
        $res = self::getWorkByNum($num);
        if($res){
            self::getWorkNum();
        }
        return $num;
    }

    //通过工单编号查询数据
    public static function getWorkByNum($work_num)
    {
        return M('userworkorderhelp')->where('work_num = "' . $work_num . '"')->find();
    }

    //获取帮助文档标题
    public static function getHelpTitle($id)
    {
        return M('userworkorderhelp')->where('id='.$id)->getField('title');
    }
}