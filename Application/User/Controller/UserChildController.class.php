<?php

namespace User\Controller;

use User\Model\OrdercommissionModel;
use User\Model\PayapiclassModel;
use User\Model\PayapiModel;
use User\Model\UserinvitecodeModel;
use User\Model\ChilduserModel;
use User\Model\UserModel;
use User\Model\UsertypeModel;
use User\Model\UsercodestatusModel;
use User\Model\UserpayapiclassModel;

//代理专区
//2019-3-27 任梦龙：删除部分注释
class UserChildController extends UserCommonController
{
    public function __construct()
    {
        parent::__construct();
    }

    //邀请码列表
    public function inviteCodeList()
    {
        UserinvitecodeModel::compareTime();   //页面自动判断邀请码是否过期
        $user_type = UserModel::getUserType(session('user_info.id'));   //读取用户的类型，只有用户是代理商才可以自己添加邀请码
        $user_type_list = UsertypeModel::selectUserType();   //注册类型=用户类型
        $code_status = UsercodestatusModel::codeStatusList();    //获取邀请码状态
        $this->assign('user_id', session('user_info.id'));
        $this->assign('user_type', $user_type);
        $this->assign('user_type_list', $user_type_list);
        $this->assign('code_status', $code_status);
        $this->display();
    }

    //加载邀请码列表数据
    public function loadInviteCodeList()
    {
        $ReturnArr = [
            'code' => 0,
            'msg' => '数据加载成功', //响应结果
            'count' => 0, //总页数
            'data' => [
            ]
        ];
        $where = [];
        //在用户后台，主用户和子账号都可以看到（除了管理员的）,只属于这个用户的邀请码
        $where[0] = "del=0";
        $where[1] = "type!=1";
        $where[2] = "pid=" . session('user_info.id');
        $i = 3;
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
        $count = M('userinvitecode')->where($where)->count(); //得到del=0的所有数据
        $datalist = D('userinvitecode')->scope('default_scope')->where($where)->page(I("post.page", "1"), I("post.limit", "10"))->order('id DESC')->select();
        //通过user_id查询对应的用户名字
        foreach ($datalist as $key => $val) {
            $datalist[$key]['par_name'] = $val['pid'] ? UserModel::getUserName($val['pid']) : '无';   //获取所属上级名称
            if ($val['type'] == 2) {
                $datalist[$key]['make_name'] = UserModel::getUserName($val['make_id']);   //获取主用户发布者名称
            }
            if ($val['type'] == 3) {
                $datalist[$key]['make_name'] = ChilduserModel::getChildName($val['make_id']);   //获取子账号发布者名称
            }
            $datalist[$key]['user_name'] = $val['user_id'] ? UserModel::getUserName($val['user_id']) : '-';   //获取使用者
        }
        $ReturnArr['count'] = $count;
        $ReturnArr['data'] = $datalist;
        $this->ajaxReturn($ReturnArr, 'JSON');
    }

    //随机生成邀请码
    public function inviteCodeCreate()
    {
        $invite = randpw(32, 'ALL');
        if (!$invite) {
            $this->ajaxReturn(['msg' => '生成失败,请重试!', 'status' => 'no']);
        }
        $this->ajaxReturn(['invite' => $invite, 'status' => 'ok']);
    }

    //添加邀请码页面
    public function userInviteAdd()
    {
        $invite = randpw(32, 'ALL');   //一进页面就出现随机的邀请码
        $user_type = UsertypeModel::selectUserType();         //用户类型=注册类型
        $invite_status = UsercodestatusModel::selectInviteStatus();   //选择邀请码状态：添加时：可以使用，禁止使用
        //根据是否有子账号来判断当前的发布者是主用户还是子账号
        //2019-4-19 rml：修改发布者id
        if (empty(session('child_info'))) {
            $type = 2;  //主用户
            $make_id = session('user_info.id');
        } else {
            $type = 3;   //子账号
            $make_id = session('child_info.id');
        }
        $this->assign('invite', $invite);
        $this->assign('user_type', $user_type);
        $this->assign('pid', session('user_info.id'));     //所属上级=用户自己，即使是子账号也有权限来生成邀请码，邀请码的上级=子账号主用户
        $this->assign('invite_status', $invite_status);
        $this->assign('type', $type);
        $this->assign('make_id', $make_id);
        $this->display();
    }

