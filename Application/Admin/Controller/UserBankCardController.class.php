<?php
/**
 * 用户的结算银行卡
 */

namespace Admin\Controller;

use Admin\Model\SystembankModel;
use Admin\Model\UserbankcardModel;
use Admin\Model\UserModel;

//2019-3-25 任梦龙：修改，从视图中取结算银行数据：还有问题
//2019-4-19 rml：优化，完善逻辑
class UserBankCardController extends CommonController
{
    public function __construct()
    {
        parent::__construct();
    }

    //用户银行卡列表页面
    public function UserBankCardList()
    {
//        $banklist = SystembankModel::getAllBankInfo();
        $banklist = M('jsbank')->where(['del' => 0])->field('bankname,bankcode')->select();
        $this->assign("banklist", $banklist);
        $this->display();
    }

    //加载用户银行卡列表
    public function LoadUserBankCardList()
    {
        $where = [];
        $where[0] = "userid = " . I("get.userid", 0);
        $where[1] = "del = 0";
        $i = 2;
        $bankname = I("post.bankname", "", 'trim');
        if ($bankname <> "") {
            $where[$i] = "bankname = '" . $bankname . "'";
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

    //添加银行卡页面
    public function AddUserBankCard()
    {
//        $banklist = M("jsbank")->field('id,bankname')->where("del = 0")->select();
        $banklist = M('jsbank')->where(['del' => 0])->field('bankname,bankcode')->select();
        $this->assign("banklist", $banklist);
        $this->display();
    }

    //确认添加用户银行卡
    public function UserBankCardAdd()
    {
        $user_name = UserModel::getUserName(I('post.userid'));
        $msg = '添加用户[' . $user_name . ']的结算银行卡[' . I('post.bankname', '', 'trim') . ']:';
        $return = AddSave('userbankcard', 'add', '添加');
        $this->addAdminOperate($msg . $return['msg']);
        $this->ajaxReturn($return, "json");
    }

    //修改用户银行卡页面
    public function UserBankCardEdit()
    {
        //2019-3-26 任梦龙：修改时不能直接从视图中取数据了,因为用户银行卡的单独存在一个表中,如果系统银行的状态改变或者删除了,则就对不上了,所以直接不让其改变银行名称
//        $banklist = M("jsbank")->field('id,bankname')->where("del = 0")->select();
//        $this->assign("banklist", $banklist);
        $find = UserbankcardModel::getInfo(I("get.id"));
        $this->assign("find", $find);
        $this->display();
    }

    //确认修改用户银行卡
    public function EditUserBankCard()
    {
        $user_name = UserModel::getUserName(I('post.userid'));
        $msg = '修改用户[' . $user_name . ']的结算银行卡[' . I('post.bankname', '', 'trim') . ']:';
        $return = AddSave('userbankcard', 'save', '编辑');
        $this->addAdminOperate($msg . $return['msg']);
        $this->ajaxReturn($return, "json");
    }

    //修改用户银行卡状态
    public function UserBankCardStatus()
    {
        $id = I("post.id", "");
        $user_id = I('get.userid');
        $user_name = UserModel::getUserName($user_id);
        $bankname = UserbankcardModel::getUserBankName($id);
        $status = I("post.status", "");
        $stat = $status == 1 ? "修改为启用" : "修改为禁用";
        $msg = '修改用户[' . $user_name . ']的结算银行卡[' . $bankname . ']的状态:' . $stat;
        $res = UserbankcardModel::editStatus($id, $status);
        if ($res) {
            $this->addAdminOperate($msg . '修改成功');
            $this->ajaxReturn(['status' => 'ok', 'msg' => '修改成功']);
        } else {
            $this->addAdminOperate($msg . '修改失败');
            $this->ajaxReturn(['status' => 'no', 'msg' => '修改失败']);
        }
    }

    //修改用户银行卡的默认状态
    public function UserBankCardMr()
    {
        $id = I("post.id", "");
        $mr = I("post.mr", "");
        $find = UserbankcardModel::getInfo($id);
        $user_name = UserModel::getUserName($find['userid']);
        $stat = $mr == 1 ? "修改为默认" : "取消默认";
        $msg = '修改用户[' . $user_name . ']的结算银行卡[' . $find['bankname'] . ']的默认状态:' . $stat;
        $res = UserbankcardModel::editMr($id, $mr);
        if ($res) {
            if ($mr == 1) {
                UserbankcardModel::editMrstatus($find['userid'], $id);
            }
            $this->addAdminOperate($msg . '修改成功');
            $this->ajaxReturn(['status' => 'ok', 'msg' => '修改成功']);
        } else {
            $this->addAdminOperate($msg . '修改失败');
            $this->ajaxReturn(['status' => 'no', 'msg' => '修改失败']);
        }
    }

    //删除用户银行卡
    public function DelUserBankCard()
    {
        $id = I("post.id", "");
        if (!$id) {
            $this->ajaxReturn(['status' => 'no', 'msg' => '请勿非法操作']);
        }
        $find = UserbankcardModel::getInfo($id);
        if (!$find) {
            $this->ajaxReturn(['status' => 'no', 'msg' => '请勿非法操作']);
        }
        $user_name = UserModel::getUserName($find['userid']);
        $msg = '删除用户[' . $user_name . ']的结算银行卡[' . $find['bankname'] . ']:';
        $res = UserbankcardModel::delUserBank($id);
        if ($res) {
            $this->addAdminOperate($msg . '删除成功');
            $this->ajaxReturn(['status' => 'ok', 'msg' => '删除成功']);
        } else {
            $this->addAdminOperate($msg . '删除失败');
            $this->ajaxReturn(['status' => 'no', 'msg' => '删除失败']);
        }
    }
}