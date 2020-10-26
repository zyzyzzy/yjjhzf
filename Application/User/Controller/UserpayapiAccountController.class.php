<?php
/**
 * 用户自己开通通道账号控制器
 */

namespace User\Controller;

use User\Model\SecretkeyModel;
use User\Model\PayapiModel;
use User\Model\PayapiaccountModel;
use User\Model\UserModel;
use User\Model\PayapiaccountkeystrModel;
use User\Model\SetuserpayapiModel;
use User\Model\PayapiaccountkeyModel;

class UserpayapiAccountController extends UserCommonController
{
    public function __construct()
    {
        parent::__construct();
    }

    //2019-4-16 rml：判断是否开通自助账号功能，判断是否有分配通道
    public function getSelfPayApi()
    {
        $self_payapi = UserModel::getSelfPayapi(session('user_info.id'));
        if ($self_payapi == 2) {
            $str = <<<MARK
<div class="layui-form-item" style="text-align: center;color: #C0C0C0;font-size: 36px;margin-top: 8%;">
    <span>未开通此功能,暂不能查看,如需要请联系管理员</span>
</div>
MARK;
            exit($str);
        }
        $payapi_str = UserModel::getPayapiid(session('user_info.id'));
        //如果有指定通道,则允许用户添加账号,否则不允许
        if (!$payapi_str) {
            $str = <<<MARK
<div class="layui-form-item" style="text-align: center;color: #C0C0C0;font-size: 36px;margin-top: 8%;">
    <span>未分配通道,暂不能查看,如需要请联系管理员</span>
</div>
MARK;
            exit($str);
        }
    }

    //通过id获取名称
    public function getNameById($table, $where, $field)
    {
        return M($table)->where('id=' . $where)->getField($field);
    }

    //用户自助通道账号列表
    public function userAccount()
    {
        $this->getSelfPayApi();
        //获取管理员给该用户设置允许的通道列表
        $payapi_str = UserModel::getPayapiid(session('user_info.id'));
        $payapi_list = [];
        $payapi_arr = explode(',', $payapi_str);
        foreach ($payapi_arr as $key => $val) {
            $payapi_list[] = PayapiModel::findPayapiAccount($val);
        }
        $this->assign('payapi_list', $payapi_list);
        $this->display();
    }

    //加载用户通道账号列表
    public function loadUserpayapiAccount()
    {
        $where = [];
        $where[0] = 'user_id=' . session('user_info.id');
        $where[1] = 'del=0';
        $i = 2;
        $bieming = I('post.bieming', '', 'trim');
        if ($bieming) {
            $where[$i] = 'bieming="' . $bieming . '"';
            $i++;
        }
        $user_payapiid = I('post.user_payapiid', '');
        if ($user_payapiid) {
            $where[$i] = 'user_payapiid=' . $user_payapiid;
            $i++;
        }
        $status = I('post.status', '');
        if ($status != '') {
            $where[$i] = 'status=' . $status;
            $i++;
        }
        $count = D('payapiaccount')->where($where)->count();
        $datalist = D('payapiaccount')->where($where)->page(I("post.page", "1"), I("post.limit", "10"))->order('id DESC')->select();
        foreach ($datalist as $key => $val) {
            $datalist[$key]['payapi_name'] = PayapiModel::getPayapiName($val['user_payapiid']);
        }
        $ReturnArr = [
            'code' => 0,
            'msg' => '数据加载成功', //响应结果
            'count' => $count, //总页数
            'data' => $datalist
        ];
        $this->ajaxReturn($ReturnArr);
    }

    //添加用户自助通道账号页面
    public function addUserPayapiAccount()
    {
        $payapi_str = UserModel::getPayapiid(session('user_info.id'));
        $payapi_list = [];
        $payapi_arr = explode(',', $payapi_str);
        foreach ($payapi_arr as $key => $val) {
            $payapi_list[] = PayapiModel::findPayapiAccount($val);
        }
        $this->assign('payapi_list', $payapi_list);
        $this->assign('user_id', session('user_info.id'));
        $this->display();

    }

    //确认添加
    public function userPayapiAccountAdd()
    {
        $tablename = D('payapiaccount');
        if(!$tablename->create()){
            $this->ajaxReturn(['status' => 'no', 'msg' => $tablename->getError()]);
        }
        $msg = '添加自助通道账号[' . I('post.bieming', '', 'trim') . ']';
        //通过通道id查找商家id
        $shangjia_id = PayapiModel::getShangjiaId(I('post.user_payapiid'));
        $tablename->payapishangjiaid = $shangjia_id;
        $res = $tablename->add();
        if ($res) {
            //添加密钥记录
            $res_keystr = M('payapiaccountkeystr')->add(['payapi_account_id' => $res]);
            if ($res_keystr) {
                $this->addUserOperate($msg . '添加成功');
                $this->ajaxReturn(['status' => 'ok', 'msg' => '添加成功']);
            } else {
                $tablename->where(['id' => $res])->delete();
                $this->addUserOperate($msg . '添加失败,此时产生了账号记录');
                $this->ajaxReturn(['status' => 'no', 'msg' => '添加失败']);
            }
        }
        $this->addUserOperate($msg . '添加失败');
        $this->ajaxReturn(['status' => 'no', 'msg' => '添加失败']);
    }

