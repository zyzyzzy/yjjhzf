<?php
/**
 * Created by PhpStorm.
 * User: 汪桂芳
 * Date: 2019/02/26
 * Time: 09:50
 */

namespace Admin\Controller;

use Admin\Model\CashierModel;
use Admin\Model\PayapiclassModel;
use Admin\Model\UserModel;
use Admin\Model\UserpayapiclassModel;
use Think\Controller;

class CashierController extends CommonController
{
    public function __construct()
    {
        parent::__construct();
    }

    //用户的收银台设置页面
    public function userCashier()
    {
        $user_id = I('get.userid');
        $this->assign('user_id', $user_id);
        //查询用户的收银台设置
        $user_cashier = CashierModel::userCashierInfo($user_id);
        //查询用户开通的通道分类
        $user_payapiclass = UserpayapiclassModel::getUserClass($user_id);
        $user_pc_payapiclass_list = [];
        $user_wap_payapiclass_list = [];
        $user_pc_cashier_payapiclass = json_decode($user_cashier['pc_payapiclass_json']);
        $user_wap_cashier_payapiclass = json_decode($user_cashier['wap_payapiclass_json']);
        foreach ($user_payapiclass as $k => $v) {
            //判断此通道分类是否开启及是在pc端显示还是wap端显示
            $class_info = PayapiclassModel::getPayapiClassInfo($v);
            if ($class_info['status'] == 1) {
                if ($class_info['pc'] == 1) {
                    $user_pc_payapiclass_list[$k]['id'] = $v;
                    $user_pc_payapiclass_list[$k]['class_name'] = M('payapiclass')->where('id=' . $v)->getField('classname');
                    if (in_array($v, $user_pc_cashier_payapiclass)) {
                        $user_pc_payapiclass_list[$k]['check'] = 1;
                    }
                }
                if ($class_info['wap'] == 1) {
                    $user_wap_payapiclass_list[$k]['id'] = $v;
                    $user_wap_payapiclass_list[$k]['class_name'] = M('payapiclass')->where('id=' . $v)->getField('classname');
                    if (in_array($v, $user_wap_cashier_payapiclass)) {
                        $user_wap_payapiclass_list[$k]['check'] = 1;
                    }
                }
            }
        }
        $this->assign('user_cashier', $user_cashier);
        $this->assign('user_pc_payapiclass_list', $user_pc_payapiclass_list);
        $this->assign('user_wap_payapiclass_list', $user_wap_payapiclass_list);
        $this->display();
    }

    //用户收银台类型修改
    public function userCashierTypeEdit()
    {
        $type = I('get.type');
        $user_id = I('get.user_id');
        $user_name = UserModel::getUserName($user_id);
        $stat = $type == 1 ? "修改为自定义收银台" : "修改为应用系统收银台";
        $msg = "修改用户'" . $user_name . "'的收银台应用类型:" . $stat;
        $res = CashierModel::saveUserCashierType($user_id, $type);
        if ($res) {
            $this->addAdminOperate($msg . ',修改成功');
            $this->ajaxReturn(['status' => 'ok', 'msg' => "用户收银台设置成功"]);
        } else {
            $this->addAdminOperate($msg . ',修改失败');
            $this->ajaxReturn(['status' => 'no', 'msg' => "用户收银台设置失败"]);
        }
    }

    //未传图片的收银台设置
    public function editUserCashier()
    {
        $post = I('post.');
        $data = [
            'user_id' => $post['user_id'],
            'company' => $post['company'],
            'footer' => $post['footer'],
        ];
        $pc_json_arr = [];
        if ($post['pc_class']) {
            foreach ($post['pc_class'] as $k => $v) {
                $pc_json_arr[] = $v;
            }
            $data['pc_payapiclass_json'] = json_encode($pc_json_arr);
        } else {
            $data['pc_payapiclass_json'] = '';
        }
        $wap_json_arr = [];
        if ($post['wap_class']) {
            foreach ($post['wap_class'] as $k => $v) {
                $wap_json_arr[] = $v;
            }
            $data['wap_payapiclass_json'] = json_encode($wap_json_arr);
        } else {
            $data['wap_payapiclass_json'] = '';
        }
        $user_name = UserModel::getUserName($post['user_id']);
        $msg = '修改用户[' . $user_name . ']的收银台内容：';
        $res = CashierModel::saveUserCashierInfo($post['id'], $data);
        if ($res) {
            $this->addAdminOperate($msg . '修改成功');
            $this->ajaxReturn(['status' => 'ok', 'msg' => "用户收银台设置成功"]);
        } else {
            $this->addAdminOperate($msg . '修改失败');
            $this->ajaxReturn(['status' => 'no', 'msg' => '用户收银台设置失败']);
        }
    }

