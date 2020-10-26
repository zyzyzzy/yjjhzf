<?php



namespace User\Controller;

use Admin\Model\StatisticModel;

use Admin\Model\SuccessrateModel;

use Daifu\Model\UsermoneyModel;

use Pay\Model\OrderModel;

use User\Model\GooglecodeModel;

use User\Model\SecretkeyModel;

use User\Model\SettleModel;

use User\Model\UserauthgroupaccessModel;

use User\Model\UserloginrecordModel;

use User\Model\UserModel;

use User\Model\UserpasswordModel;

use User\Model\ChilduserModel;

use User\Model\UserpayapiclassModel;

class IndexController extends UserCommonController
{
    public function __construct()
    {
        parent::__construct();
    }



    //用户中心首页

    public function index()
    {

        //2019-4-12 rml：修改

        //如果是主账号登录，

        if (empty(session('child_info'))) {
            $user_name = session('user_info.username');

            //直接获取所有权限，然后树状递归

            if (!file_exists(C('USER_MENU_PATH'))) {
                mkdir(C('USER_MENU_PATH'), '0777', true);
            }

            $main_file_name = hash('sha1', 'usermenu');

            $main_menu_path = C('USER_MENU_PATH') . $main_file_name . '.json';

            //如果存在主用户的菜单文件，则去读文件中的菜单数据，如果不存在，则直接去读取菜单表的数据，生成菜单文件

            if (file_exists($main_menu_path)) {
                $menu = file_get_contents($main_menu_path);

                $menujson = json_decode($menu, true);
            } else {
                $menujson = UserauthgroupaccessModel::getAllUserMenu();

                file_put_contents($main_menu_path, json_encode($menujson));
            }

            if (session('user_info.usertype') != 2) {
                unset($menujson['代理专区']);
            }
        } else {
            $user_name = session('child_info.child_name');

            //如果是子账号登录：查找该用户下的子账户的权限,树状递归出菜单

            $child_menu_path = $this->getUserMenuPath(session('user_info.id'), session('child_info.id'));

            //如果存在子账号的菜单文件，则直接获取文件的内容，如果没有，则需要从数据库中取菜单数据

            //如果是子账号登录,则先去查找子账号的菜单文件，没有则去数据库中查找数据，没有则为空

            if (file_exists($child_menu_path)) {
                $menujsons = file_get_contents($child_menu_path);

                $menujson = json_decode($menujsons, true);
            } else {
                $child_menu_json = M('childuser')->where(['id' => session('child_info.id')])->getField('menu_json');

                if ($child_menu_json) {
                    file_put_contents($child_menu_path, $child_menu_json);

                    $menujson = json_decode($child_menu_json, true);
                } else {
                    $menujson = '';
                }
            }
        }

//        print_r($menujson);die;

        $this->assign("user_name", $user_name);



//        $menujson = C('USER_MENU_JSON');

        $this->assign("menujson", $menujson);



        $user_id = session('user_info.id');

        $this->assign("user_id", $user_id);



        $child_id = session('child_info.id');

        $this->assign("child_id", $child_id);



        //查询用户码

        $user_code = M('user')->where(['id' => session('user_info.id')])->getField('usercode');

        $this->assign("user_code", $user_code);



        //判断用户自助收银状态

        $selfcash_status = UserModel::getUserSelfcashStatus($user_id);

        $this->assign('selfcash_status', $selfcash_status);



        //判断用户是否开启二次验证

        $google_code = $this->getUserGoogle();

        $this->assign('google_code', $google_code);



        //判断有没有公告

        //2019-5-6 rml：判断用户有没有新公告

        $exist = 0;  //默认不存在公告

        $all_notice = M('usernotice')->where(['user_id' => 0, 'del' => 0])->order('id DESC')->limit(1)->find();  //获取最近一条所有用户的公告



        $user_notice = M('usernotice')->where(['user_id' => session('user_info.id'), 'del' => 0])->order('id DESC')->limit(1)->find();  //获取最近一条用户的公告



        $read_notice = M('readnotice')->where(['user_id' => session('user_info.id'), 'status' => 1])->getField('notice_id', true);  //获取该用户已经查看过的公告id组

        //如果所有的和用户的同时存在，则去比较时间

        $notice = '';  //记录真正的公告

        if ($all_notice && $user_notice) {
            if (strtotime($all_notice['date_time']) > strtotime($user_notice['date_time'])) {
                $notice = $all_notice;
            } else {
                $notice = $user_notice;
            }
        }

        //如果只存在所有的

        if ($all_notice && !$user_notice) {
            $notice = $all_notice;
        }

        //如果只存在用户的

        if (!$all_notice && $user_notice) {
            $notice = $user_notice;
        }

        //只有存在公告

        //获取到了公告,然后在查看表中对比该公告是否已经被查看了,如果查看了，则没有

        if ($notice) {
            if (!in_array($notice['id'], $read_notice)) {
                $exist = 1;

                $this->assign('notice_id', $notice['id']);
            }
        }

        $this->assign('exist', $exist);



        $this->display();
    }





