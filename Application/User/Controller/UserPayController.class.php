<?php

namespace User\Controller;
use Think\Controller;
use User\Model\UserModel;

class UserPayController extends UserCommonController
{
    //用户自助收银设置页面
    public function selectTemplate()
    {
        $all = C('SELFCASHBACK');
        //查询用户是否设置背景
        $user_back = UserModel::getUserSelfcashBack(session('user_info.id'));
        $all_imgs = [];
        foreach ($all as $k=>$v){
            $all_imgs[$k]['img_name'] = $v;
            $all_imgs[$k]['img_src'] = '/'.C('SELFCASHBACK_URL').$v;
            if(C('SELFCASHBACK_URL').$v==$user_back){
                $all_imgs[$k]['select'] = 1;
            }
        }
        $this->assign('all_imgs',$all_imgs);
        $this->assign('user_back',$user_back);
        $this->display();
    }

    //确认选择的模板
    public function confirmTeplate()
    {
        $templateid = I('post.templateid');
        if($templateid){
            $msg = "用户选择自助收银背景图片:";
        }else{
            $msg = "用户取消自助收银背景图片:";
        }
        $user_back = UserModel::getUserSelfcashBack(session('user_info.id'));
        if(C('SELFCASHBACK_URL').$templateid!=$user_back){
            $res = UserModel::setUserSelfcashBack(session('user_info.id'),C('SELFCASHBACK_URL').$templateid);
        }
        if ($res) {
            $this->addUserOperate($msg . '设置成功');
            $this->ajaxReturn(['status' => 'ok', 'msg' => '设置成功',]);
        } else {
            $this->addUserOperate($msg . '设置失败');
            $this->ajaxReturn(['status' => 'no', 'msg' => '设置失败',]);
        }
    }
}