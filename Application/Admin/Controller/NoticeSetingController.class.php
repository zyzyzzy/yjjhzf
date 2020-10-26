<?php
/**
 * 管理后台：公告发布控制器
 */

namespace Admin\Controller;

use Admin\Model\UserModel;
use Admin\Model\UsernoticeModel;

class NoticeSetingController extends CommonController
{
    //公告列表页面
    public function noticeList()
    {
        $user_list = UserModel::selectUser(['del' => 0]);  //查询未删除了用户列表
        $this->assign('user_list', $user_list);
        $this->display();
    }

    //加载列表页面
    public function loadNoticeList()
    {
        $where = [];
        $where[0] = 'del=0';
        $user_id = I('post.user_id');
        if ($user_id <> '') {
            $where[1] = 'user_id=' . $user_id;
        }
        $count = D('usernotice')->where($where)->count();
        $datalist = D('usernotice')->scope('default_scope')->where($where)->page(I("post.page", "1"), I("post.limit", "10"))->order('id DESC')->select();
        foreach ($datalist as $key => $val) {
            if ($val['user_id']) {
                $datalist[$key]['user_name'] = UserModel::getUserName($val['user_id']);
            } else {
                $datalist[$key]['user_name'] = '所有用户';
            }
        }
        $ReturnArr = [
            'code' => 0,
            'msg' => '数据加载成功', //响应结果
            'count' => $count, //总页数
            'data' => $datalist
        ];
        $this->ajaxReturn($ReturnArr);
    }

    //添加公告
    public function addNotice()
    {
        $user_list = UserModel::selectUser(['del' => 0]);  //查询未删除了用户列表
        $this->assign('user_list', $user_list);
        $this->display();
    }

    //确认添加
    public function noticeAdd()
    {
        $user_id = I('post.user_id', 0, 'intval');
        if ($user_id) {
            $user_name = UserModel::getUserName($user_id);
            $msg = '给用户[' . $user_name . ']发布公告:';
        } else {
            $msg = '给所有用户发布公告:';
        }
        $return = AddSave('usernotice', 'add', '发布公告');
        //成功时循环将公告id存入已读表
        $data = [
            'status' => 0,
            'read_time' => date('Y-m-d H:i:s')
        ];
//        if ($return['status'] == 'ok') {
//            if ($user_id) {
//                $data['user_id'] = $user_id;
//                $data['notice_id'] = $return['id'];
//                M('readnotice')->add($data);
//            } else {
//                $user_list = UserModel::selectUser(['del'=>0]);
//                foreach($user_list as $key => $val){
//                    $data['user_id'] = $val['id'];
//                    $data['notice_id'] = $return['id'];
//                    M('readnotice')->add($data);
//                }
//            }
//        }
        $this->addAdminOperate($msg . $return['msg']);
        $this->ajaxReturn($return, 'json');
    }

    //修改页面
    public function editNotice()
    {
        $id = I('get.id');
        $find = UsernoticeModel::getNotice($id);
        $user_list = UserModel::selectUser(['del' => 0]);  //查询未删除了用户列表
        $this->assign('user_list', $user_list);
        $this->assign('find', $find);
        $this->display();
    }

    //确认修改
    public function noticeEdit()
    {
        $user_id = I('post.user_id');
        if ($user_id) {
            $user_name = UserModel::getUserName($user_id);
            $msg = '修改用户[' . $user_name . ']的公告:';
        } else {
            $msg = '修改所有用户的公告:';
        }
        $return = AddSave('usernotice', 'save', '修改公告');
        $this->addAdminOperate($msg . $return['msg']);
        $this->ajaxReturn($return, 'json');
    }

    //查看记录
    public function seeNoitice()
    {
        $id = I('get.id');
        $find = UsernoticeModel::getNotice($id);
        $this->assign('find', $find);
        $this->display();
    }

    //删除公告记录
    //2019-5-6 rml：优化
    public function delNotice()
    {
        $id = I('post.id');
//        $notice_num = UsernoticeModel::getNoticeNum($id);
        $find = UsernoticeModel::getNotice($id);
        $msg = '删除公告[' . $find['title'] . ']:';
        $res = UsernoticeModel::deleteNotice($id);
        if ($res) {
            $this->addAdminOperate($msg . '删除成功');
            $this->ajaxReturn(['status' => 'ok', 'msg' => '删除成功']);
        }
        $this->addAdminOperate($msg . '删除失败');
        $this->ajaxReturn(['status' => 'no', 'msg' => '删除失败']);
    }

    //批量删除
    public function delAllNotice()
    {
        $msg = '批量删除公告:';
        $id_str = I("post.idstr", "");
        if ($id_str == "") {
            $this->ajaxReturn(['status' => 'no', 'msg' => '请选择公告']);
        }
        $res = UsernoticeModel::delAll($id_str);
        if ($res) {
            $this->addAdminOperate($msg . '删除成功');
            $this->ajaxReturn(['status' => 'ok', 'msg' => '删除成功']);
        }
        $this->addAdminOperate($msg . '删除失败');
        $this->ajaxReturn(['status' => 'no', 'msg' => '删除失败']);
    }

}