    public function welcome()
    {
        $userid = session('user_info.id');

        //用户基本信息

        $user = UserModel::getUserInfo($userid);

        //用户的商户信息

        $secretkey = SecretkeyModel::userKeyFind($userid);

        //用户等登录信息

        $userLatestrecord = UserloginrecordModel::getLatestrecord($userid);



        //用户余额

        $usermoney = UsermoneyModel::getInfo($userid);

        //今日交易金额

        $today_sum_money = StatisticModel::getTodaySumByUser($userid) ?: "0";

        //dump($today_sum_money);

        //成功率

        $today_successrate = SuccessrateModel::getUserSuccessrateByDay($userid);

        //最新5笔交易记录

        $latestOrder = OrderModel::getUserLatestOrder($userid, 5);

        //用户可用通道以及费率

//        $userpayapiclass = UserpayapiclassModel::getUserPayapis($userid);



        $userpayapiclass = UserpayapiclassModel::getUserpayapiList($userid);

        foreach ($userpayapiclass as $key => $val) {
            $userpayapiclass[$key]['order_feilv'] = $val['user_feilv'] ? $val['user_feilv'] : $val['class_feilv'];
        }



        //dump($userpayapiclass);

        //最新5条结算记录

        $latestSettle = SettleModel::getUserLatestSettle($userid, 5);



        $this->assign('user', $user);

        $this->assign('secretkey', $secretkey);

        $this->assign('userLatestrecord', $userLatestrecord);

        $this->assign('usermoney', $usermoney);

        $this->assign('today_sum_money', $today_sum_money);

        $this->assign('today_successrate', $today_successrate);

        $this->assign('latestOrder', $latestOrder);

        $this->assign('userpayapiclass', $userpayapiclass);

        $this->assign('latestSettle', $latestSettle);

        $this->display();
    }



    //2019-3-29 任梦龙：用户首页显示用户密钥页面

    public function secretkeyInfo()
    {
        $code_type = $this->getCodeType();

        $this->assign('code_type', $code_type);

        $this->display();
    }





    //2019-3-29 任梦龙：在用户首页验证用户密钥

    public function verfiyUserSecret()
    {
        $code_type = I('post.code_type', 0, 'intval');   //验证类型

        $verfiy_code = I('post.verfiy_code', '', 'trim');  //验证码

        $this->verifyEmpty($verfiy_code);   //判断空值

        $return_res = $this->returnCodeRes($verfiy_code, $code_type);

        if ($return_res) {
            $info = SecretkeyModel::userKeyFind(session('user_info.id'));

            $info['username'] = session('user_info.username');

            $this->ajaxReturn(['status' => 'ok', 'msg' => '验证成功', 'info' => $info]);
        }

        $this->ajaxReturn(['status' => 'no', 'msg' => '验证码错误,请重试']);
    }



//    public function secretkeyInfo()

//    {

//        $userid = session('user_info.id');

//        $secretkeyInfo = SecretkeyModel::userKeyFind($userid);

//        $this->assign('secretkeyInfo',$secretkeyInfo);

//        $this->assign('username',session('user_info.username'));

//        $this->display();

//    }





    /**

     * 验证用户输入的验证码

     */

    public function verifyCode()
    {
        $code = I('post.code', '', 'trim');

        if (!empty(session('child_info'))) {
            $type = 'child';

            $login_id = session('child_info.id');
        } else {
            $type = 'user';

            $login_id = session('user_info.id');
        }

        $secret = GooglecodeModel::getSecret($type, $login_id);  //获取二次密钥

        $res = verifcode_googlecode($secret, $code);   //验证二次验证码正确性

        $msg = '开启谷歌验证功能时:';

        if ($res) {
            GooglecodeModel::saveStatus($type, $login_id);  //修改状态

            $this->addUserOperate($msg . '验证成功');

            $this->ajaxReturn(['status' => 'ok', 'msg' => '验证成功']);
        }

        $this->addUserOperate($msg . '验证码错误');

        $this->ajaxReturn(['status' => 'no', 'msg' => '验证码错误,请重新输入']);
    }



