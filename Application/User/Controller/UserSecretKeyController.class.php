<?php

namespace User\Controller;

use User\Model\SecretkeyModel;

class UserSecretKeyController extends UserCommonController
{
    public function __construct()
    {
        parent::__construct();
    }

    //密钥管理总页面
    //2019-4-11 rml：如果用户还未认证，则不允许修改密钥
    public function secretKeyList()
    {
        $authentication = M('user')->where(['id' => session('user_info.id')])->getField('authentication');
        if ($authentication == 3) {
            $renzheng = 1;
        } else {
            $renzheng = 0;
        }
        $this->assign('renzheng', $renzheng);
        session('switch_code', 1);
        //2019-4-15 rml：根据当前用户id查询密钥记录
        $find = SecretkeyModel::userKeyFind(session('user_info.id'));
        $this->assign('find', $find);
        $this->display();
    }

    //2019-3-27 任梦龙：用户的MD5密钥页面
    /****************************************/
    public function md5Secretkey()
    {
        $code_type = $this->getCodeType();
        $this->assign('code_type', $code_type);
        $find = SecretkeyModel::getUserSecret(I('get.id'));
        $this->assign('find', $find);
        $this->display();
    }

    //重新生成MD5密钥
    public function updateMd5key()
    {
        $str = SecretkeyModel::createdMd5key();
        $this->ajaxReturn(['status' => 'success', 'code' => $str]);
    }

    //确认修改MD5密钥
    public function editMd5key()
    {
        $msg = '修改md5密钥:';
        $verfiy_code = I('post.verfiy_code', '', 'trim');
        $code_type = I('post.code_type', 0, 'intval');
        $this->checkVerifyCode($verfiy_code, $code_type, $msg);
        $return = AddSave('secretkey', 'save', '修改md5密钥');
        $this->addUserOperate($msg . $return['msg']);
        $this->ajaxReturn($return, "json");
    }
    /***************************************************/

    //查看系统公钥：用户只可以查看，不可以修改
    public function seeSyspublicKey()
    {
        $code_type = $this->getCodeType();
        $this->assign('code_type', $code_type);
        $find = SecretkeyModel::getUserSecret(I('get.id'));
        $this->assign('sys_publickey', $find['sys_publickey']);
        $this->display();
    }

    //用户密钥：可修改
    public function userKey()
    {
        $code_type = $this->getCodeType();
        $this->assign('code_type', $code_type);
        $find = SecretkeyModel::getUserSecret(I('get.id'));
        $this->assign("find", $find);
        $this->display();
    }

    //修改用户密钥
    public function editUserkey()
    {
        //如果是子账号，则没有权利修改
        if (!empty(session('child_info'))) {
            $this->ajaxReturn(['status' => 'no', 'msg' => '子账户没有权限修改用户密钥']);
        }
        $msg = '修改用户[' . session('user_info.username') . ']的用户密钥(未上传文件):';
        //检测验证码的代码
        $verfiy_code = I('post.verfiy_code', '', 'trim');
        $code_type = I('post.code_type', 0, 'intval');
        $this->checkVerifyCode($verfiy_code, $code_type, $msg);
        $return = AddSave('secretkey', 'save', '用户密钥修改');
        //2019-4-16 rml：添加密钥修改记录
        $record = [
            'user_id' => session('user_info.id'),
            'date_time' => date('Y-m-d H:i:s'),
            'msg' => $msg . $return['msg']
        ];
        if (!empty(session('child_info'))) {
            $record['child_id'] = session('child_info.id');
        }
        M('secretkeyrecord')->add($record);
        $this->addUserOperate($msg . $return['msg']);
        $this->ajaxReturn($return, 'json');
    }