    //提交表单，添加邀请码
    public function createInvite()
    {
        $msg = '添加邀请码:' . I('post.invite_code', '', 'trim');
        $return = AddSave('userinvitecode', 'add', '添加邀请码');
        $this->addUserOperate($msg . ',' . $return['msg']);
        $this->ajaxReturn($return, "json");
    }

    //修改邀请码页面
    public function userInviteEdit()
    {
        $id = I('id', 0, 'intval');
        $one_info = userinvitecodeModel::getOneInfo($id);
        $user_type = UsertypeModel::selectUserType();           //用户类型=注册类型
        $invite_status = UsercodestatusModel::selectInviteStatus();    //选择邀请码状态：修改时：可以使用，禁止使用
        $this->assign('one_info', $one_info);
        $this->assign('user_type', $user_type);
        $this->assign('invite_status', $invite_status);
        $this->display();
    }

    //确认修改邀请码
    public function inviteUpdate()
    {
        $msg = '修改邀请码:' . I('post.invite_code', '', 'trim');
        $return = AddSave('userinvitecode', 'save', '修改邀请码');
        $this->addUserOperate($msg . ',' . $return['msg']);
        $this->ajaxReturn($return, "json");
    }

    //删除一条数据
    public function inviteDel()
    {
        $id = I("post.id", 0, 'intval');
        $invite_code = UserinvitecodeModel::getInviteCode($id);
        $msg = '删除邀请码:' . $invite_code;
        if (!$id) {
            $this->ajaxReturn(['status' => 'no', 'msg' => '请勿非法操作!']);
        }
        $res = UserinvitecodeModel::delInviteCode(['id' => $id]);
        if ($res) {
            $this->addUserOperate($msg . ',删除成功');
            $this->ajaxReturn(['status' => 'ok', 'msg' => '删除成功']);
        } else {
            $this->addUserOperate($msg . ',删除失败');
            $this->ajaxReturn(['status' => 'no', 'msg' => '删除失败']);
        }
    }

    //批量删除邀请码
    public function delAll()
    {
        $idstr = I("post.idstr", "", 'trim');
        if (!$idstr) {
            $this->ajaxReturn(['status' => 'no', 'msg' => '请先选择邀请码']);
        }
        $res = UserinvitecodeModel::delInviteCode(['id' => ['in', $idstr]]);
        if ($res) {
            $this->addUserOperate('批量删除邀请码:删除成功');
            $this->ajaxReturn(['status' => 'ok', 'msg' => '删除成功']);
        } else {
            $this->addUserOperate('批量删除邀请码:删除失败');
            $this->ajaxReturn(['status' => 'no', 'msg' => '删除失败，请重试']);
        }
    }

    /**
     * 下级用户模块
     */
    //下级用户页面
    public function childUserList()
    {
        $user_id = session('user_info.id');

        $user_status_list = M("userstatus")->select();
        $this->assign("user_status_list", $user_status_list);

        $user_type_list = UsertypeModel::selectUserType();
        $this->assign("user_type_list", $user_type_list);

        $user_rengzheng_list = M("userrengzheng")->select();
        $this->assign("user_rengzheng_list", $user_rengzheng_list);

        $user_name = UserModel::getUsername($user_id);
        $this->assign("user_name", $user_name);

        $this->display();

    }

    //加载下级用户列表
    public function loadChildUserList()
    {
        $where = [];
        $where[0] = "del=0";
        $where[1] = "superiorid = " . session('user_info.id');  //查找用户的下级
        $i = 2;
        $user_name = I("post.username", "", 'trim');
        if ($user_name <> "") {
            $where[$i] = "(username like '%" . $user_name . "%' or getmemberid(id) like '%" . $user_name . "%')";
            $i++;
        }

        $status = I("post.status", "");
        if ($status <> "") {
            $where[$i] = "`status` = " . $status;
            $i++;
        }

        $user_rengzheng = I("post.userrengzheng", "");
        if ($user_rengzheng <> "") {
            $where[$i] = "authentication = " . $user_rengzheng;
            $i++;
        }

        $user_type = I("post.usertype", "");
        if ($user_type <> "") {
            $where[$i] = "usertype = " . $user_type;
            $i++;
        }

        $start = I("post.start", "");
        $end = I("post.end", "");
        if ($start <> "") {
            $where[$i] = "DATEDIFF('" . $start . "',regdatetime) <= 0";
            $i++;
        }
        if ($end <> "") {
            $where[$i] = "DATEDIFF('" . $end . "',regdatetime) >= 0";
            $i++;
        }
        $count = D('user')->where($where)->count();
        $datalist = D('user')->scope('default_scope')->where($where)->page(I("post.page", "1"), I("post.limit", "10"))->order('id DESC')->select();
        foreach ($datalist as $key => $val) {
            $datalist[$key]['child_count'] = UserModel::getChildCount($val['id']);
        }
        $ReturnArr = [
            'code' => 0,
            'msg' => '数据加载成功',
            'count' => $count,
            'data' => $datalist
        ];
        $this->ajaxReturn($ReturnArr, 'JSON');
    }