    //当用户登录，需要开启谷歌验证时，需要先验证他的登录密码和管理密码

    public function verifyPwd()
    {
        $login_pwd = I('post.login_pwd', '', 'trim');  //登录密码

        $manage_pwd = I('post.manage_pwd', '', 'trim');  //管理密码

        if (!$login_pwd) {
            $this->ajaxReturn(['status' => 'no', 'msg' => '请先输入登录密码']);
        }

        if (!$manage_pwd) {
            $this->ajaxReturn(['status' => 'no', 'msg' => '请输入管理密码']);
        }

        //判断主用户还是子账户登录

        if (empty(session('child_info'))) {
            $type = 'user';

            $name = '主用户:';

            $login_id = session('user_info.id');

            $res_login = UserpasswordModel::verifyLoginpwd($login_id, $login_pwd);

            $res_manage = UserpasswordModel::verifyManagepwd($login_id, $manage_pwd);

            $user_name = UserModel::getUsername($login_id);
        } else {
            $type = 'child';

            $name = '子账户:';

            $login_id = session('child_info.id');

            $res_login = ChilduserModel::verifyLoginpwd($login_id, $login_pwd);

            $res_manage = ChilduserModel::verifyManagepwd($login_id, $manage_pwd);

            $user_name = ChilduserModel::getChildName($login_id);
        }

        if (!$res_login) {
            $this->ajaxReturn(['status' => 'no', 'msg' => '登录密码错误']);
        }

        if (!$res_manage) {
            $this->ajaxReturn(['status' => 'no', 'msg' => '管理密码错误']);
        }

        $secret = GooglecodeModel::getSecret($type, $login_id);

        $src = create_googlecode_qr($name . $user_name, $secret);

        $this->ajaxReturn(['status' => 'ok', 'msg' => '验证成功', 'src' => $src]);
    }



    //用户按时段统计成功率

    public function getUserTodaySuccessrate()
    {
        $successrate = SuccessrateModel::getUserTodaySuccessrate(session('user_info.id'));

        $h = ['00', '01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12', '13', '14', '15', '16', '17', '18', '19', '20', '21', '22', '23'];



        $h = array_slice($h, 0, intval(date('H')) + 1);

        $data = [];

        foreach ($h as $item) {
            $data['h'][] = $item;

            if (in_array($item, array_keys($successrate))) {
                $data['rate'][] = sprintf('%.4f', $successrate[$item]['successcount'] / $successrate[$item]['count']) * 100;
            } else {
                $data['rate'][] = 0;
            }
        }

        $this->ajaxReturn([

            'status' => "success",

            "data" => $data,

            "msg" => "",

        ], "json");
    }



    //获取用户最近七天的成功率

    public function getUserLatelySuccessrate()
    {
        $date = getLatestDate();

        $data = [];



        foreach ($date as $key => $value) {
            $data['date'][] = $value['m'] . "/" . $value['d'];

            $data['rate'][] = SuccessrateModel::getUserSuccessrateByDay(session("user_id"), $value['d'], $value['m'], $value['y']);
        }

        $this->ajaxReturn([

            'status' => "success",

            "data" => $data,

            "msg" => "",

        ], "json");
    }



    //查看公告

    //2019-5-6 rml：优化：当点击查看新公告时，同时将这条公告修改为已读

    public function viewNotice()
    {
        $notice_id = I('get.noticeid');

        M('readnotice')->add([

            'user_id' => session('user_info.id'),

            'notice_id' => $notice_id,

            'status' => 1,

            'read_time' => date('Y-m-d H:i:s'),

        ]);

        $notice = M('usernotice')->find($notice_id);

        $this->assign('notice', $notice['notice']);

        $this->display();
    }



    //验证遮罩层的验证码

    public function verifyCodeType()
    {
        $code_type = I('post.code_type', 0, 'intval');   //验证类型

        $verfiy_code = I('post.verfiy_code', '', 'trim');  //验证码

        $this->checkLayerCode($verfiy_code, $code_type);
    }



    //用户最近充值总金额统计

    public function getUserLastSumAmount()
    {
        $user_id = session('user_info.id');

        $data = StatisticModel::getUserLastSumAmount($user_id);

        $this->ajaxReturn([

            'status' => "success",

            "data" => $data,

            "msg" => "",

        ], "json");
    }
}