    //上传用户密钥文件
    public function uploadUserKey()
    {
        $msg = '上传用户[' . session('user_info.username') . ']的用户密钥:';
        //如果是子账号，则没有权利修改
        if (!empty(session('child_info'))) {
            $this->ajaxReturn(['status' => 'no', 'msg' => '子账户没有权限修改用户密钥']);
        }
        //检测验证码的代码
        $id = I('post.id');
        $upload_code = I('post.upload_code');
        $code_type = I('post.code_type', 0, 'intval');
        $this->checkVerifyCode($upload_code, $code_type, $msg);
        //2019-4-15 rml：将密钥文件名进行加密  (字段名_主用户id)
        $file_name = hash('sha1', 'user_keypath_' . session('user_info.id'));
        $upload = new \Think\Upload();// 实例化上传类
        $upload->maxSize = 3 * 1024 * 1024;// 设置附件上传大小3M
        $upload->exts = array('pem', 'txt');// 设置附件上传类型
        $upload->rootPath = C('USER_KEY_STR_PATH'); // 设置附件上传根目录
        $upload->saveName = $file_name;   //文件名
        $upload->replace = true;   //存在同名文件覆盖
        $upload->subName = 'user-' . session('user_info.id');   //子目录创建方式，以用户id命名
        // 上传单个文件
        $info = $upload->uploadOne($_FILES['file']);
        //2019-4-15 rml：添加密钥修改记录
        $record = [
            'user_id' => session('user_info.id'),
            'date_time' => date('Y-m-d H:i:s'),
        ];
        if (!empty(session('child_info'))) {
            $record['child_id'] = session('child_info.id');
        }
        if (!$info) {
            $record['msg'] = $msg . $upload->getError();
            M('secretkeyrecord')->add($record);
            $this->addUserOperate($msg . $upload->getError());
            $this->ajaxReturn(['status' => 'no', 'msg' => $upload->getError()]);
        } else {
            // 上传成功 拼接上传文件的路径，存入数据库，并且读取文件内容，也存入数据库
            $file_path = C('USER_KEY_STR_PATH') . $info['savepath'] . $info['savename'];
            //读取密钥文件内容，去除两端的字符串,空格，换行，并将内容保存到用户密钥表中对应的字段
            $file_contents = file_get_contents($file_path);
            $public_begin = '-----BEGIN PUBLIC KEY-----';
            $public_end = '-----END PUBLIC KEY-----';
            $privte_begin = '-----BEGIN RSA PRIVATE KEY-----';
            $private_end = '-----END RSA PRIVATE KEY-----';
            $private_begin_rsa = '-----BEGIN PRIVATE KEY-----';
            $private_end_rsa = '-----END PRIVATE KEY-----';
            $contents = str_replace(array("\r\n", "\r", "\n", $public_begin, $public_end, $privte_begin, $private_end, $private_begin_rsa, $private_end_rsa), '', $file_contents);
            $res = SecretkeyModel::editUserSecret($id, ['user_keypath' => $contents, 'user_key_path' => $file_path, 'upload_time' => date('Y-m-d H:i:s')]);
            if ($res) {
                $record['msg'] = $msg . '上传成功';
                M('secretkeyrecord')->add($record);
                $this->addUserOperate($msg . '上传成功');
                $this->ajaxReturn([
                    'status' => 'ok',
                    'msg' => '上传成功',
                    'file_contents' => $contents  //文件内容
                ]);
            } else {
                $record['msg'] = $msg . '上传失败';
                M('secretkeyrecord')->add($record);
                unlink($file_path);
                $this->addUserOperate($msg . '上传失败');
                $this->ajaxReturn(['status' => 'no', 'msg' => '上传失败']);
            }
        }
    }

    //查看系统公钥，用户密钥前，先验证谷歌验证
    public function veryGoogle()
    {
        $google_code = I('post.googlecode');  //提交的验证码
        if (!$google_code) {
            $this->ajaxReturn(['status' => 'no', 'msg' => '验证码不得为空']);
        }
        $key_type = I('post.key_type');
        //区分主用户和子账号
        if (empty(session('child_id'))) {
            $type = 'user';
        } else {
            $type = 'child';
        }
        //在Common控制器里建立判断谷歌验证码的方法
        $res = $this->veryGoogleCode($type, session('user_info.id'), $google_code);
        //如果验证成功，将系统公钥显示，否则继续验证
        if ($res) {
            $find = SecretkeyModel::userKeyFind(session('user_info.id'));
            //显示系统公钥
            if ($key_type == 'publickey') {
                $str = $find['sys_publickeypath'] ? $find['sys_publickeypath'] : '';
            }
            //显示用户密钥
            if ($key_type == 'userkey') {
                $str = $find['user_keypath'] ? $find['user_keypath'] : '';
            }
            $this->ajaxReturn(['status' => 'ok', 'msg' => '验证成功', 'str' => $str]);
        } else {
            $this->ajaxReturn(['status' => 'no', 'msg' => '验证失败']);
        }
    }


    public function veryGoogle2()
    {
        $google_code = I('post.googlecode2');  //提交的验证码
        if (!$google_code) {
            $this->ajaxReturn(['status' => 'no', 'msg' => '验证码不得为空']);
        }
        $key_type = I('post.key_type');
        //区分主用户和子账号
        if (empty(session('child_id'))) {
            $type = 'user';
        } else {
            $type = 'child';
        }
        //在Common控制器里建立判断谷歌验证码的方法
        $res = $this->veryGoogleCode($type, session('user_info.id'), $google_code);
        //如果验证成功，将系统公钥显示，否则继续验证
        if ($res) {
            $this->ajaxReturn(['status' => 'ok', 'msg' => '验证成功']);
        } else {
            $this->ajaxReturn(['status' => 'no', 'msg' => '验证失败']);
        }
    }

}