<?php

namespace Admin\Controller;

use Admin\Model\DomainModel;
use Admin\Model\SecretkeyModel;
use Admin\Model\IpaccesslistModel;  //2019-1-25 任梦龙：新增
use Admin\Model\SecretkeyrecordModel;  //2019-2-22 任梦龙：新增
use Admin\Model\UsersecretkeypathModel;  //2019-3-12 任梦龙：用户密钥路径表模型
use Admin\Model\UserModel;  //2019-3-12 任梦龙：用户密钥路径表模型


//2019-1-21 任梦龙：将Controller更改为CommonController
//2019-3-29 任梦龙：修改
class SecretkeyController extends CommonController
{
    //用户密钥/域名页面
    public function SecretkeyDomain()
    {
        $userid = I('get.userid');
        $authentication = UserModel::getAuthentication($userid);
        if ($authentication == 3) {
            $renzheng = 1;
        } else {
            $renzheng = 0;
        }
        $this->assign('renzheng', $renzheng);
        $this->assign('userid', $userid);
        session('code_switch', 1);
        $this->display();
    }

    //MD5密钥页面
    public function Md5Secretkey()
    {
        $userid = I('get.userid');
        $secretkey = SecretkeyModel::userKeyFind($userid);
        $this->assign('userid', $userid);
        $this->assign('id', $secretkey['id']);
        $this->assign('md5key', $secretkey['md5str']);
        $code_type = $this->getCodeType();
        $this->assign('code_type', $code_type);
        $this->display();
    }

    //重新生成MD5密钥
    public function updateMd5key()
    {
        $str = SecretkeyModel::createdMd5key();
        $this->ajaxReturn(['status' => 'success', 'code' => $str]);
    }

    //2019-4-1 修改用户MD5密钥
    public function editMd5key()
    {
        $userid = I('userid');
        $user_name = UserModel::getUserName($userid);
        $msg = '为用户[' . $user_name . ']修改md5密钥:';
        $verfiy_code = I('post.verfiy_code', '', 'trim');
        $code_type = I('post.code_type', 0, 'intval');
        $this->checkVerifyCode($verfiy_code, $code_type, $msg);
        $return = AddSave('secretkey', 'save', '修改md5密钥');
        $this->addAdminOperate($msg . $return['msg']);
        $this->ajaxReturn($return, "json");
    }

    //绑定域名页面
    public function BindingDomain()
    {
        $userid = I('get.userid');
        $domains = DomainModel::getDomains($userid);
        $this->assign('domains', $domains);
        $this->assign('userid', $userid);
        $this->display();
    }

    //域名列表
    public function loadDomain()
    {
        $userid = I('get.userid');
        $where = "userid=" . $userid;
        $count = D('domain')->where($where)->count();
        $datalist = D('domain')->where($where)->page(I("post.page", "1"), I("post.limit", "10"))->order('id DESC')->select();
        $ReturnArr = [
            'code' => 0,
            'msg' => '数据加载成功', //响应结果
            'count' => $count, //总页数
            'data' => $datalist
        ];
        $this->ajaxReturn($ReturnArr, 'JSON');
    }

    //添加域名
    public function addDomain()
    {
        $user_name = UserModel::getUserName(I('post.userid'));
        $msg = '为用户[' . $user_name . ']绑定域名[' . I('post.domain') . ']:';
        $return = AddSave('domain', 'add', '绑定域名');
        $this->addAdminOperate($msg . $return['msg']);
        $this->ajaxReturn($return, "json");
    }

    //删除域名
    public function delDomain()
    {
        $id = I('post.id');
        $user_name = UserModel::getUserName(I('get.userid'));
        $domain = DomainModel::getDomain($id);
        $msg = '删除用户[' . $user_name . ']的绑定域名[' . $domain . ']';
        $res = DomainModel::delDomain($id);
        if ($res) {
            $this->addAdminOperate($msg . '删除成功');
            $this->ajaxReturn(['status' => 'ok', 'msg' => '删除成功']);
        }
        $this->addAdminOperate($msg . '删除失败');
        $this->ajaxReturn(['status' => 'no', 'msg' => '删除失败']);
    }






    //2019-4-1 任梦龙：将密钥路径表合并在密钥内容表中
    //用户rsa密钥页面
    public function userSecretkey()
    {
        $user_id = I('get.userid');  //用户id
        $file_name = I('get.file_name');  //密钥类型名称
        $alert_str = I('get.alert_str');  //密钥中文名称
        $find = SecretkeyModel::userKeyFind($user_id);
        $this->assign("keystr", $find[$file_name]);  //密钥内容
        $this->assign("id", $find['id']);  //用户密钥表id
        $this->assign('user_id', $user_id);  //用户id
        $this->assign('file_name', $file_name);  //密钥类型名称 ==字段名
        $this->assign('alert_str', $alert_str);  //密钥中文名
        $code_type = $this->getCodeType();
        $this->assign('code_type', $code_type);
        $this->display();
    }

