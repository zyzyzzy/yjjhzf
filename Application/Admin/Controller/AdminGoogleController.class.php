<?php

namespace Admin\Controller;

use Think\Controller;
use Admin\Model\GooglecodeModel;
use Admin\Model\AdminuserModel;

//整个Controller2019-02-14  任梦龙创建：管理员的二次验证
class AdminGoogleController extends CommonController
{

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 二次验证页面
     */
    public function googleInfo(){
        $this->assign("admin_id",$this->admin_id);
            $google = GooglecodeModel::getInfo('admin',$this->admin_id);
            //查询是否有此记录，没有则生成二维码
            if($google){
                $google['code'] = 1;
                if($google['status']!=1){
                    //查询管理员名称
                    $admin_name = AdminuserModel::getAdminName($this->admin_id);
                    $src = GooglecodeModel::createSrc('用户名 : '.$admin_name,$google['secret']);
                    $this->assign('src',$src);
                }
            }else{
                $google['code'] = 0;
            }


        $this->assign('google',$google);

        $this->display();
    }

    /**
     * 验证用户输入的二维码
     */
    public function verifyCode()
    {
        $code = I('code');
            $secret = GooglecodeModel::getSecret('admin',$this->admin_id);
            $res = GooglecodeModel::verifyCode($secret,$code);
            if($res){
                //修改状态
                GooglecodeModel::saveStatus('admin',$this->admin_id);
                $this->ajaxReturn(['status'=>'ok','msg'=>'验证成功,已开启二次验证']);
//                $this->operateReturn($this->admin_id,'用户开启二次验证成功','ok','验证成功,已开启二次验证');
            }else{
                $this->ajaxReturn(['status'=>'no','msg'=>'验证失败,请稍后重试']);
//                $this->operateReturn($this->admin_id,'用户开启二次验证失败,验证码输入错误','no','验证失败,请稍后重试');
            }


    }

}