    //上传图片的收银台设置
    public function editUserCashierLogo()
    {
        $post = I('post.');
        //上传文件
        $upload = new \Think\Upload(); // 实例化上传类
        $upload->maxSize = 2097152; // 设置附件上传大小
//        $upload->exts = array('xls'); // 设置附件上传类型jpg|png|gif|bmp|jpeg
        $upload->rootPath = C('CASHIER_PATH'); // 设置附件上传目录
        $upload->saveName = 'user-' . $post['user_id'].'-'.date('YmdHis');   //以用户id+时间命名
//        $upload->subName = 'user-' . $post['user_id'];   //子目录创建方式，
        $upload->replace = true;  //存在同名文件覆盖
        $upload->autoSub = false;  //不自动使用子目录保存上传文件
        if (!file_exists(C('CASHIER_PATH'))) {
            mkdir(C('CASHIER_PATH'), 0777, true);
        }
        // 上传文件
        $info = $upload->uploadOne($_FILES['file']);
        $user_name = UserModel::getUserName($post['user_id']);
        $msg = '上传用户[' . $user_name . ']收银台的logo时：';
        if (!$info) {
            $this->addAdminOperate($msg . $upload->getError());
            $this->ajaxReturn(['status' => 'no', 'msg' => $upload->getError()]);
        } else {
            $all_path = C('CASHIER_PATH') . $info['savename'];
            $data = [
                'user_id' => $post['user_id'],
                'company' => $post['company'],
                'footer' => $post['footer'],
                'logo' => $all_path,
            ];
            if ($post['pc_class']) {
                $pc_json = trim($post['pc_class'], '');
                $pc_json_arr = explode(',', $pc_json);
                $data['pc_payapiclass_json'] = json_encode($pc_json_arr);
            } else {
                $data['pc_payapiclass_json'] = '';
            }
            if ($post['wap_class']) {
                $wap_json = trim($post['wap_class'], '');
                $wap_json_arr = explode(',', $wap_json);
                $data['wap_payapiclass_json'] = json_encode($wap_json_arr);
            } else {
                $data['wap_payapiclass_json'] = '';
            }
            $old_logo = CashierModel::getCashlogo($post['user_id']);
            $res = CashierModel::saveUserCashierInfo($post['id'], $data);
            if ($res) {
                //成功时将原图片删除
                if(file_exists($old_logo)){
                    unlink($old_logo);
                }
                $this->addAdminOperate($msg . '修改成功');
                $this->ajaxReturn(['status' => 'ok', 'msg' => "用户收银台设置成功"]);
            } else {
                unlink($all_path);
                $this->addAdminOperate($msg . '修改失败');
                $this->ajaxReturn(['status' => 'no', 'msg' => '用户收银台设置失败']);
            }
        }
    }

