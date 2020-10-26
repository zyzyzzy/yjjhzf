<?php

namespace User\Controller;

use User\Model\UserauthimgsModel;
use User\Model\UseroperateModel;

class UserAuthController extends UserCommonController
{

    public function __construct()
    {
        parent::__construct();
    }

    //认证信息页面
    public function userAuthList()
    {
        $user_id = session('user_info.id');
        $user_auth = UserauthimgsModel::getUserAuth($user_id);
        if (!$user_auth) {
            $user_auth['authentication'] = 1;//未认证
        }
        $this->assign("idcard_front", $user_auth['idcard_front']);
        $this->assign("idcard_back", $user_auth['idcard_back']);
        $this->assign("idcard_hand", $user_auth['idcard_hand']);
        $this->assign("bankcard_front", $user_auth['bankcard_front']);
        $this->assign("bankcard_back", $user_auth['bankcard_back']);
        $this->assign("business_license", $user_auth['business_license']);
        $this->assign("authentication", $user_auth['authentication']);
        $this->assign('user_id', $user_id);
        $this->display();
    }

    //上传图片程序
    //2019-4-15 rml:修改
    public function upload()
    {
        $fieldsname = I("post.fieldsname");
        $user_id = I("post.user_id");
        $save_name = $fieldsname . '_' . $user_id;
        // 上传文件
        $upload = new \Think\Upload(); // 实例化上传类
        $upload->maxSize = 2097152; // 设置附件上传大小,单位字节,2M以内1024*1024*2
        $upload->exts = array('jpg', 'jpeg', 'gif', 'png', 'bmp'); // 设置附件上传类型
        $upload->rootPath = C('VERIFYINFO_PATH'); // 设置附件上传目录
        $upload->saveName = $save_name;   //文件名
        $upload->subName = 'user-' . $user_id;   //子目录创建方式，以账号id命名
        $upload->replace = true;   //存在同名文件覆盖
        $info = $upload->uploadOne($_FILES['file']);
        if (!$info) {
            $this->addUserOperate('用户上传认证图片:' . $upload->getError());
            // 上传错误提示错误信息
            $this->ajaxReturn(['code' => 0, 'msg' => $upload->getError()]);
        } else {
            // 上传成功
            $upload_path = C('VERIFYINFO_PATH') . $info['savepath'] . $info['savename'];//做图像处理的路径,不带最前面的/
            // 图像处理
            $image = new \Think\Image();
            //缩略
            /*$image->open($upload_path)
                ->thumb(400, 300,\Think\Image::IMAGE_THUMB_SCALE)
                ->save($upload_path);*/
            //水印
            $image->open($upload_path)
                ->text($_SERVER['SERVER_NAME'], './Public/fonts/1.ttf', 30, '#cccccc', \Think\Image::IMAGE_WATER_CENTER)
                ->save($upload_path);

            $Userverifyinfo = M("userauthimgs");
            $img_path = '/' . $upload_path;//存入数据库并且在前端显示的路径,需要加上前面的/
            $exist = UserauthimgsModel::getUserAuth($user_id);
            if ($exist) {
                $delfilename = trim($exist[$fieldsname], '/');
//                if($delfilename!=$img_path){
//                    unlink($delfilename);
//                }
                $data[$fieldsname] = $img_path;
//                $Userverifyinfo->where("user_id=" . $user_id)->save($data);
                UserauthimgsModel::saveUserAuth($user_id, $data);
            } else {
                $da = [
                    'user_id' => $user_id,
                    $fieldsname => $img_path,
                    'authentication' => 1
                ];
//                $Userverifyinfo->add($da);
                UserauthimgsModel::addUserAuth($da);
            }
            $this->addUserOperate('用户上传认证图片:上传图片成功');
            $this->ajaxReturn([
                'code' => 1,
                'msg' => '上传成功',
//                'src' => $img_path, //文件地址
//                'fieldsname' => $fieldsname, //类型
//                'user_id' => $user_id //用户id
            ]);
        }
    }

    //删除图片程序
    public function delete()
    {
        $user_id = I("user_id");
        $field_name = I("fieldsname");
        $fieldname = UserauthimgsModel::getFieldname($user_id, $field_name);
        if ($fieldname == '') {
            $this->ajaxReturn(['status' => 'no', 'msg' => '还未上传图片,请确认']);
        }
        $data[$field_name] = "";
        $res = UserauthimgsModel::saveUserAuth($user_id, $data);
        if ($res) {
            $delfilename = trim($fieldname,'/');
            unlink($delfilename);
            $this->addUserOperate('用户删除认证图片成功');
            $this->ajaxReturn(['status' => 'ok', 'msg' => '删除成功']);
        } else {
            $this->addUserOperate('用户删除认证图片失败');
            $this->ajaxReturn(['status' => 'no', 'msg' => '删除失败']);
        }
    }

    //申请认证程序
    public function applyAuth()
    {
        $user_id = I("user_id");
        $Userverifyinfo = M("userauthimgs");
        $data["authentication"] = 2;
//        $res = $Userverifyinfo->where("user_id=" . $user_id)->save($data);
        $res = UserauthimgsModel::saveUserAuth($user_id, ['authentication' => 2]);
        if ($res) {
            M('user')->where(['id' => $user_id])->save(['authentication' => 2]);
            $this->addUserOperate('用户成功申请认证,等待审核中');
            $this->ajaxReturn(['status' => 'ok', 'msg' => '已申请认证，请等待审核！']);
        } else {
            $this->addUserOperate('用户申请认证失败');
            $this->ajaxReturn(['status' => 'no', 'msg' => '申请失败!']);
        }
    }

}