    //修改用户密钥文件(直接修改)
    public function editRsakey()
    {
        $verfiy_code = I('post.verfiy_code', '', 'trim');
        $code_type = I('post.code_type', 0, 'intval');
        $zh_name = I('post.zh_name', '', 'trim');
        $user_id = I('post.userid');
        $user_name = UserModel::getUserName($user_id);
        $msg = '为用户[' . $user_name . ']修改' . $zh_name . '文件(未上传文件):';
        $this->checkVerifyCode($verfiy_code, $code_type, $msg);   //验证密码
        $res = AddSave('secretkey', 'save', $zh_name . '修改');
        $data = [
            'admin_id' => session('admin_info.id'),
            'user_id' => I('post.userid'),
            'date_time' => date('Y-m-d H:i:s'),
            'msg' => $msg . $res['msg']
        ];
        SecretkeyrecordModel::addSecretRecord($data);   //2019-3-12 任梦龙：单独添加密钥修改记录,这个看需要不
        $this->addAdminOperate($msg . $res['msg']);
        $this->ajaxReturn($res, 'json');
    }


    //2019-4-1 任梦龙：优化代码,同时将密钥路径合并在密钥内容表中
    //修改用户密钥文件(上传文件)
    public function uploadUserKey()
    {
        $id = I('post.id');  //当前密钥表id
        $code_type = I('post.code_type');  //验证码类型
        $upload_code = I('post.upload_code');  //验证码
        $user_id = I('post.user_id');  //用户id
        $zh_name = I('post.zh_name');  //密钥中文名称
        $file_name = I('post.file_name');  //密钥类型名称
        $user_path_name = substr($file_name, 0, -4) . '_path';  //用户密钥路径表的字段名称
        $user_name = UserModel::getUserName($user_id);
        $msg = '为用户[' . $user_name . ']上传' . $zh_name . '文件:';
        //判断验证码
        $this->checkVerifyCode($upload_code, $code_type, $msg);
        //在上传文件前强制检查是否登录,如果在点击上传文件时，系统已经下线了，那么点击后如何直接退出？？？
//        if (!session('admin_info')) {
//
//        }
        $record_data = [
            'admin_id' => session('admin_info.id'),
            'user_id' => $user_id,
            'date_time' => date('Y-m-d H:i:s')
        ];
        /*************************************************************/
        //判断上传目录是否存在，否则创建
        $root_path = C('USER_KEY_STR_PATH') . 'user-' . $user_id;  //上传的根目录
        if (!file_exists($root_path)) {
            mkdir($root_path, 0777, true);   //true：表示创建多级目录 ;0777表示文件的最高权限
        }
        $new_file_name = hash('sha1', $file_name . '_' . session('user_info.id'));
        $upload = new \Think\Upload();// 实例化上传类
        $upload->maxSize = 3 * 1024 * 1024;// 设置附件上传大小 3M
        $upload->exts = array('txt', 'pem', 'pfx', 'cer');// 设置附件上传类型
        $upload->rootPath = C('USER_KEY_STR_PATH'); // 设置附件上传根目录
        $upload->subName = 'user-' . $user_id;   //子目录创建方式，以账号id命名
        $upload->replace = true;   //存在同名文件覆盖
        $upload->saveName = $new_file_name;   //文件名以密钥类型名称为准
        // 上传文件
        $info = $upload->uploadOne($_FILES['file']);
        if (!$info) {
            // 上传错误提示错误信息
            $record_data['msg'] = $msg . $upload->getError();
            SecretkeyrecordModel::addSecretRecord($record_data);
            $this->addAdminOperate($msg . $upload->getError());
            $this->ajaxReturn(['status' => 'no', 'msg' => $upload->getError()]);
        } else {
            // 上传成功：获取文件内容，写入密钥表中，将密钥路径写入路径表中，同时添加操作记录与密钥的操作记录（密钥的操作记录是否可以不需要???）
            $file_path = C('USER_KEY_STR_PATH') . $info['savepath'] . $info['savename'];
            $file_contents = file_get_contents($file_path);
            $public_begin = '-----BEGIN PUBLIC KEY-----';
            $public_end = '-----END PUBLIC KEY-----';
            $privte_begin = '-----BEGIN RSA PRIVATE KEY-----';
            $private_end = '-----END RSA PRIVATE KEY-----';
            $private_begin_rsa = '-----BEGIN PRIVATE KEY-----';
            $private_end_rsa = '-----END PRIVATE KEY-----';
            $contents = str_replace(array("\r\n", "\r", "\n", $public_begin, $public_end, $privte_begin, $private_end, $private_begin_rsa, $private_end_rsa), '', $file_contents);
            $data = [
                $file_name => $contents,
                $user_path_name => $file_path,
                'upload_time' => date('Y-m-d H:i:s')
            ];
            $res = SecretkeyModel::editUserSecret($id, $data);
            if ($res) {
                $record_data['msg'] = $msg . '上传成功';
                SecretkeyrecordModel::addSecretRecord($record_data);
                $this->addAdminOperate($msg . '上传成功');
                $this->ajaxReturn(['status' => 'ok', 'msg' => '上传成功', 'file_contents' => $contents]);
            } else {
                $record_data['msg'] = $msg . '上传失败,已经产生过文件记录';
                SecretkeyrecordModel::addSecretRecord($record_data);
                //如果修改失败,则将文件删除
                unlink($file_path);
                $this->addAdminOperate($msg . '上传失败,已经产生过文件记录');
                $this->ajaxReturn(['status' => 'no', 'msg' => '上传失败']);
            }
        }
        /*************************************************************/
    }

