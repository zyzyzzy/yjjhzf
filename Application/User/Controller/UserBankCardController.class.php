<?php
/**
 * Created by PhpStorm.
 * User: zhangyang
 * Date: 18-10-16
 * Time: 上午11:51
 */

namespace User\Controller;


use Think\Controller;
use User\Model\UserbankcardModel;
use User\Model\UserModel;
use User\Model\UseroperateModel;

class UserBankCardController extends UserCommonController
{
    public function __construct()
    {
        parent::__construct();
    }

    //银行卡列表
    public function userBankCardList()
    {
        $userid = session('user_info.id');
        $this->assign('userid', $userid);
        //2019-5-7 rml:直接从结算银行视图中去数据
        $banklist = M('jsbank')->where(['del' => 0])->field('bankname,bankcode')->select();
        $this->assign("banklist", $banklist);
        $this->display();
    }

    //加载银行卡列表
    public function loadUserBankCardList()
    {
        $where = [];
        $where[0] = "userid = " . session('user_info.id');
        $where[1] = "del = 0";
        $i = 2;
        $bankname = I("post.bankname", "");
        if ($bankname <> "") {
            $where[$i] = "bankname like '%" . $bankname . "%'";
            $i++;
        }
        $banknumber = I("post.banknumber", "", 'trim');
        if ($banknumber <> "") {
            $where[$i] = "banknumber like '%" . $banknumber . "%'";
            $i++;
        }
        $bankmyname = I("post.bankmyname", "", 'trim');
        if ($bankmyname <> "") {
            $where[$i] = "bankmyname like '%" . $bankmyname . "%'";
            $i++;
        }
        $shenfenzheng = I("post.shenfenzheng", "", 'trim');
        if ($shenfenzheng <> "") {
            $where[$i] = "shenfenzheng like '%" . $shenfenzheng . "%'";
            $i++;
        }
        $tel = I("post.tel", "", 'trim');
        if ($tel <> "") {
            $where[$i] = "tel like '%" . $tel . "%'";
            $i++;
        }
        $status = I("post.status", "");
        if ($status <> "") {
            $where[$i] = "status = " . $status;
            $i++;
        }
        $this->ajaxReturn(PageDataLoad('userbankcard', $where), 'JSON');
    }

    //银行卡添加页面
    public function addUserBankCard()
    {
        $banklist = M('jsbank')->where(['del' => 0])->field('bankname,bankcode')->select();
        $this->assign("banklist", $banklist);
        $this->display();
    }

    //添加用户银行卡
    public function userBankCardAdd()
    {
        $return = AddSave('userbankcard', 'add', '添加');
        $this->addUserOperate('添加用户结算银行：' . $return['msg']);
        $this->ajaxReturn($return, 'json');
    }

    //银行卡编辑页面
    public function userBankCardEdit()
    {
        $banklist = M('jsbank')->where(['del' => 0])->field('bankname,bankcode')->select();
        $this->assign("banklist", $banklist);
        $find = UserbankcardModel::findUserBank(I("get.id"));
        $this->assign("find", $find);
        $this->display();
    }

    //编辑用户银行卡
    public function editUserBankCard()
    {
        $return = AddSave('userbankcard', 'save', '编辑');
        $this->addUserOperate('修改用户结算银行：' . $return['msg']);
        $this->ajaxReturn($return, 'json');
    }

    //修改用户银行卡状态
    public function userBankCardStatus()
    {
        $id = I("post.id", "");
        $bankname = UserbankcardModel::getUserBankname($id);
        $status = I("post.status", "");
        $stat = $status == 1 ? "修改为启用" : "修改为禁用";
        $msg = "修改用户结算银行卡[" . $bankname . "]状态:" . $stat;
        $res = UserbankcardModel::saveUserBank($id, ['status' => $status]);
        if ($res) {
            $this->addUserOperate($msg . ',修改成功');
            $this->ajaxReturn(['status' => 'ok', 'msg' => '修改成功']);
        } else {
            $this->addUserOperate($msg . ',修改失败');
            $this->ajaxReturn(['status' => 'no', 'msg' => '修改失败']);
        }
    }

    //设置默认银行卡
    public function userBankCardMr()
    {
        $id = I("post.id", "");
        $bankname = UserbankcardModel::getUserBankname($id);
        $mr = I("post.mr", "");
        $stat = $mr == 1 ? "修改为默认" : "取消默认";
        $msg = "修改用户结算银行卡[" . $bankname . "]状态:" . $stat;
        $res = UserbankcardModel::saveUserBank($id, ['mr' => $mr]);
        if ($res) {
            if ($mr == 1) {
                UserbankcardModel::editMrstatus(session('user_info.id'), $id);
            }
            $this->addUserOperate($msg . ',修改成功');
            $this->ajaxReturn(['status' => 'ok', 'msg' => '修改成功']);
        } else {
            $this->addUserOperate($msg . ',修改失败');
            $this->ajaxReturn(['status' => 'no', 'msg' => '修改失败']);
        }
    }

    //银行卡删除
    public function delUserBankCard()
    {
        $id = I("post.id", "");
        $bankname = UserbankcardModel::getUserBankname($id);
        $msg = "删除用户结算银行卡[" . $bankname . "]:";
        if (!$id) {
            $this->addUserOperate($msg . '非法操作');
            $this->ajaxReturn(['status' => 'no', 'msg' => '请勿非法操作']);
        }
        $find = UserbankcardModel::findUserBank($id);
        if (!$find) {
            $this->addUserOperate($msg . '银行卡不存在');
            $this->ajaxReturn(['status' => 'no', 'msg' => '没有该用户银行记录']);
        }
        $res = UserbankcardModel::saveUserBank($id, ['del' => 1]);
        if ($res) {
            $this->addUserOperate($msg . '删除成功');
            $this->ajaxReturn(['status' => 'ok', 'msg' => '删除成功']);
        } else {
            $this->addUserOperate($msg . '删除失败');
            $this->ajaxReturn(['status' => 'no', 'msg' => '删除失败']);
        }
    }

}