    //修改页面
    public function editUserPayapiAccount()
    {
        $find = PayapiaccountModel::getuserPayapiaccount(I('get.id'));
        $this->assign('find', $find);
        $this->display();
    }

    //确认修改
    public function userPayapiAccountEdit()
    {
        $return = AddSave('payapiaccount', 'save', '修改账号');
        $this->addUserOperate('修改自助通道账号[' . I('post.bieming', '', 'trim') . ']:' . $return['msg']);
        $this->ajaxReturn($return);
    }

    //修改费率页面
    public function userPayapiaccountFeilv()
    {
        $account = PayapiaccountModel::getuserPayapiaccount(I('get.id'));
        $this->assign('account', $account);
        $this->display();
    }

    //确认修改费率
    public function editUserPayapiaccountFeilv()
    {
        $account_name = PayapiaccountModel::getBieming(I('post.id'));
        $msg = "修改自助账号[" . $account_name . "]费率:";
        $return = AddSave('payapiaccount', 'save', "费率修改");
        $this->addUserOperate($msg . $return['msg']);
        $this->ajaxReturn($return, "json");
    }

    //密钥页面
    public function userSecretkey()
    {
        session('switch_code', 1);
        $this->assign('id', I('get.id'));
        $this->display();
    }

    /*******************************************/
    //2019-4-2 任梦龙：账号设置页面
    //通过账号查询出商家id,根据商家id去查找配置文件,如果有配置文件,则替换，没有则默认
    public function setUserAccount()
    {
        $code_type = $this->getCodeType();
        $this->assign('code_type', $code_type);
        $find = PayapiaccountModel::getuserPayapiaccount(I('get.id'));
        $shangjia_name = M('payapishangjia')->where(['id' => $find['payapishangjiaid']])->getField('shangjianame');
        $finds = getShangjiaConfig($shangjia_name, $find);
        $this->assign('find', $finds);
        $this->display();
    }

    //修改账号设置
    public function editAccountSet()
    {
        $verfiy_code = I('post.verfiy_code', '', 'trim');  //修改数据时的验证码
        $code_type = I('post.code_type', 0, 'intval');  //验证码类型
        $id = I('post.id');
        $bieming = PayapiaccountModel::getBieming($id, 'bieming');
        $msg = '修改自助账号[' . $bieming . ']的账号设置:';
        $this->checkVerifyCode($verfiy_code, $code_type, $msg);   //验证密码
        $return = AddSave('payapiaccount', 'save', "修改账号设置");
        $this->addUserOperate($msg . $return['msg']);
        $this->ajaxReturn($return, "json");
    }

    //地址设置页面
    public function setUserAddress()
    {
        $code_type = $this->getCodeType();
        $this->assign('code_type', $code_type);
        $find = PayapiaccountModel::getuserPayapiaccount(I('get.id'));
        $this->assign('find', $find);
        $this->display();
    }

    //修改地址设置
    public function editAddressSet()
    {
        $verfiy_code = I('post.verfiy_code', '', 'trim');  //修改数据时的验证码
        $code_type = I('post.code_type', 0, 'intval');  //验证码类型
        $id = I('post.id');
        $bieming = PayapiaccountModel::getBieming($id, 'bieming');
        $msg = '修改自助账号[' . $bieming . ']的账号设置:';
        $this->checkVerifyCode($verfiy_code, $code_type, $msg);   //验证密码
        $return = AddSave('payapiaccount', 'save', "修改账号设置");
        $this->addUserOperate($msg . $return['msg']);
        $this->ajaxReturn($return, "json");
    }
    /*******************************************/

