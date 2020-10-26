<?php

namespace Admin\Controller;

use Admin\Model\UserModel;
use Admin\Model\UserinvitecodeModel;
use Admin\Model\UsertypeModel;
use Admin\Model\UsercodestatusModel;
use Admin\Model\AdminuserModel;
use Think\Controller;


class UserInviteCodeController extends CommonController
{
    /*
     * 邀请码列表
     */
    public function InviteList()
    {
        //页面自动判断邀请码是否过期
        UserinvitecodeModel::compareTime();
        //注册类型=用户类型
        $usertypelist = UsertypeModel::selectUserType();
        $this->assign('usertypelist', $usertypelist);
        //获取邀请码状态
        $code_status = UsercodestatusModel::codeStatusList();
        $this->assign('code_status', $code_status);
        $this->display();
    }

    /*
     * 加载邀请码列表数据
     */
    public function LoadInviteList()
    {
        $where = [];
        $i = 0;
        $where[$i] = "del=0";
        //邀请码
        $invite_code = I('post.invite_code', '', 'trim');
        if ($invite_code <> '') {
            $where[$i] = "invite_code = '" . $invite_code . "'";
            $i++;
        }
        //使用者
        $user_name = I("post.user_name", "", 'trim');
        if ($user_name <> "") {
            $user_id = UserModel::getUserId($user_name);  //根据使用者名称获取对应的用户id
            $user_id = $user_id ? $user_id : -1;
            $where[$i] = "user_id = " . $user_id;
            $i++;
        }

        //注册时间
        $create_time = I("post.create_time", "");
        if ($create_time <> "") {
            $where[$i] = "DATEDIFF('" . $create_time . "',create_time) <= 0";
            $i++;
        }

        //过期时间
        $over_time = I("post.over_time", "");
        if ($create_time <> "") {
            $where[$i] = "DATEDIFF('" . $over_time . "',over_time) <= 0";
            $i++;
        }

        //使用时间
        $use_time = I("post.use_time", "");
        if ($use_time <> "") {
            $where[$i] = "DATEDIFF('" . $use_time . "',use_time) <= 0";
            $i++;
        }

        //注册类型
        $reg_type = I("post.reg_type", "");
        if ($reg_type <> "") {
            $where[$i] = 'reg_type = ' . $reg_type;
            $i++;
        }

        //状态
        $status = I("post.status", "");
        if ($status <> "") {
            $where[$i] = '`status` = ' . $status;
            $i++;
        }

        $count = D('userinvitecode')->where($where)->count();
        //分页页面展示设置,得到数据库里的数据（del=0）二维数组
        $datalist = D('userinvitecode')->scope('default_scope')->where($where)
            ->page(I("post.page", "1"), I("post.limit", "10"))
            ->order('id DESC')
            ->select();
        //通过user_id查询对应的用户名字
        foreach ($datalist as $key => $val) {
            $datalist[$key]['par_name'] = $val['pid'] ? UserModel::getUserName($val['pid']) : '无';   //获取所属上级名称
            //根据发布者类型在对应的表中查询发布者名称
            if ($val['type'] == 1) {
                $datalist[$key]['make_name'] = AdminuserModel::getAdminName($val['make_id']);   //获取管理员发布者名称
            }
            if ($val['type'] == 2) {
                $datalist[$key]['make_name'] = UserModel::getUserName($val['make_id']);   //获取主用户发布者名称
            }
            if ($val['type'] == 3) {
                $datalist[$key]['make_name'] = M('childuser')->where("id=" . $val['make_id'])->getField('child_name');   //获取子账号发布者名称
            }
            $datalist[$key]['user_name'] = $val['user_id'] ? UserModel::getUserName($val['user_id']) : '-';   //获取使用者
        }
        $ReturnArr = [
            'code' => 0,
            'msg' => '数据加载成功',
            'count' => $count,
            'data' => $datalist
        ];
        $this->ajaxReturn($ReturnArr, 'JSON');
    }

    //2019-3-6 任梦龙：修改生成邀请码的方式
    public function UserInviteAdd()
    {
        //2019-3-7 任梦龙：修改为pay_userinvitecode，直接生成邀请码
        $invite = randpw(32, 'ALL');    //一进页面就出现随机的邀请码
        $user_type = UsertypeModel::selectUserType();   //用户类型=注册类型
        $user_info = UserModel::selectUser(['del' => 0]);   //所属上级=用户名字 获取用户列表数据
        $invite_status = UsercodestatusModel::selectInviteStatus();    //选择邀请码状态：添加时：可以使用，禁止使用
        $this->assign('invite', $invite);
        $this->assign('user_type', $user_type);
        $this->assign('invite_status', $invite_status);
        $this->assign('user_info', $user_info);
        //2019-3-7 任梦龙:修改admin_id为make_id ,添加type字段，区分发布者类型
        $this->assign('make_id', session('admin_info.id'));    //发布者id(当前管理员的id)  用session
        $this->assign('type', 1);    //发布者类型：1=管理员
        $this->display();
    }

