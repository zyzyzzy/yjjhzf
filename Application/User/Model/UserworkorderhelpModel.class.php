<?php
/**
 * Created by PhpStorm.
 * User: 汪桂芳
 * Date: 2019/02/21
 * Time: 15:00
 * 用户工单帮助文档
 */

namespace User\Model;

use Think\Model;

class UserworkorderhelpModel extends Model
{

    //查询单条记录
    public static function getInfoById($id)
    {
        return M('userworkorderhelp')->where('id=' . $id)->find();
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
        if ($list) {
            foreach ($list as $key => $val) {
                $content = html_entity_decode($val['content']);
                if (mb_strlen($content) > 45) {
                    $list[$key]['content'] = mb_substr($content, 0, 45) . '...';
                } else {
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

    //2019-4-9 任梦龙：批量删除帮助文档
    public static function delAll($id_str)
    {
        return M('userworkorderhelp')->where(['id' => ['in', $id_str]])->setField('del', 1);
    }
}