    //MD5密钥页面
    public function accountMd5Key()
    {
        $code_type = $this->getCodeType();
        $this->assign('code_type', $code_type);
        $find = PayapiaccountkeystrModel::getkeyInfo(['payapi_account_id' => I('get.id')]);
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
    public function editUserAccountMd5key()
    {
        $bieming = PayapiaccountModel::getBieming(I('post.id'));
        $msg = '修改自助账号[' . $bieming . ']的MD5密钥:';
        $verfiy_code = I('post.verfiy_code', '', 'trim');
        $code_type = I('post.code_type', 0, 'intval');
        $this->checkVerifyCode($verfiy_code, $code_type, $msg);   //验证密码
        $return = AddSave('payapiaccountkeystr', 'save', '修改MD5密钥');
        $this->addUserOperate($msg . $return['msg']);
        $this->ajaxReturn($return);
    }

    //rsa密钥页面
    public function showUserkeystr()
    {
        $account_id = I("get.id");  //账号表id
        $filename = I("get.filename");
        $alertstr = I("get.alertstr");
        $keystr = PayapiaccountkeystrModel::getkeyInfo(['del' => 0, 'payapi_account_id' => $account_id]);
        $this->assign("keystr", $keystr[$filename]);  //密钥对应的内容
        $this->assign("key_id", $keystr['id']);  //账号密钥表id
        $this->assign("filename", $filename);
        $this->assign("alertstr", $alertstr);
        $this->assign("account_id", $account_id);
        //判断当前操作者的二次验证与管理密码的开启状态
        $code_type = $this->getCodeType();
        $this->assign('code_type', $code_type);
        $this->display();
    }

    //修改用户自助通道账号密钥(直接修改)
    public function userkeystrEdit()
    {
        $zh_name = I('post.zh_name', '', 'trim');
        $account_id = I('post.account_id');
        $bieming = PayapiaccountModel::getBieming($account_id);
        $msg = '修改用户自助通道账号[' . $bieming . ']的' . $zh_name . ':';
        $verfiy_code = I('post.verfiy_code', '', 'trim');
        $code_type = I('post.code_type', 0, 'intval');
        $this->checkVerifyCode($verfiy_code, $code_type, $msg);   //验证密码
        $return = AddSave('payapiaccountkeystr', 'save', $zh_name . "修改");
        $this->addUserOperate($msg . $return['msg']);
        $this->ajaxReturn($return, "json");
    }

    //修改用户自助通道账号密钥(上传文件时)
    //2019-4-3 任梦龙：修改
    public function uploadFile()
    {
        $id = I("post.id");  //当前账户密钥表id
        $account_id = I('post.account_id'); //账号表id
        $file_name = I('post.file_name');  //密钥字段名称
        $zh_name = I('post.zh_name');  //密钥字段中文名称
        $bieming = PayapiaccountModel::getBieming($account_id);
        $msg = '上传自助通道账号[' . $bieming . ']的' . $zh_name . ':';
        $code_type = I('post.code_type');  //验证码类型
        $upload_code = I('post.upload_code');  //验证码
        $this->checkVerifyCode($upload_code, $code_type, $msg);
        $upload = new \Think\Upload();// 实例化上传类
        $upload->maxSize = 3 * 1024 * 1024;// 设置附件上传大小 3M
        $upload->exts = array('pem', 'txt');// 设置附件上传类型
        $upload->rootPath = C('KEY_STR_PATH'); // 设置附件上传根目录
        $upload->saveName = $file_name;   //文件名以密钥类型名称为准
        $upload->replace = true;   //存在同名文件覆盖
        $upload->subName = 'account-' . $account_id;   //子目录创建方式，以账号id命名
        /*************************************************************/
        //判断上传目录是否存在，否则创建
        $root_path = C('KEY_STR_PATH') . 'account-' . $account_id;  //上传的根目录
        if (!file_exists($root_path)) {
            mkdir($root_path, 0777, true);   //true：表示创建多级目录 ;0777表示文件的最高权限
        }
        // 上传单个文件
        $info = $upload->uploadOne($_FILES['file']);
        if (!$info) {
            $this->addUserOperate($msg . $upload->getError());
            $this->ajaxReturn(['status' => 'no', 'msg' => $upload->getError()]);
        } else {
            // 上传成功 拼接上传文件的路径，存入数据库，并且读取文件内容，也存入数据库
            $file_path = C('KEY_STR_PATH') . $info['savepath'] . $info['savename'];
            //读取密钥文件内容，去除两端的字符串,空格，换行，并将内容保存到账号表中对应的字段
            $file_contents = file_get_contents($file_path);
            $public_begin = '-----BEGIN PUBLIC KEY-----';
            $public_end = '-----END PUBLIC KEY-----';
            $privte_begin = '-----BEGIN RSA PRIVATE KEY-----';
            $private_end = '-----END RSA PRIVATE KEY-----';
            $contents = str_replace(array("\r\n", "\r", "\n", $public_begin, $public_end, $privte_begin, $private_end), '', $file_contents);
            //密钥路径字段名称
            $file_name_path = $file_name . '_path';
            $data = [
                $file_name => $contents,
                $file_name_path => $file_path,
                'upload_time' => date('Y-m-d H:i:s'),
            ];
            $res = PayapiaccountkeystrModel::editUsersecret($id, $data);
            if ($res) {
                $this->addUserOperate($msg . '上传成功');
                $this->ajaxReturn([
                    'status' => 'ok',
                    'msg' => '上传成功',
                    'file_contents' => $contents
                ]);
            } else {
                unlink($file_path);
                $this->addUserOperate($msg . '上传失败,产生过上传文件');
                $this->ajaxReturn(['status' => 'no', 'msg' => '上传失败'
                ]);
            }

        }
    }

    //删除用户自助通道账号
    public function userPayapiAccountDel()
    {
        $id = I('post.id');
        $bieming = PayapiaccountModel::getBieming($id);
        $msg = '删除自助通道账号[' . $bieming . ']:';
        $res = PayapiaccountModel::delUserPayapiAccount($id);
        if ($res) {
            $this->addUserOperate($msg . '删除成功');
            $this->ajaxReturn(['status' => 'ok', 'msg' => '删除成功']);
        }
        $this->addUserOperate($msg . '删除失败');
        $this->ajaxReturn(['status' => 'no', 'msg' => '删除失败']);

    }

    //修改用户自助通道账号状态
    public function updateStatus()
    {
        $id = I("post.id", "");
        $status = I("post.status", "");
        $bieming = PayapiaccountModel::getBieming($id);
        $msg = "修改用户自助通道账号[" . $bieming . "]状态:";
        if ($status == 1) {
            $stat = "修改为启用";
        } else {
            $stat = "修改为禁用";
        }
        $r = PayapiaccountModel::changeStatus($id, 'status', $status);
        if ($r) {
            $this->addUserOperate($msg . $stat . ',修改成功');
            $this->ajaxReturn(['status' => 'ok', 'msg' => '修改成功']);
        } else {
            $this->addUserOperate($msg . $stat . ',修改失败');
            $this->ajaxReturn(['status' => 'no', 'msg' => '修改失败']);
        }
    }

    //2019-3-29 任梦龙：修改自助通道账号的默认状态
    public function updateDefault()
    {
        $id = I("post.id", "");
        $default_status = I("post.default_status", "");
        $bieming = PayapiaccountModel::getBieming($id);
        $msg = "修改用户自助通道账号[" . $bieming . "]状态:";
        if ($default_status == 1) {
            $stat = "修改为默认";
        } else {
            $stat = "修改为普通";
        }
        $r = PayapiaccountModel::changeStatus($id, 'default_status', $default_status);
        if ($r) {
            //2019-4-28 rml:修改逻辑
            //如果修改为默认,那么这个用户在这个通道上添加的其它账号需要修改为普通
            if ($default_status == 1) {
                $user_payapiid = PayapiaccountModel::getUserPayapiid($id);
                $where = [
                    'id' => ['neq', $id],
                    'user_id' => session('user_info.id'),
                    'user_payapiid' => $user_payapiid,
                    'default_status' => 1,
                    'del' => 0,
                ];

                $other_default = PayapiaccountModel::findUserAccountid($where);
                if ($other_default) {
                    PayapiaccountModel::changeStatus($other_default, 'default_status', 0);
                }
            }
            $this->addUserOperate($msg . $stat . ',修改成功');
            $this->ajaxReturn(['status' => 'ok', 'msg' => '修改成功']);
        } else {
            $this->addUserOperate($msg . $stat . ',修改失败');
            $this->ajaxReturn(['status' => 'no', 'msg' => '修改失败']);
        }

    }

    //2019-3-29 任梦龙：设置单笔金额（单笔最大和单笔最小）
    public function setQuotaMoney()
    {
        $find = PayapiaccountModel::getuserPayapiaccount(I('get.id'));
        $this->assign('find', $find);
        $this->display();
    }

    //2019-3-29 任梦龙：确认修改单笔限额
    public function conformQuotaMoney()
    {
        $id = I('post.id');
        $max_money = I('post.max_money', '', 'trim');
        $min_money = I('post.min_money', '', 'trim');
        $id = I('post.id');
        $account_name = PayapiaccountModel::getBieming($id);
        $msg = "修改通道账号[" . $account_name . "]的单笔限额:";
        if ($max_money == '' || $min_money == '') {
            $this->ajaxReturn(['status' => 'no', 'msg' => '数据不能为空']);
        }

        if (!is_numeric($min_money) || !is_numeric($max_money)) {
            $this->ajaxReturn(['status' => 'no', 'msg' => '金额需为数字']);
        }
        if ($min_money < 0) {
            $this->ajaxReturn(['status' => 'no', 'msg' => '单笔最小金额不得小于0']);
        }
        if ($min_money < 0) {
            $this->ajaxReturn(['status' => 'no', 'msg' => '单笔最小金额不得小于0']);
        }
        if ($min_money > $max_money) {
            $this->ajaxReturn(['status' => 'no', 'msg' => '最大金额不得小于最小金额']);
        }
        $res = PayapiaccountModel::editUserAccount($id, ['min_money' => $min_money, 'max_money' => $max_money]);
        if ($res) {
            $this->addUserOperate($msg . '修改成功');
            $this->ajaxReturn(['status' => 'ok', 'msg' => '修改成功']);
        }
        $this->addUserOperate($msg . '修改失败');
        $this->ajaxReturn(['status' => 'no', 'msg' => '修改失败']);

    }

    /********************************************/
    //2019-4-3 任梦龙：通道设置总页面
    public function userAccountset()
    {

        $this->getSelfPayApi();
        $this->display();
    }

    //通道设置页面：如果没有给用户分配通道,则不显示页面
    public function accountSet()
    {
        $payapi_str = UserModel::getPayapiid(session('user_info.id'));
        $payapi_list = [];
        $payapi_arr = explode(',', $payapi_str);
        foreach ($payapi_arr as $key => $val) {
            $payapi_list[] = PayapiModel::findPayapiAccount($val);
        }
        $this->assign('payapi_list', $payapi_list);
        //得到指定的被用户指定的通道
        $set_payapi = SetuserpayapiModel::getUserPayapi(session('user_info.id'));
        $set_arr = $set_payapi ? $set_payapi : [];
        $this->assign('set_arr', $set_arr);
        $code_type = $this->getCodeType();
        $this->assign('code_type', $code_type);
        $this->display();


    }

    //用户强制设置该通道为交易通道,一旦该通道内没有账号，则交易报错
    public function editUserAccountset()
    {
        $payapi_arr = I('post.payapi_id', '');
        $payapi_arr = $payapi_arr ? $payapi_arr : '';
        //首先查询该用户有没有指定
        $set_payapi = SetuserpayapiModel::getUserPayapi(session('user_info.id'));
        $set_payapi = $set_payapi ? $set_payapi : '';
        if ($set_payapi == $payapi_arr) {
            $this->ajaxReturn(['status' => 'no', 'msg' => '没有任何修改,请确认']);
        }
        $msg = '将自助通道强制设置为指定通道：';
        $verfiy_code = I('post.verfiy_code', '', 'trim');
        $code_type = I('post.code_type', 0, 'intval');
        $this->checkVerifyCode($verfiy_code, $code_type, $msg);
        //如果有修改,先将原有数据删除
        if ($set_payapi) {
            SetuserpayapiModel::delUserpayapi(session('user_info.id'));
        }
        if ($payapi_arr) {
            $str = '重新指定了通道,';
            foreach ($payapi_arr as $val) {
                SetuserpayapiModel::addUserpayapi([
                    'user_id' => session('user_info.id'),
                    'user_payapi' => $val
                ]);
            }
        } else {
            $str = '清空了指定通道,';
        }
        $this->addUserOperate($msg . $str . '修改成功');
        $this->ajaxReturn(['status' => 'ok', 'msg' => '修改成功']);
    }
    /********************************************/

    //2019-4-9 任梦龙：将交易记录移到用户自助账号的交易记录中，避免菜单url重复了
    /**********************************************/
    //用户自助账号交易记录表
    public function userOrderList()
    {
        //搜索
        $statustype = C('STATUSTYPE');
        $this->assign('statustype', $statustype);

        $shangjias = M('payapishangjia')->select();
        $this->assign('shangjias', $shangjias);

        $payapis = M('payapi')->field('id,zh_payname')->select();
        $this->assign('payapis', $payapis);

        $accounts = M('payapiaccount')->field('id,bieming')->select();
        $this->assign('accounts', $accounts);


        //提交时间
        //查询所有提交时间的类型
        $all_open_type = C('ORDEROPENTYPE');
        $this->assign('all_open_type', $all_open_type);
        //查询当前用户的提交时间
        $user_id = session('user_info.id');
        $user_open_type = M('orderopentype')->where('user_id=' . $user_id . ' and user_type="user"')->getField('order_open_type');
        $this->assign('user_open_type', $user_open_type);
        $this->assign('user_id', $user_id);
        //获取提交日期
        if ($user_open_type) {
            $open_start = date("Y-m-d", strtotime('-' . ($user_open_type - 1) . " day")) . " 00:00:00";
            $open_end = date("Y-m-d") . " 23:59:59";
        }

        $this->assign('open_start', $open_start);
        $this->assign('open_end', $open_end);

        $this->display();
    }

    //加载用户自身的自助账号交易记录数据
    public function loadUserOrderList()
    {
        $where = [];
        $where[0] = "users_id=" . session('user_info.id');
        $i = 1;
        $payapiid = I("post.payapiid", "");
        if ($payapiid <> "") {
            $where[$i] = "payapiid = " . $payapiid;
            $i++;
        }

        $payapiaccountid = I("post.payapiaccountid", "");
        if ($payapiaccountid <> "") {
            $where[$i] = "payapiaccountid = " . $payapiaccountid;
            $i++;
        }

        $userip = I("post.userip", "", 'trim');
        if ($userip <> "") {
            $where[$i] = "(userip like '" . $userip . "%')";
            $i++;
        }
        $userordernumber = I("post.userordernumber", "", 'trim');
        if ($userordernumber <> "") {
            $where[$i] = "(userordernumber like '" . $userordernumber . "%')";
            $i++;
        }
        $status = I("post.status", "");
        if ($status <> "") {
            $where[$i] = "status = " . $status;
            $i++;
        }
        $type = I("post.ordertype", "");
        if ($type <> "") {
            $where[$i] = "type = " . $type;
            $i++;
        }
        $start = I("post.start", "");
        if ($start <> "") {
            $where[$i] = "DATEDIFF('" . $start . "',datetime) <= 0";
            $i++;
        }
        $end = I("post.end", "");
        if ($end <> "") {
            $where[$i] = "DATEDIFF('" . $end . "',datetime) >= 0";
            $i++;
        }
        $success_start = I("post.success_start", "");
        if ($success_start <> "") {
            $where[$i] = "DATEDIFF('" . $success_start . "',successtime) <= 0";
            $i++;
        }
        $success_end = I("post.success_end", "");
        if ($success_end <> "") {
            $where[$i] = "DATEDIFF('" . $success_end . "',successtime) >= 0";
            $i++;
        }
        $money_start = I("post.money_start", "");
        if ($money_start <> "") {
            $where[$i] = "ordermoney >= " . $money_start;
            $i++;
        }
        $money_end = I("post.money_end", "");
        if ($money_end <> "") {
            $where[$i] = "ordermoney <= " . $money_end;
            $i++;
        }

        //统计
        $where1 = $where;
        $where1['status'] = ['gt', 0];
        $where1['type'] = 0;
        $sum_ordermoney = M('orderinfo')->where($where1)->sum('true_ordermoney');//订单总金额
        $sum_costmoney = M('orderinfo')->where($where1)->sum('moneycost');//成本金额
        $sum_trademoney = M('orderinfo')->where($where1)->sum('moneytrade');//手续费
        $sum_money = M('orderinfo')->where($where1)->sum('money');//到账金额
        $sum_freezemoney = M('orderinfo')->where($where1)->sum('freezemoney');//冻结金额
        $count_success = M('orderinfo')->where($where1)->count();//成功笔数
        $where2 = $where;
        $where2['status'] = 0;
        $where2['type'] = 0;
        $count_fail = M('orderinfo')->where($where2)->count();//失败笔数
        $where3 = $where;
        $where3['type'] = 1;
        $count_test = M('orderinfo')->where($where3)->count();//测试笔数
        $success_rate = sprintf("%.2f", ($count_success / ($count_success + $count_fail)) * 100) . "%";
        //总页数
        $count = M('orderinfo')->where($where)->count();
        $page = I('post.page');
        $limit = I('post.limit');
        $datalist = M('orderinfo')->where($where)->page($page, $limit)->order('orderid DESC')->select();

        //查询数据
        $statustype = C('STATUSTYPE');
        foreach ($datalist as $k => $v) {
            $datalist[$k]['payapiname'] = $this->getNameById('payapi', $v['payapiid'], 'zh_payname');
            $datalist[$k]['accountname'] = $this->getNameById('payapiaccount', $v['payapiaccountid'], 'bieming');
//            $datalist[$k]['username'] = $this->getNameById('user', $v['userid'], 'username');
//            $datalist[$k]['shangjianame'] = $this->getNameById('payapishangjia', $v['shangjiaid'], 'shangjianame');
//            $payapiclassid = M('payapi')->where('id=' . $v['payapiid'])->getField('payapiclassid');
//            if ($payapiclassid) {
//                $datalist[$k]['payapiclassname'] = M('payapiclass')->where('id=' . $payapiclassid)->getField('classname');
//            }
            $datalist[$k]['statusname'] = $statustype[$v['status']];
            //处理订单金额和冻结金额为两位小数点
            $datalist[$k]['new_ordermoney'] = substr($v['ordermoney'], 0, strlen($v['ordermoney']) - 1);
            $datalist[$k]['new_true_ordermoney'] = substr($v['true_ordermoney'], 0, strlen($v['true_ordermoney']) - 1);
            $datalist[$k]['new_money'] = $v['money'];
            $datalist[$k]['new_freezemoney'] = $v['freezemoney'];
        }
        if (!empty($datalist)) {
            $datalist[0]['sum_ordermoney'] = $sum_ordermoney ? $sum_ordermoney : 0;
            $datalist[0]['sum_costmoney'] = $sum_costmoney ? $sum_costmoney : 0;
            $datalist[0]['sum_trademoney'] = $sum_trademoney ? $sum_trademoney : 0;
            $datalist[0]['sum_money'] = $sum_money ? $sum_money : 0;
            $datalist[0]['sum_freezemoney'] = $sum_freezemoney ? $sum_freezemoney : 0;
            $datalist[0]['count_success'] = $count_success;
            $datalist[0]['count_fail'] = $count_fail;
            $datalist[0]['count_test'] = $count_test;
            $datalist[0]['success_rate'] = $success_rate > 0 ? $success_rate : 0;
        }
        //分页
        $ReturnArr = [
            'code' => 0,
            'msg' => '数据加载成功', //响应结果
            'count' => $count, //总页数
            'data' => $datalist
        ];
        $this->ajaxReturn($ReturnArr, 'json');
    }

    //导出自助账号交易数据
    public function downloadUserOrder()
    {
        $where = [];
        $where[0] = "users_id=" . session('user_info.id');
        $i = 1;
        $payapiid = I("get.payapiid", "");
        if ($payapiid <> "") {
            $where[$i] = "payapiid = " . $payapiid;
            $i++;
        }

        $payapiaccountid = I("get.payapiaccountid", "");
        if ($payapiaccountid <> "") {
            $where[$i] = "payapiaccountid = " . $payapiaccountid;
            $i++;
        }

        $userip = I("get.userip", "", 'trim');
        if ($userip <> "") {
            $where[$i] = "(userip like '" . $userip . "%')";
            $i++;
        }
        $userordernumber = I("get.userordernumber", "", 'trim');
        if ($userordernumber <> "") {
            $where[$i] = "(userordernumber like '" . $userordernumber . "%')";
            $i++;
        }
        $status = I("get.status", "");
        if ($status <> "") {
            $where[$i] = "status = " . $status;
            $i++;
        }
        $type = I("get.ordertype", "");
        if ($type <> "") {
            $where[$i] = "type = " . $type;
            $i++;
        }
        $start = I("get.start", "");
        if ($start <> "") {
            $where[$i] = "DATEDIFF('" . $start . "',datetime) <= 0";
            $i++;
        }
        $end = I("get.end", "");
        if ($end <> "") {
            $where[$i] = "DATEDIFF('" . $end . "',datetime) >= 0";
            $i++;
        }
        $success_start = I("get.success_start", "");
        if ($success_start <> "") {
            $where[$i] = "DATEDIFF('" . $success_start . "',successtime) <= 0";
            $i++;
        }
        $success_end = I("get.success_end", "");
        if ($success_end <> "") {
            $where[$i] = "DATEDIFF('" . $success_end . "',successtime) >= 0";
            $i++;
        }
        $money_start = I("get.money_start", "");
        if ($money_start <> "") {
            $where[$i] = "ordermoney >= " . $money_start;
            $i++;
        }
        $money_end = I("get.money_end", "");
        if ($money_end <> "") {
            $where[$i] = "ordermoney <= " . $money_end;
            $i++;
        }

        //查询数据
        $datalist = M('orderinfo')->where($where)->select();
        $statustype = C('STATUSTYPE');
        foreach ($datalist as $k => $v) {
            if ($v['type'] == 1) {
                $datalist[$k]['type'] = '测试';
            } else {
                $datalist[$k]['type'] = '交易';
            }
//            $datalist[$k]['username'] = $this->getNameById('user', $v['userid'], 'username');
            $payapiclassid = PayapiModel::getPayapiclassid($v['payapiid']);
            if ($payapiclassid) {
                $datalist[$k]['payapiclassname'] = PayapiclassModel::getPayapiclassid($payapiclassid);
            }
            $datalist[$k]['payapiname'] = $this->getNameById('payapi', $v['payapiid'], 'zh_payname');
            $datalist[$k]['accountname'] = $this->getNameById('payapiaccount', $v['payapiaccountid'], 'bieming');
            $datalist[$k]['status'] = $statustype[$v['status']];
        }

        $title = '自助账号交易记录表';
        $menu_zh = array('商户号', '用户订单号', '提交地址IP', '提交金额', '实际金额', '到账金额', '冻结金额', '通道名称', '通道账号', '订单时间', '成功时间', '状态', '类型');
        $menu_en = array('memberid', 'userordernumber', 'userip', 'ordermoney', 'true_ordermoney', 'money', 'freezemoney', 'payapiname', 'accountname', 'datetime', 'successtime', 'status', 'type');
        $config = array('RowHeight' => 25, 'Width' => 20);
        $this->addUserOperate('用户[' . session('user_info.username') . ']导出了自助账号交易记录列表');
        DownLoadExcel($title, $menu_zh, $menu_en, $datalist, $config);
    }

    //2019-4-9 任梦龙：查看
    public function seeUserOrderInfo()
    {
        $order = M('order')->where('id=' . I('orderid'))->find();
        $order_info = M('orderinfo')->where('orderid=' . I('orderid'))->find();
        $statustype = C('STATUSTYPE');
        $order_info['username'] = $this->getNameById('user', $order_info['userid'], 'username');
        $payapiclass_id = PayapiModel::getPayapiclassid($order_info['payapiid']);
        if ($payapiclass_id) {
            $order_info['classname'] = $this->getNameById('payapiclass', $payapiclass_id, 'classname');
        }
        $order_info['status_name'] = $statustype[$order_info['status']];
        $order_info['callbackurl'] = $order['callbackurl'];
        $order_info['notifyurl'] = $order['notifyurl'];
        if ($order_info['type'] == 1) {
            $order_info['type'] = '测试';
        } else {
            $order_info['type'] = '交易';
        }
        if ($order_info['complaint'] == 1) {
            $order_info['complaint'] = '已投诉';
        } elseif ($order_info['complaint'] == 2) {
            $order_info['complaint'] = '已撤诉';
        } else {
            $order_info['complaint'] = '正常';
        }
        //添加资金变动记录
        if ($order_info['status'] > 0) {
            $change = M('moneychange')->where('transid="' . $order_info['sysordernumber'] . '" and changetype=4')->find();
            $order_info['oldmoney'] = $change['oldmoney'];
            $order_info['changemoney'] = $change['changemoney'];
            $order_info['nowmoney'] = $change['nowmoney'];
        }
        $this->assign('order_info', $order_info);
        $this->display();
    }

    //查看冻结金额明细记录
    public function seeOrderFreezeMoney()
    {
        $orderid = I('orderid');
        //查询订单金额表的id和冻结金额
        $sysordernumber = M('order')->where('id=' . $orderid)->getField('sysordernumber');
        $ordermoney = M('ordermoney')->where('sysordernumber="' . $sysordernumber . '"')->find();
        $this->assign('ordermoney', $ordermoney);
        $this->display();
    }

    //加载冻结金额明细记录
    public function loadFreezeMoneyList()
    {
        $datalist = M('orderfreezemoney')->where('ordermoney_id=' . I('get.ordermoneyid'))->select();
        foreach ($datalist as $k => $v) {
            $moneytype = M('moneytype')->where('id=' . $v['moneytype_id'])->find();
            $datalist[$k]['moneytypename'] = $moneytype['moneytypename'];
            $datalist[$k]['dzsj_day'] = $moneytype['dzsj_day'];
            $datalist[$k]['jiejiar'] = $moneytype['jiejiar'];
            $datalist[$k]['dzbl'] = $moneytype['dzbl'];
        }
        //分页
        $ReturnArr = [
            'code' => 0,
            'msg' => '数据加载成功', //响应结果
            'count' => 0, //总页数
            'data' => [
            ]
        ];
        //总页数
        $count = count($datalist);
        $ReturnArr['count'] = $count;
        $ReturnArr['data'] = $datalist;
        $this->ajaxReturn($ReturnArr, 'json');
    }

    //查看解冻时间变化过程
    public function loadUnfreezeInfo()
    {
        $freezemoney_id = I('get.id');
        $dataList = [];
        //查询手动解冻记录
        $manualunfreeze = M('manualunfreeze')->where('freezemoney_id=' . $freezemoney_id)->find();
        if ($manualunfreeze) {
            $manualunfreeze['type'] = 'manual';
            $dataList[] = $manualunfreeze;
        }

        //查询自动解冻记录
        $autounfreeze = M('autounfreeze')->where('freezemoney_id=' . $freezemoney_id)->find();
        if ($autounfreeze) {
            $autounfreeze['type'] = 'auto';
            $dataList[] = $autounfreeze;
        }

        //查询延期记录
        $delay = M('delayunfreeze')->where('freezemoney_id=' . $freezemoney_id)->order('id desc')->select();
        foreach ($delay as $key => $val) {
            $dataList[] = $val;
        }

        if ($dataList) {
            foreach ($dataList as $k => $v) {
                if ($v['adminuser_id'] > 0) {
                    $dataList[$k]['adminuser_name'] = M('adminuser')->where('id=' . $v['adminuser_id'])->getField('user_name');
                }
            }
        }

        //分页
        $ReturnArr = [
            'code' => 0,
            'msg' => '数据加载成功', //响应结果
            'count' => 0, //总页数
            'data' => [
            ]
        ];
        //总页数
        $count = count($dataList);
        $ReturnArr['count'] = $count;
        $ReturnArr['data'] = $dataList;
        $this->ajaxReturn($ReturnArr, 'json');
    }

    //修改提交时间
    public function changeOpenType()
    {
        $msg = "修改默认提交时间:";
        $data['user_id'] = I('post.user_id');
        $data['order_open_type'] = I('post.order_open_type');
        $all_type = C('ORDEROPENTYPE');
        $stat = "修改为[" . $all_type[$data["order_open_type"]] . "],";
        $data['user_type'] = 'user';
        $where = [
            'user_id' => I('post.user_id'),
            'user_type' => 'user'
        ];
        $old = M('orderopentype')->where($where)->find();
        if ($old) {
            //判断是否修改
            if ($old['order_open_type'] != I('post.order_open_type')) {
                $res = M('orderopentype')->where($where)->save($data);
            } else {
                $res = true;
            }
        } else {
            //添加
            $res = M('orderopentype')->add($data);
        }
        if ($res) {
            $this->addUserOperate($msg . $stat . '修改成功');
            $this->ajaxReturn(['msg' => "提交时间修改成功!", 'status' => 'ok'], "json");
        }
        $this->addUserOperate($msg . $stat . '修改失败');
        $this->ajaxReturn(['msg' => "提交时间修改失败!", 'status' => 'no'], "json");
    }
    /**********************************************/
}