    /*
     * 提交表单，添加邀请码
     */
    //2019-3-6 任梦龙：修改，添加操作记录
    //2019-3-14 任梦龙：优化代码
    public function createInvite()
    {
        $msg = '添加邀请码:' . I('post.invite_code', '', 'trim') . ',';
        $return = AddSave('userinvitecode', 'add', '添加邀请码');
        $this->addAdminOperate($msg . $return['msg']);
        $this->ajaxReturn($return, "json");
    }

    /*
     * 修改页面
     */
    //2019-3-6 任梦龙：修改，重新排版，对应的操数据作放到对应的模型层
    public function UserInviteEdit()
    {
        $id = I('id', 0, 'intval');
        $one_info = UserinvitecodeModel::getOneInfo($id);    //2019-3-6 任梦龙：添加注释，当前邀请码记录
        $user_type = UsertypeModel::selectUserType(); //2019-4-18 rml：修改  用户类型=注册类型
        $invite_status = UsercodestatusModel::selectInviteStatus();    //选择邀请码状态：修改时：可以使用，禁止使用
        $user_info = UserModel::selectUser(['del' => 0]);      //获取用户列表数据
        $this->assign('user_info', $user_info);
        $this->assign('user_type', $user_type);
        $this->assign('invite_status', $invite_status);
        $this->assign('one_info', $one_info);
        //2019-3-7 任梦龙:修改时不需要改发布者了
        $this->display();
    }

    /*
     * 提交表单，修改数据
     */
    //2019-3-6 任梦龙：修改，添加操作记录
    //2019-3-14 任梦龙：优化代码
    public function InviteUpdate()
    {
        $msg = '修改邀请码:' . I('post.invite_code', '', 'trim').',';
        $return = AddSave('userinvitecode', 'save', '修改邀请码');
        $this->addAdminOperate($msg . $return['msg']);
        $this->ajaxReturn($return, "json");
    }

    /*
     * 随机生成邀请码
     */
    //2019-3-7 任梦龙：修改为pay_userinvitecode,直接生成邀请码
    //2019-3-6 任梦龙：修改
    public function InviteCodeCreate()
    {
        $invite = randpw(32, 'ALL');
        if (!$invite) {
            $this->ajaxReturn(['msg' => '生成失败,请重试!', 'status' => 'no']);
        }
        $this->ajaxReturn(['invite' => $invite, 'status' => 'ok']);
    }

    /*
     * 删除一条数据
     * 2019-1-14 任梦龙：修改，将删除放在模型层处理
     */
    //2019-3-6 任梦龙：修改删除方法，添加操作记录
    public function InviteDel()
    {
        $id = I("post.id", 0, 'intval');
        if (!$id) {
            $this->ajaxReturn(['status' => 'no', 'msg' => '请勿非法操作!']);
        }
        $invite_code = UserinvitecodeModel::getInviteCode($id);
        $msg = '删除邀请码:' . $invite_code;
        $res = UserinvitecodeModel::delInviteCode(['id' => $id]);
        if ($res) {
            $this->addAdminOperate($msg . ',删除成功');
            $this->ajaxReturn(['status' => 'ok', 'msg' => '删除成功']);
        } else {
            $this->addAdminOperate($msg . ',删除失败');
            $this->ajaxReturn(['status' => 'no', 'msg' => '删除失败，请重试']);
        }

    }

    //2019-3-6 任梦龙：修改删除方法，添加操作记录
    //2019-1-14 任梦龙：添加批量删除功能
    public function delAll()
    {
        $idstr = I("post.idstr", "", 'trim');
        if (!$idstr) {
            $this->ajaxReturn(['status' => 'no', 'msg' => '请先选择邀请码']);
        }
        $res = UserinvitecodeModel::delInviteCode(['id' => ['in', $idstr]]);
        if ($res) {
            $this->addAdminOperate('批量删除邀请码:删除成功');
            $this->ajaxReturn(['status' => 'ok', 'msg' => '删除成功']);
        } else {
            $this->addAdminOperate('批量删除邀请码:删除失败');
            $this->ajaxReturn(['status' => 'no', 'msg' => '删除失败，请重试']);
        }
    }

}