    //2019-4-8 任梦龙：验证公钥()
    public function loadX509Cert($path)
    {
        try {
            $file = file_get_contents($path);
            if (!$file) {
                throw new \Exception('loadx509Cert::file_get_contents ERROR');
            }

            $cert = chunk_split(base64_encode($file), 64, "\n");
            $cert = "-----BEGIN CERTIFICATE-----\n" . $cert . "-----END CERTIFICATE-----\n";

            $res = openssl_pkey_get_public($cert);

            $detail = openssl_pkey_get_details($res);
            openssl_free_key($res);

            if (!$detail) {
                throw new \Exception('loadX509Cert::openssl_pkey_get_details ERROR');
            }

            return $detail['key'];
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * 2019-1-25 任梦龙：为每个主用户添加ip组
     */
    public function userIpList()
    {
        $userid = I('userid');
        $this->assign('userid', $userid);
        $this->display();
    }

    /**
     * 加载主用户ip列表
     */
    public function loadUserIpList()
    {
        $user_id = I('get.user_id');
        $where = [
            'admin_id' => 0,
            'user_id' => $user_id,
            'child_id' => 0
        ];
        $this->ajaxReturn(PageDataLoad('ipaccesslist', $where));
    }

    /**
     * 添加主用户的ip白名单
     */
    public function addUserIp()
    {
        $this->ajaxReturn(AddSave('ipaccesslist', 'add', '添加ip'));
    }

    /**
     * 删除主用户ip
     */
    public function delUserIp()
    {
        $id = I('post.id');
        if (!$id) {
            $this->ajaxReturn(['status' => 'no', 'msg' => '请勿非法操作']);
        }
        $res = IpaccesslistModel::delIp($id);
        if ($res) {
            $this->ajaxReturn(['status' => 'ok', 'msg' => '删除成功']);
        }
        $this->ajaxReturn(['status' => 'no', 'msg' => '删除失败']);
    }

    /**
     * 2019-2-22 任梦龙：管理员对哪一个用户的密钥修改记录列表
     */
    //密钥记录页面
    public function secretRecord()
    {
        $userid = I('userid');
        $this->assign('userid', $userid);
        $this->display();
    }

    /**
     * 2019-2-22 任梦龙：加载列表
     */
    //2019-4-16 rml：查询出对这个用户的密钥进行的修改操作记录
    public function loadSecretRecord()
    {
        $user_id = I('get.user_id');
        $count = D('secretkeyrecord')->where(['user_id' => $user_id])->count();
        $return_data = D('secretkeyrecord')->where(['user_id' => $user_id])->page(I('post.page', 1), I('post.limit', 10))->select();
        foreach ($return_data as $key => $val) {
            if ($val['admin_id']) {
                $return_data[$key]['type'] = '管理员';
                $return_data[$key]['user_name'] = $this->getNameById('adminuser', $val['admin_id'], 'user_name');
            } else if ($val['child_id']) {
                $return_data[$key]['type'] = '子账号';
                $return_data[$key]['user_name'] = $this->getNameById('childuser', $val['child_id'], 'child_name');
            } else {
                $return_data[$key]['type'] = '用户';
                $return_data[$key]['user_name'] = $this->getNameById('user', $val['user_id'], 'username');
            }
        }
//        $where = 'a.admin_id=' . session('admin_info.id') . ' AND a.user_id=' . $user_id;
//        $count = M('secretkeyrecord')->alias('a')->join('__ADMINUSER__ b ON a.admin_id = b.id')->where($where)->count();
//        $return_data = M('secretkeyrecord')->alias('a')->join('__ADMINUSER__ b ON a.admin_id = b.id')
//            ->where($where)->page(I('post.page', 1), I('post.limit', 10))
//            ->field('a.*,b.user_name')->order('a.id DESC')->select();
        $ReturnArr = [
            'code' => 0,
            'msg' => '数据加载成功',
            'count' => $count,
            'data' => $return_data
        ];
        $this->ajaxReturn($ReturnArr);
    }

    public function getNameById($table_name, $id, $field)
    {
        return M($table_name)->where(['id' => $id])->getField($field);
    }

}