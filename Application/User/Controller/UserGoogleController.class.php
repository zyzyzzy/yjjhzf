<?php

namespace User\Controller;

use Think\Controller;
use User\Model\GooglecodeModel;

class UserGoogleController extends UserCommonController
{

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 谷歌验证页面
     */
    public function googleInfo()
    {
        if (!empty(session('child_info'))) {
            $google = GooglecodeModel::getInfo('child', session('child_info.id'));
            if ($google) {
                $google_code = 1;  //表示已开通还未开启
                if ($google['status'] != 1) {
                    $src = GooglecodeModel::createSrc('子账号:' . session('child_info.child_name'), $google['secret']);  //生成二维码
                    $this->assign('src', $src);
                } else {
                    $google_code = 2;  //表示已经开启
                }
            } else {
                $google_code = 0;  //表示还未开通
            }
            $type = 1;  //代表子账号
        } else {
            $google = GooglecodeModel::getInfo('user', session('user_info.id'));
            if ($google) {
                $google_code = 1;  //表示已开通还未开启
                if ($google['status'] != 1) {
                    $src = GooglecodeModel::createSrc('用户名:' . session('user_info.username'), $google['secret']);  //生成二维码
                    $this->assign('src', $src);
                } else {
                    $google_code = 2;  //表示已经开启
                }
            } else {
                $google_code = 0;  //表示还未开通
            }
            $type = 0;  //代表主用户
        }
        $this->assign('google_code', $google_code);
        $this->display();
    }

    /**
     * 验证用户输入的二维码
     */
    public function verifyCode()
    {
        $msg = "开启谷歌二次验证:";
        $user_id = session('user_info.id');
        $child_id = session('child_info.id');
        $code = I('code');
        if ($child_id > 0) {
            $secret = GooglecodeModel::getSecret('child', $child_id);
            $res = GooglecodeModel::verifyCode($secret, $code);
            if ($res) {
                //修改状态
                GooglecodeModel::saveStatus('child', $child_id);
                $this->addUserOperate($msg.'开启成功');
                $this->ajaxReturn(['status'=>'ok','验证成功,已开启谷歌验证']);
            } else {
                $this->addUserOperate($msg.'开启谷歌验证失败,验证码输入错误');
                $this->ajaxReturn(['status'=>'no','验证失败,请稍后重试']);
            }
        } else {
            $secret = GooglecodeModel::getSecret('user', $user_id);
            $res = GooglecodeModel::verifyCode($secret, $code);
            if ($res) {
                //修改状态
                GooglecodeModel::saveStatus('user', $user_id);
                $this->addUserOperate($msg.'开启成功');
                $this->ajaxReturn(['status'=>'ok','验证成功,已开启谷歌验证']);
            } else {
                $this->addUserOperate($msg.'开启谷歌验证失败,验证码输入错误');
                $this->ajaxReturn(['status'=>'no','验证失败,请稍后重试']);
            }
        }

    }

}