    //用户的自助收银设置页面
    public function userSelfCash()
    {
        $user_id = I('get.userid');
        $this->assign('user_id', $user_id);
        //查询用户是否开通自助收银
        $selfcash_status = UserModel::getUserSelfcashStatus($user_id);
        $this->assign('selfcash_status', $selfcash_status);
        if ($selfcash_status == 1) {
            //开启,查询用户的二维码,没有的话给用户生成一个二维码
            $qrcode = UserModel::getUserSelfcashQrcode($user_id);
            if (!$qrcode) {
                import("Vendor.phpqrcode.phpqrcode", '', ".php");
                $usercode = UserModel::getUserCode($user_id);
                if (!file_exists(C('USERQRCODE_PATH'))) {
                    mkdir(C('USERQRCODE_PATH'), 0777, true);
                }
                $qrcode = C('USERQRCODE_PATH') . $usercode . ".png";//二维码图片路径
                $website = M('website')->field('pay_domain,pay_http')->order('id DESC')->limit(1)->find();   //2019-5-7 rml:修改
                $http = $website['pay_http'] == 1 ? "http://" : "https://";
                $url = $http . $website['pay_domain'] . U('UserPay/SelfCash/selfCash') . '?usercode=' . $usercode;
                \QRcode::png($url, $qrcode, "L", 20);
                UserModel::setUserSelfcashQrcode($user_id, $qrcode);
                //不存在
                $this->assign('del', 1);
            } else {
                //判断图片是否被删除
                if (file_exists($qrcode)) {
                    //存在,未删除
                    $this->assign('del', 2);
                } else {
                    //存在,已删除
                    $this->assign('del', 3);
                }
            }
            $this->assign('qrcode', $qrcode);
        }
        $this->display();
    }

    //用户自助收银状态修改
    public function userSelfCashStatusEdit()
    {
        $status = I('get.status');
        $user_id = I('get.user_id');
        $res = UserModel::setUserSelfcashStatus($user_id, $status);
        $user_name = UserModel::getUserName($user_id);
        $stat = $status == 1 ? '修改为开启' : '修改为关闭';
        $msg = "修改用户'" . $user_name . "'的自助收银状态:" . $stat;
        if ($res) {
            $this->addAdminOperate($msg . ',修改成功');
            $this->ajaxReturn(['status' => 'ok', 'msg' => "用户自助收银状态修改成功"]);
        } else {
            $this->addAdminOperate($msg . ',修改失败');
            $this->ajaxReturn(['status' => 'no', 'msg' => "用户自助收银状态修改失败"]);
        }
    }

    //用户自助收银二维码的删除
    public function deleteUserQrcode()
    {
        $user_id = I('user_id');
        $user_name = UserModel::getUserName($user_id);
        $msg = "删除用户'" . $user_name . "'的自助收银二维码:";
        //删除用户的二维码图片
        $qrcode = UserModel::getUserSelfcashQrcode($user_id);
        $res = unlink($qrcode);
        if ($res) {
            $this->addAdminOperate($msg . '删除成功');
            $this->ajaxReturn(['status' => 'ok', 'msg' => "用户自助收银二维码删除成功"]);
        } else {
            $this->addAdminOperate($msg . '删除失败');
            $this->ajaxReturn(['status' => 'no', 'msg' => "用户自助收银二维码删除失败"]);
        }
    }

    //用户自助收银二维码的重新生成
    public function addUserQrcode()
    {
        $user_id = I('user_id');
        $user_name = M('user')->where('id=' . $user_id)->getField('username');
        $msg = "重新生成用户'" . $user_name . "'的自助收银二维码:";
        //生成二维码图片
        import("Vendor.phpqrcode.phpqrcode", '', ".php");
        $usercode = UserModel::getUserCode($user_id);
        if (!file_exists(C('USERQRCODE_PATH'))) {
            mkdir(C('USERQRCODE_PATH'), 0777, true);
        }
        $qrcode = C('USERQRCODE_PATH') . $usercode . ".png";//二维码图片路径
        $website = M('website')->field('pay_domain,pay_http')->order('id DESC')->limit(1)->find();   //2019-5-7 rml:修改
        $http = $website['pay_http'] == 1 ? "http://" : "https://";
        $url = $http . $website['pay_domain'] . U('UserPay/SelfCash/selfCash') . '?usercode=' . $usercode;
        \QRcode::png($url, $qrcode, "L", 20);
        $old_qrcode = UserModel::getUserSelfcashQrcode($user_id);
        if ($old_qrcode == $qrcode) {
            $res = true;
        } else {
            $res = UserModel::setUserSelfcashQrcode($user_id, $qrcode);
        }
        if ($res) {
            $this->addAdminOperate($msg . '二维码生成成功');
            $this->ajaxReturn(['status' => 'ok', 'msg' => "用户自助收银二维码生成成功"]);
        } else {
            $this->addAdminOperate($msg . '二维码生成失败');
            $this->ajaxReturn(['status' => 'no', 'msg' => "用户自助收银二维码生成失败"]);
        }
    }
}