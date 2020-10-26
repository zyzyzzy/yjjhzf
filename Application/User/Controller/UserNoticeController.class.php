<?php

namespace User\Controller;

use Admin\Model\UserModel;

class UserNoticeController extends UserCommonController
{
    //公告列表页面
    public function noticeList()
    {
        $this->display();
    }

    //加载列表页面
    public function loadNoticeList()
    {
        $where = [];
        $where[0] = 'user_id=0 OR user_id=' . session('user_info.id');
        $where[1] = 'del=0';
        $count = D('usernotice')->where($where)->count();
        $datalist = D('usernotice')
            ->field('id,user_id,notice,date_time,title')
            ->where($where)
            ->page(I("post.page", "1"), I("post.limit", "10"))
            ->order('id DESC')
            ->select();
//        foreach ($datalist as $key => $val) {
//            $datalist[$key]['user_name'] = session('user_info.username');
//        }
        $ReturnArr = [
            'code' => 0,
            'msg' => '数据加载成功',
            'count' => $count,
            'data' => $datalist
        ];
        $this->ajaxReturn($ReturnArr);
    }

    //查看公告
    public function seeNotice()
    {
        $id = I('get.id');
        $find = M('usernotice')->where('id=' . $id)->find();
        $this->assign('find', $find);
        $this->display();
    }


}