    //下级商户不可以手动添加,只能通过注册而来
    /*********************************************/
    //下级用户添加页面
    public function addChild()
    {
        $user_id = session('user_info.id');
        $this->assign('user_id', $user_id);

        $types = UsertypeModel::selectUserType();
        $this->assign('types', $types);

        $loginpassword = C('DEFAULT_LOGINPASSWORD');
        $paypassword = C('DEFAULT_PAYPASSWORD');
        $this->assign('loginpassword', $loginpassword);
        $this->assign('paypassword', $paypassword);

        $this->display();
    }

    //添加下级用户处理程序
    public function childUserAdd()
    {
        $res = AddSave('user', 'add', '添加用户');
        $msg = "添加下级商户[" . I('username') . "]:";
        if ($res['status'] == 'ok') {
            //密码表添加记录
            $data = [
                'loginpassword' => md5(I('loginpassword')),
                'paypassword' => md5(I('paypassword')),
                'userid' => $res['id']
            ];
            $table = D('userpassword');
            if (!$table->create($data)) {
                $return["status"] = "no";
                $return["msg"] = $table->getError();
            } else {
                $r = $table->add();
            }

            //密钥表添加记录
            $data1 = [
                'userid' => $res['id']
            ];
            $table1 = D('secretkey');
            if (!$table1->create($data1)) {
                $return["status"] = "no";
                $return["msg"] = $table1->getError();
            } else {
                $r1 = $table1->add();
            }
            if ($r && $r1) {
                $this->addUserOperate($msg . '添加成功');
                $this->ajaxReturn(["status" => 'ok', "msg" => '添加用户成功',], "json");
            } else {
                $this->addUserOperate($msg . '添加失败');
                $this->ajaxReturn(["status" => 'no', "msg" => $table->getError(),], "json");
            }
        } else {
            $this->addUserOperate($msg . $res['msg']);
            $this->ajaxReturn($res, "json");
        }
    }
    /*********************************************/

    //下级用户的通道分类页面
    public function childClassList()
    {
        $user_id = I('user_id');
        $this->assign('user_id', $user_id);
        //读取系统默认的利润
        $system = C('USER_COMMISSION');
        $b_system = ($system * 100) . '%';
        $this->assign('system', $system);
        $this->assign('b_system', $b_system);
        //查询是否有数据
        $datalist = UserpayapiclassModel::getUserPayapiClass($user_id);
        if ($datalist) {
            $status = 1;
        } else {
            $status = 0;
        }
        $this->assign('status', $status);
        $code_type = $this->getCodeType();
        $this->assign('code_type', $code_type);

        $this->display();
    }

    //加载下级用户的通道分类
    public function loadChildClassList()
    {
        $superior_id = session('user_info.id');
        //读取系统默认的利润
        $system = C('USER_COMMISSION');
        $user_id = I('get.user_id');
        //查询用户开通的通道分类
//        $count = M('userpayapiclass')->where('userid=' . $user_id)->count();
//        $datalist = M('userpayapiclass')->where('userid=' . $user_id)->select();
        $datalist = UserpayapiclassModel::getUserPayapiClass($user_id);
        foreach ($datalist as $k => $v) {
            //查询分类名称
//            $datalist[$k]['classname'] = M('payapiclass')->where('id=' . $v['payapiclassid'])->getField('classname');
            $datalist[$k]['classname'] = PayapiclassModel::getPayapiclassid($v['payapiclassid']);
            //读取代理商自己的费率
//            $where = ['payapiclassid' => $v['payapiclassid'], 'userid' => $superior_id];
//            $s_feilv = M('userpayapiclass')->where(['payapiclassid' => $v['payapiclassid'], 'userid' => $superior_id])->getField('order_feilv');
            $s_feilv = UserpayapiclassModel::getOrderFeilv(['payapiclassid' => $v['payapiclassid'], 'userid' => $superior_id]);
            if (!$s_feilv) {
                //读取该分类的系统默认费率
//                $s_feilv = M('payapiclass')->where('id=' . $v['payapiclassid'])->getField('order_feilv');
                $s_feilv = PayapiclassModel::getOrderFeilv($v['payapiclassid']);
            }
            $datalist[$k]['min_feilv'] = $s_feilv;
            $datalist[$k]['max_feilv'] = $s_feilv + $system;
            if (!$v['order_feilv']) {
                $datalist[$k]['order_feilv'] = $datalist[$k]['max_feilv'];
            }
        }
        $ReturnArr = [
            'code' => 0,
            'msg' => '数据加载成功',
            'count' => count($datalist),
            'data' => $datalist
        ];
        $this->ajaxReturn($ReturnArr, 'json');
    }

    //修改下级用户的通道分类费率
    public function childFeilvEdit()
    {
        $superior_id = session('user_info.id');
        $list = I('post.');
        $user_name = UserModel::getUsername($list['user_id']);
        $msg = '设置下级商户[' . $user_name . ']的通道费率:';
        $this->checkVerifyCode($list['verfiy_code'], $list['code_type'], $msg);
        //读取系统默认的利润
        $system = C('USER_COMMISSION');
        unset($list['verfiy_code']);
        unset($list['code_type']);
        unset($list['user_id']);
        //判断范围
        foreach ($list as $k => $v) {
            //查询此条记录
            $userpayapiclass = M('userpayapiclass')->where('id=' . $k)->find();
            //读取代理商自己的费率
            $where = ['payapiclassid' => $userpayapiclass['payapiclassid'], 'userid' => $superior_id];
            $s_feilv = M('userpayapiclass')->where($where)->getField('order_feilv');
            if (!$s_feilv) {
                //读取该分类的系统默认费率
                $s_feilv = M('payapiclass')->where('id=' . $userpayapiclass['payapiclassid'])->getField('order_feilv');
            }
            $min_feilv = $s_feilv;
            $max_feilv = $s_feilv + $system;
            if ($v < $min_feilv || $v > $max_feilv) {
                //添加操作记录
                $this->addUserOperate($msg . '设置的费率超出可设置范围');
                $this->ajaxReturn(['status' => 'no', 'msg' => '设置的费率超出可设置范围,请检查后重新设置'], 'json');
            }
        }
        //保存设置
        foreach ($list as $key => $val) {
            M('userpayapiclass')->where('id=' . $key)->save(['order_feilv' => $val]);
        }
        $this->addUserOperate($msg . '费率设置成功');
        $this->ajaxReturn(['status' => 'ok', 'msg' => '费率设置成功'], 'json');
    }

    //下级的下级页面
    public function childUserChildList()
    {
        $user_id = I('user_id');
        $this->assign("user_id", $user_id);

        $user_status_list = M("userstatus")->select();
        $this->assign("user_status_list", $user_status_list);

        $user_type_list = UsertypeModel::selectUserType();
        $this->assign("user_type_list", $user_type_list);

        $user_rengzheng_list = M("userrengzheng")->select();
        $this->assign("user_rengzheng_list", $user_rengzheng_list);

        $user_name = UserModel::getUsername($user_id);
        $this->assign("user_name", $user_name);

        $this->display();
    }

    //加载下级的下级
    public function loadChildUserChildList()
    {
        $where = [];
        $superior_id = I('get.superior_id');
        $where[0] = "del=0";
        $where[1] = "superiorid = " . $superior_id;
        $i = 2;
        $user_name = I("post.username", "", 'trim');
        if ($user_name <> "") {
            $where[$i] = "(username like '%" . $user_name . "%' or getmemberid(id) like '%" . $user_name . "%')";
            $i++;
        }

        $status = I("post.status", "");
        if ($status <> "") {
            $where[$i] = "`status` = " . $status;
            $i++;
        }

        $user_rengzheng = I("post.userrengzheng", "");
        if ($user_rengzheng <> "") {
            $where[$i] = "authentication = " . $user_rengzheng;
            $i++;
        }

        $user_type = I("post.usertype", "");
        if ($user_type <> "") {
            $where[$i] = "usertype = " . $user_type;
            $i++;
        }

        $start = I("post.start", "");
        $end = I("post.end", "");
        if ($start <> "") {
            $where[$i] = "DATEDIFF('" . $start . "',regdatetime) <= 0";
            $i++;
        }
        if ($end <> "") {
            $where[$i] = "DATEDIFF('" . $end . "',regdatetime) >= 0";
            $i++;
        }

        $count = D('user')->where($where)->count();
        $datalist = D('user')->scope('default_scope')->where($where)->page(I("post.page", "1"), I("post.limit", "10"))->order('id DESC')->select();
        $ReturnArr = [
            'code' => 0,
            'msg' => '数据加载成功',
            'count' => $count,
            'data' => $datalist
        ];
        $this->ajaxReturn($ReturnArr, 'JSON');
    }


    /**
     * 分润记录模块
     */
    //下级用户的交易列表页面
    public function childOrderList()
    {
        $user_id = I('user_id');
        $this->assign('user_id', $user_id);

        //搜索
        $statustype = C('STATUSTYPE');
        unset($statustype[0]);//剔除未支付的状态
        $this->assign('statustype', $statustype);

//        $shangjias = M('payapishangjia')->select();
//        $this->assign('shangjias', $shangjias);
//
//        $payapis = M('payapi')->select();
//        $this->assign('payapis', $payapis);
//
//        $accounts = M('payapiaccount')->select();
//        $this->assign('accounts', $accounts);

        $this->display();
    }

    //加载下级用户的交易记录数据
    public function loadChildOrderList()
    {
        //读取此用户的所有下级的交易记录,并且读交易类型,已成功的记录
        //查询用户的所有下级
        $ids = [session('user_info.id')];
        $user_ids = UserModel::getAllChild($ids);
        //搜索
        $where = [];
        $where[0] = ['userid' => ['in', $user_ids]];
        $i = 1;
        $username = I("post.username", "");
        if ($username <> "") {
            $where[$i] = "(b.username like '%" . $username . "%' or a.memberid like '%" . $username . "%')";
            $i++;
        }
        $userordernumber = I("post.userordernumber", "");
        if ($userordernumber <> "") {
            $where[$i] = "(userordernumber like '%" . $userordernumber . "%')";
            $i++;
        }
        $status = I("post.status", "");
        if ($status <> "") {
            $where[$i] = "a.status = " . $status;
            $i++;
        } else {
            $where[$i] = "a.status > 0";//读取所有已付记录
            $i++;
        }
        $type = I("post.ordertype", "");
        if ($type <> "") {
            $where[$i] = "a.type = " . $type;
            $i++;
        } else {
            $where[$i] = "a.type = 0";//读取所有交易类型数据
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

        //分页
        $ReturnArr = [
            'code' => 0,
            'msg' => '数据加载成功', //响应结果
            'count' => 0, //总页数
            'data' => [
            ]
        ];
        //总页数
        $count = M('orderinfo')->alias('a')->where($where)->join('LEFT JOIN __USER__ b on a.userid = b.id')->count();
        $page = I('post.page');
        $limit = I('post.limit');
        $datalist = M('orderinfo')->alias('a')->where($where)->join('LEFT JOIN __USER__ b on a.userid = b.id')
            ->field('a.*,b.username')->page($page, $limit)->order('orderid desc')->select();

        //查询数据
        $statustype = C('STATUSTYPE');
        $sum_tcmoney = 0;
        $sum_ordermoney = 0;
        foreach ($datalist as $k => $v) {
            $payapiclassid = PayapiModel::getPayapiclassid($v['payapiid']);
            if ($payapiclassid) {
                $datalist[$k]['payapiclassname'] = PayapiclassModel::getPayapiclassid($payapiclassid);
            }
            $datalist[$k]['statusname'] = $statustype[$v['status']];
            //处理订单金额和冻结金额为两位小数点
            $datalist[$k]['new_ordermoney'] = substr($v['ordermoney'], 0, strlen($v['ordermoney']) - 2);
            //读取提成金额
            $tc_money = OrdercommissionModel::getCommissionField($v['sysordernumber'], 'tc_money');
            if ($tc_money > 0) {
                $datalist[$k]['tc_money'] = $tc_money;
            } else {
                $datalist[$k]['tc_money'] = 0;
            }
            $sum_tcmoney += $datalist[$k]['tc_money'];
            $sum_ordermoney += $datalist[$k]['ordermoney'];
        }
        if (!empty($datalist)) {
            $datalist[0]['sum_ordermoney'] = $sum_ordermoney ? sprintf("%.4f", $sum_ordermoney) : 0;
            $datalist[0]['sum_tcmoney'] = $sum_tcmoney > 0 ? sprintf("%.4f", $sum_tcmoney) : 0;
        }


        $ReturnArr['count'] = $count;
        $ReturnArr['data'] = $datalist;
        $this->ajaxReturn($ReturnArr, 'json');
    }

    //操作：查看
    public function seeChildOrderInfo()
    {
        $order = M('order')->where('id=' . I('orderid'))->find();
        $order_info = M('orderinfo')->where('orderid=' . I('orderid'))->find();
        $statustype = C('STATUSTYPE');
        $order_info['username'] = UserModel::getUsername($order_info['userid']);
        $order_info['status'] = $statustype[$order_info['status']];
        $payapiclassid = PayapiModel::getPayapiclassid($order_info['payapiid']);
        if ($payapiclassid) {
            $order_info['payapiclassname'] = PayapiclassModel::getPayapiclassid($payapiclassid);
        }
        $order_info['callbackurl'] = $order['callbackurl'];
        $order_info['notifyurl'] = $order['notifyurl'];
        if ($order_info['type'] == 1) {
            $order_info['type'] = '测试';
        } else {
            $order_info['type'] = '交易';
        }
        //读取用户的提成费率及提成金额
        $order_info['tc_money'] = OrdercommissionModel::getCommissionField($order_info['sysordernumber'], 'tc_money');
        $order_info['tc_feilv'] = OrdercommissionModel::getCommissionField($order_info['sysordernumber'], 'tc_feilv');
        $order_info['tc_feilv'] = sprintf("%.2f", ($order_info['tc_feilv']) * 100) . "%";
        $this->assign('order_info', $order_info);
        $this->display();
    }

    //导出列表
    public function downloadChildOrderList()
    {
        //读取此用户的所有下级的交易记录,并且读交易类型,已成功的记录
        //查询用户的所有下级
        $ids = [session('user_info.id')];
        $user_ids = UserModel::getAllChild($ids);
        //搜索
        $where = [];
        $where[0] = ['userid' => ['in', $user_ids]];
        $i = 2;
        $username = I("post.username", "");
        if ($username <> "") {
            $where[$i] = "(b.username like '%" . $username . "%' or a.memberid like '%" . $username . "%')";
            $i++;
        }
        $userordernumber = I("get.userordernumber", "");
        if ($userordernumber <> "") {
            $where[$i] = "(userordernumber like '%" . $userordernumber . "%')";
            $i++;
        }
        $status = I("get.status", "");
        if ($status <> "") {
            $where[$i] = "a.status = " . $status;
            $i++;
        } else {
            $where[$i] = "a.status > 0";//读取所有已付记录
            $i++;
        }
        $type = I("get.ordertype", "");
        if ($type <> "") {
            $where[$i] = "a.type = " . $type;
            $i++;
        } else {
            $where[$i] = "a.type = 0";//读取所有交易类型数据
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

        //查询数据
        $datalist = M('orderinfo')->alias('a')->where($where)->join('LEFT JOIN __USER__ b on a.userid = b.id')
            ->field('a.*,b.username')->order('orderid desc')->select();
        $statustype = C('STATUSTYPE');
        foreach ($datalist as $k => $v) {
            $datalist[$k]['username'] = UserModel::getUsername($v['userid']);
            $payapiclassid = PayapiModel::getPayapiclassid($v['payapiid']);
            if ($payapiclassid) {
                $datalist[$k]['payapiclassname'] = PayapiclassModel::getPayapiclassid($payapiclassid);
            }
            $datalist[$k]['statusname'] = $statustype[$v['status']];
            //处理订单金额为两位小数点
            $datalist[$k]['new_ordermoney'] = substr($v['ordermoney'], 0, strlen($v['ordermoney']) - 2);
            //读取提成金额
            $tc_money = OrdercommissionModel::getCommissionField($v['sysordernumber'], 'tc_money');
            if ($tc_money > 0) {
                $datalist[$k]['tc_money'] = $tc_money;
            } else {
                $datalist[$k]['tc_money'] = 0;
            }
        }

        $title = '下级用户' . $datalist[0]['username'] . '交易记录表';
        $menu_zh = array('商户号', '用户名', '用户订单号', '通道分类', '订单金额', '提成金额', '订单时间', '成功时间', '状态');
        $menu_en = array('memberid', 'username', 'userordernumber', 'payapiclassname', 'ordermoney', 'tc_money', 'datetime', 'successtime', 'statusname');
        $config = array('RowHeight' => 25, 'Width' => 20);
        $this->addUserOperate("用户导出了下级商户的交易记录");
        DownLoadExcel($title, $menu_zh, $menu_en, $datalist, $config);
    }

}