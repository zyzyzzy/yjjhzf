<?php



namespace User\Controller;

use Think\Controller;

use User\Model\UserloginrecordModel;

use User\Model\UserauthgroupaccessModel;

use User\Model\UserloginerrorModel;

use User\Model\IpaccesslistModel;

use User\Model\UserothersetingModel;

use User\Model\UsersessionidModel;

use User\Model\ChilduserModel;

use User\Model\UserModel;

use User\Model\UserpasswordModel;

use User\Model\UserinfoModel;

class UserLoginController extends Controller
{
    public function __construct()
    {
        parent::__construct();
    }



    //登录页面

    public function index()
    {
        $where = [

                'type' => 2,  //用户后台

                'default' => 1,


            ];

        $info = M('logintemplate')->where($where)->field('temp_name,img_path')->find();

        $tem_path = 'Application/User/View/UserLogin/' . $info['temp_name'] . '.html';

        if ($info['temp_name'] && file_exists($info['img_path']) && file_exists($tem_path)) {
            $name = $info['temp_name'];
        } else {
            $name = 'index4';
        }



        if (session('user_info')) {
            $this->redirect('Index/index');
        }

        $this->display();
    }



    /**

     * 登录操作

     * 如何判断登录账号是主用户还是子账号

     * 2019-1-17 任梦龙：修改判断逻辑

     * 问题：如果是线上的，则获取IP归属地的则方法没有用

     * 最终的目的是判断是主账号登录还是子账号登录，由此得出session值，并依此判断二次验证的问题

     *

     * IP白名单：如果有设置IP白名单，则只允许IP通过。没设置，按照正常逻辑走

     *

     */

    public function doLogin()
    {
        $info = I('post.');

        $ip = getIp();

//        $ip_area = $this->getIpArea();

        $ip_area = getAreaByApi($ip);

        $error_data = [

            'user_id' => 0,

            'child_id' => 0,

            'login_name' => $info['login_name'],

            'login_pwd' => $info['login_pwd'],

//            'error_msg' => '',

            'login_ip' => $ip,

            'login_address' => $ip_area,

            'login_time' => date('Y-m-d H:i:s')

        ];

        //2019-4-12 rml ：判断是否允许登录(全部功能，用户登录前台功能)

        $website = M('website')->field('all_valve,view_valve')->order('id DESC')->find();

        if ($website['all_valve'] != 0) {
            $error_data['error_msg'] = '管理员将全部功能关闭了';

            UserloginerrorModel::addErrorMsg($error_data);

            $this->ajaxReturn(array('status' => 'no', 'msg' => '登录功能已被禁止,请联系管理员'));
        }

        if ($website['view_valve'] != 0) {
            $error_data['error_msg'] = '管理员将用户登录前台功能关闭了';

            UserloginerrorModel::addErrorMsg($error_data);

            $this->ajaxReturn(array('status' => 'no', 'msg' => '登录功能已被禁止,请联系管理员'));
        }

        if (!$this->isBlackIp($ip)) {
            $msg = '该登录IP地址已被限制，请联系管理员！';

            $error_data['error_msg'] = $msg;

            UserloginerrorModel::addErrorMsg($error_data);

            $this->ajaxReturn(array('status' => 'no', 'msg' => $msg));
        }

        if (!($info['login_name'] && $info['login_pwd'] && $info['login_code'])) {
            $this->ajaxReturn(array('status' => 'no', 'msg' => '请输入账号信息'));
        }

        //2019-4-17 rml：修改登录次数限制逻辑

        /************************************************************************/

        $website = M('website')->order('id DESC')->field('login_count,set_time')->find();

        //如果没有设置,则使用默认的登录限制设置

        if ($website['login_count'] == 0) {
            $login_count = C('DEFAULT_COUNT');

            $set_time = C('DEFAULT_TIME');
        } else {
            $login_count = $website['login_count'];

            $set_time = $website['set_time'];
        }



        //获取登录次数及限制时间

//        $login_set = UserothersetingModel::getLoginCount();

//        if (!$login_set) {

//            $login_count = C('LOGIN_COUNT');

//            $set_time = C('SET_TIME');

//            $set_name = $set_time / 60;

//        } else {

//            $login_count = $login_set[0]['login_count'];

//            $set_time = $login_set[0]['set_time'];

//            $set_name = $login_set[0]['set_name'];

//        }



        $find_child = ChilduserModel::getChildUser(['del' => 0, 'child_name' => $info['login_name']]);

        $find_user = UserModel::findUserInfo(['del' => 0, 'username' => $info['login_name']]);

        //2019-1-28 任梦龙：判断登录次数放在最前面，针对同一个ip，计算在login_time 在当前时间和指定时间段之间的登录次数，如果大于等于指定的次数，则禁止登录

        $make_time = time() - (intval($set_time) * 60);

        $where_count = [

            'login_ip' => $ip,

            'login_time' => ['between', [date('Y-m-d H:i:s', $make_time), date('Y-m-d H:i:s', time())]],

        ];

        $allow_count = UserloginerrorModel::getCount($where_count);

        if ($allow_count >= $login_count) {
            $this->ajaxReturn(array('status' => 'no', 'msg' => '您登录次数过于频繁，请' . $set_time . '分钟后再试'));
        }

        /************************************************************************/



        //子账号登录

        if ($find_child) {
            $error_data['user_id'] = $find_child['user_id'];

            $error_data['child_id'] = $find_child['id'];

            if (!$this->check_verify($info['login_code'])) {
                $this->ajaxReturn(array('status' => 'no', 'msg' => '验证码错误'));
            }



            //2019-3-27 任梦龙：用户和子账号的IP白名单是否需要？？？

            $where_ip = [

                'admin_id' => 0,

                'user_id' => $find_child['user_id'],

                'child_id' => $find_child['id'],

            ];



            if (!$this->isAllowIp($ip, $where_ip)) {
                $error_data['error_msg'] = '账户的登录ip[' . $ip . ']已被限制，请联系主用户';

                $this->userLoginError($error_data);

                $this->ajaxReturn(array('status' => 'no', 'msg' => '该账户的登录ip已被限制，请联系主用户'));
            }



            $user = UserModel::findUserInfo('id=' . $find_child['user_id']);

            if ($user['status'] != 2) {
                $error_data['error_msg'] = '账户[' . $find_child['child_name'] . ']的主用户[' . $user['username'] . ']已被禁用，请联系主用户';

                UserloginerrorModel::addErrorMsg($error_data);

                $this->ajaxReturn(array('status' => 'no', 'msg' => '该账户的主用户已被禁用，请联系主用户'));
            }



            if ($find_child['child_pwd'] != md5($info['login_pwd'])) {
                $error_data['error_msg'] = '密码错误';

                UserloginerrorModel::addErrorMsg($error_data);

                $this->ajaxReturn(array('status' => 'no', 'msg' => '密码错误'));
            }



            //判断子账户的状态

            if ($find_child['status'] != 1) {
                $error_data['error_msg'] = '账户[' . $find_child['child_name'] . ']已被禁用，请联系主用户[' . $user['username'] . ']';

                UserloginerrorModel::addErrorMsg($error_data);

                $this->ajaxReturn(array('status' => 'no', 'msg' => '该账户已被禁用，请联系主用户'));
            }

            //如果一个没有任何权限的账号登录时，直接拦截不让其进入

            $child_menus = UserauthgroupaccessModel::getUserRules($find_child['user_id'], $find_child['id']);

            if (!$child_menus) {
                $error_data['error_msg'] = '账号[' . $find_child['child_name'] . ']没有任何权限，请联系主用户';

                UserloginerrorModel::addErrorMsg($error_data);

                $this->ajaxReturn(array('status' => 'no', 'msg' => '该账号没有任何权限，请联系主用户[' . $user['username'] . ']'));
            }



            //添加子账号的登录记录

            $child_record = [

                'userid' => $find_child['user_id'],

                'child_id' => $find_child['id'],

                'logindatetime' => date('Y-m-d H:i:s'),

                'loginip' => $ip,

                'loginaddress' => $ip_area

            ];

            UserloginrecordModel::addUserRecrod($child_record);



            //将session_id 的值存入数据库中,用于做踢下线处理

            $session_id = session_id();

            $where_session = [

                'admin_id' => 0,

                'user_id' => 0,

                'child_id' => $find_child['id'],

            ];

            $session_count = UsersessionidModel::getSessionCount($where_session);

            if ($session_count) {
                UsersessionidModel::editSessionid($where_session, ['session_id' => $session_id]);
            } else {
                $data = [

                    'admin_id' => 0,

                    'user_id' => 0,

                    'child_id' => $find_child['id'],

                    'session_id' => $session_id,

                ];

                UsersessionidModel::addSessionId($data);
            }



            //修改登录时间

            ChilduserModel::saveLoginTime($find_child['id']);

            //存入session(这三个后面需要删除)

            //2019-4-22 rml:用原生的更好

            /* $_SESSION['child_id'] = $find_child['id'];

             $_SESSION['user_name'] = $find_child['child_name'];

             $_SESSION['user_id'] = $find_child['user_id'];*/



            session('child_id', $find_child['id']);

            session('user_name', $find_child['child_name']);

            session('user_id', $find_child['user_id']);



            //存入真正需要的session

            unset($find_child['child_pwd']);

            /* $_SESSION['user_info'] = $user;

             $_SESSION['child_info'] = $find_child;

             $_SESSION['user_time'] = time();*/



            session('user_info', $user);

            session('child_info', $find_child);

            session('user_time', time());

            $this->ajaxReturn(array('status' => 'ok', 'msg' => '登录成功,即将跳转'));
        }



        //主账号登录

        if ($find_user) {
            $error_data['user_id'] = $find_user['id'];

            if (!$this->check_verify($info['login_code'])) {
                $this->ajaxReturn(array('status' => 'no', 'msg' => '验证码错误'));
            }

            //2019-3-27 任梦龙：用户和子账号的IP白名单是否需要？？？



            $where_ip = [

                'admin_id' => 0,

                'user_id' => $find_user['id'],

                'child_id' => 0,

            ];

            if (!$this->isAllowIp($ip, $where_ip)) {
                $error_data['error_msg'] = '账户的登录ip[' . $ip . ']已被限制，请联系管理员';

                $this->userLoginError($error_data);

                $this->ajaxReturn(array('status' => 'no', 'msg' => '该账户的登录ip已被限制，请联系管理员'));
            }



//            $user_pwd = M('userpassword')->where('userid = ' . $find_user['id'])->getField('loginpassword');

            $user_pwd = UserpasswordModel::getLoginPwd($find_user['id']);

            if ($user_pwd != md5($info['login_pwd'])) {
                $error_data['error_msg'] = '密码错误';

                UserloginerrorModel::addErrorMsg($error_data);

                $this->ajaxReturn(array('status' => 'no', 'msg' => '密码错误'));
            }

            //用户状态的判断

            if ($find_user['status'] == 1) {
                $error_data['error_msg'] = '账户[' . $find_user['username'] . ']还未激活，请联系管理员';

                UserloginerrorModel::addErrorMsg($error_data);

                $this->ajaxReturn(array('status' => 'no', 'msg' => '该账户还未激活，请联系管理员'));
            }

            if ($find_user['status'] == 3) {
                $error_data['error_msg'] = '账户[' . $find_user['username'] . ']已被禁用，请联系管理员';

                UserloginerrorModel::addErrorMsg($error_data);

                $this->ajaxReturn(array('status' => 'no', 'msg' => '该账户已被禁用，请联系管理员'));
            }



            //添加主账号的登陆记录

            $data = [

                'userid' => $find_user['id'],

                'logindatetime' => date('Y-m-d H:i:s'),

                'loginip' => $ip,

                'loginaddress' => $ip_area

            ];

            UserloginrecordModel::addUserRecrod($data);



            //2将session_id 的值存入数据库中,用于做踢下线处理

            $session_id = session_id();

            $where_session = [

                'admin_id' => 0,

                'user_id' => $find_user['id'],

                'child_id' => 0,

            ];

            $session_count = UsersessionidModel::getSessionCount($where_session);

            if ($session_count) {
                $data = ['session_id' => $session_id];

                UsersessionidModel::editSessionid($where_session, $data);
            } else {
                $data = [

                    'admin_id' => 0,

                    'user_id' => $find_user['id'],

                    'child_id' => 0,

                    'session_id' => $session_id,

                ];

                UsersessionidModel::addSessionId($data);
            }

            //存入session(这三个后面需要删除)

            //2019-4-22 rml:用原生的更好

            /*$_SESSION['user_id'] = $find_user['id'];

            $_SESSION['user_name'] = $find_user['username'];

            $_SESSION['child_id'] = '';*/



            session('user_id', $find_user['id']);

            session('user_name', $find_user['username']);

            session('child_id', '');



            //实际需要的

            /* $_SESSION['user_info'] = $find_user;

             $_SESSION['child_info'] = '';

             $_SESSION['user_time'] = time();*/



            session('user_info', $find_user);

            session('child_info', '');

            session('user_time', time());

            $this->ajaxReturn(array('status' => 'ok', 'msg' => '登录成功,即将跳转'));
        }

        $error_data['error_msg'] = '用户[' . $info['login_name'] . ']不存在';

        UserloginerrorModel::addErrorMsg($error_data);

        $this->ajaxReturn(array('status' => 'no', 'msg' => '该用户不存在'));
    }



    /**

     * 退出登录

     */

    public function logout()
    {
        session('user_info', null);

        $this->ajaxReturn(['msg' => '退出成功，即将跳转！', 'status' => 'ok']);
    }



    /**

     * 生成验证码

     */

    public function verify()
    {
        ob_end_clean();

        $verify = new \Think\Verify();

        $verify->length = 4;

        $verify->expire = 60;  //验证码的有效期（60秒）

        $verify->entry();
    }



    /**

     * 验证码检测

     */

    public function check_verify($code, $id = '')
    {
        $verify = new \Think\Verify();

        $verify->fontSize = 60;

        $verify->length = 4;

        $verify->expire = 60;

        $verify->useNoise = false;

        return $verify->check($code, $id);
    }



    /**

     * 2019-1-18 任梦龙：封装获取ip归属地

     */

    public function getIpArea()
    {
        $Ip = new \Org\Net\IpLocation('UTFWry.dat'); // 实例化类 参数表示IP地址库文件,tp自带获取地址方法

        $area = $Ip->getlocation(); // 获取某个IP地址所在的位置

        if ($area) {
            $area = $this->arrayUtf8($area);

            $ip_area = $area['country'] . '-' . $area['area'];
        } else {
            $ip_area = '';
        }

        return $ip_area;
    }



    /**

     * 用户注册页面

     */

    public function reg()
    {
        $this->display();
    }



    /**

     * 注册操作

     */

    //2019-4-8 任梦龙：修改

    public function doReg()
    {
        $info = I('post.', '', 'trim');

        if (!($info['reg_name'] && $info['reg_pwd'])) {
            $this->ajaxReturn(['status' => 'no', 'msg' => '数据不能为空！']);
        }

        $count = M('user')->where(['username' => $info['reg_name']])->count();

        if ($count > 0) {
            $this->ajaxReturn(['status' => 'no', 'msg' => '用户名已存在！']);
        }

        if ($info['reg_pwd'] != $info['pwd_confirm']) {
            $this->ajaxReturn(['status' => 'no', 'msg' => '密码不一致！']);
        }

        //判断验证码

        if (!$info['reg_code']) {
            $this->ajaxReturn(['status' => 'no', 'msg' => '验证码不能为空！']);
        }

        if (!$this->check_verify($info['reg_code'])) {
            $this->ajaxReturn(['status' => 'no', 'msg' => '验证码错误！']);
        }

        //判断手机号

        /*if($info['reg_phone_code'] != session('code')){

            $this->ajaxReturn(['status' => 'no', 'msg' => '手机验证码错误！']);

        }*/



        //判断当前的邀请码是否可以使用

        if (!$info['reg_invite']) {
            $this->ajaxReturn(['status' => 'no', 'msg' => '邀请码不得为空！']);
        }

        $invite_find = M('userinvitecode')->where(['del' => 0, 'status' => 1, 'invite_code' => $info['reg_invite']])->find();

        if (!$invite_find) {
            $this->ajaxReturn(['status' => 'no', 'msg' => '此邀请码不能使用，请重新索取！']);
        }

        //将数据插入用户表

        $user_data = [

            'username' => $info['reg_name'],

            'usertype' => $invite_find['reg_type'],

            'status' => 1,

            'authentication' => 1,  /*未认证*/

            'superiorid' => $invite_find['pid'] ? $invite_find['pid'] : 0,

            'regdatetime' => date('Y-m-d H:i:s'),

            'usercode' => $this->createUserCode(),

        ];

        //默认未激活，但是如果管理员有设置用户注册的默认状态，则使用设置的

        $website = M('website')->order('id DESC')->limit(1)->find();

        if ($website['reg_status']) {
            $user_data['status'] = $website['reg_status'];
        }

        $user_id = M('user')->add($user_data);

        if (!$user_id) {
            $this->ajaxReturn(['status' => 'no', 'msg' => '注册失败,请重试！']);
        }



        //将邀请码的状态改为已使用

        $save_invite = [

            'status' => 3,

            'user_id' => $user_id,

            'use_time' => date('Y-m-d H:i:s'),

        ];

        M('userinvitecode')->where('id = ' . $invite_find['id'])->save($save_invite);

        //将注册的密码存入用户密码表

        $data_pwd = [

            'userid' => $user_id,

            'loginpassword' => md5($info['reg_pwd']),

            'manage_pwd' => md5('123456')

        ];

        M('userpassword')->add($data_pwd);

        M('secretkey')->add(['userid' => $user_id]);

        M('usermoney')->add(['userid' => $user_id]);  /*2019-7-19 rml:同时向usermoney表插入记录*/

        //将session_id 的值存入数据库中

//        $session_id = session_id();

//        $where_session = [

//            'admin_id' => 0,

//            'user_id' => $user_id,

//            'child_id' => 0,

//        ];

//        $session_count = UsersessionidModel::getSessionCount($where_session);

//        if ($session_count) {

//            $data = ['session_id' => $session_id];

//            UsersessionidModel::editSessionid($where_session, $data);

//        } else {

//            $data = [

//                'admin_id' => 0,

//                'user_id' => $user_id,

//                'child_id' => 0,

//                'session_id' => $session_id,

//            ];

//            UsersessionidModel::addSessionId($data);

//        }



//        $user_info = M('user')->where(['id' => $user_id])->find();

//        //将用户id信息存入session

//        session('user_id', $user_id);

//        session('child_id', '');

//        session('user_name', $info['reg_name']);

//        $_SESSION['user_info'] = $user_info;

//        $_SESSION['child_info'] = '';

//        $this->sendEmail(); //如果要发送邮件，则调用

        $this->ajaxReturn(array('status' => 'ok', 'msg' => '注册成功'));
    }



    //生成用户标识码 2019-01-07汪桂芳添加

    public function createUserCode()
    {
        $user_code = random_str(32);

        $count = M('user')->where('usercode="' . $user_code . '"')->count();

        if ($count > 0) {
            $this->createUserCode();
        }

        return $user_code;
    }



    //发送短信

    public function sendPhone()
    {
        $phone = I('post.phone');

        //http://v.juhe.cn/sms/send?mobile=手机号码&tpl_id=短信模板ID&tpl_value=%23code%23%3D654654&key=

        //tpl_id=124530   mobile=$phone  key=28d9a48275b2d8d280de42a62a6e15cb

        $url = 'http://v.juhe.cn/sms/send?mobile=' . $phone . '&tpl_id=124530&key=28d9a48275b2d8d280de42a62a6e15cb&tpl_value=';

        $code = rand(100000, 999999);

        $_SESSION['code'] = $code;

        $tpl_value = '#code#=' . $code;

        $tpl_value = urlenCode($tpl_value);

        $url = $url . $tpl_value;

        $res = $this->juheCurl($url);

        echo $res;

        exit;
    }



    /**

     * 请求接口返回内容

     * @param  string $url [请求的URL地址]

     * @param  string $params [请求的参数]

     * @param  int $ipost [是否采用POST形式]

     * @return  string

     */

    public function juheCurl($url, $params = false, $ispost = 0)
    {
        $httpInfo = array();

        $ch = curl_init();



        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);

        curl_setopt($ch, CURLOPT_USERAGENT, 'JuheData');

        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60);

        curl_setopt($ch, CURLOPT_TIMEOUT, 60);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

        if ($ispost) {
            curl_setopt($ch, CURLOPT_POST, true);

            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);

            curl_setopt($ch, CURLOPT_URL, $url);
        } else {
            if ($params) {
                curl_setopt($ch, CURLOPT_URL, $url . '?' . $params);
            } else {
                curl_setopt($ch, CURLOPT_URL, $url);
            }
        }

        $response = curl_exec($ch);

        if ($response === false) {

            //echo "cURL Error: " . curl_error($ch);

            return false;
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        $httpInfo = array_merge($httpInfo, curl_getinfo($ch));

        curl_close($ch);

        return $response;
    }



    /**

     * 发送邮件接口

     */

    public function sendEmail()
    {
        $tjurl = 'http://www.e_pay.com/Api/Email/createEmail'; //邮件接口路径

        $post_arr = [

            'version' => '1.0.0',

            'merid' => '201901021007',  //商户编号：由平台分配

//            'merorderno' => $this->createMerorderno(15),  //商户订单号：邮件流水号

            'merorderno' => '033635429067546',  //商户订单号：邮件流水号

            'notifyurl' => 'http://www.amnpay.com/',

//            'createtime' => date('Y-m-d H:i:s'),

            'createtime' => '2019-01-11 11:13:08',

            'addressee_email' => '1453191896@qq.com',  //接收人地址

            'title' => '你好，客户',

            'body' => '这是测试邮件接口数据',

//            'content' => '1.0.0',

//            'sign' => '1.0.0'

        ];

        M('emailapi')->add($post_arr);  //将数据存储表中

        $post_arr['content'] = base64_encode(json_encode($post_arr));

        $signStr = $this->getSignStr($post_arr);

        $res = 'cer/rsa_private_key.pem'; //私钥文件路径

        $res = file_get_contents($res);

        openssl_sign($signStr, $sign, $res, OPENSSL_ALGO_SHA256);  // 私钥加密

        $sign = base64_encode($sign);

        $post_arr['sign'] = $sign;

        $this->setHtml($tjurl, $post_arr);
    }



    //字典排序

    public function getSignStr($data)
    {
        ksort($data);

        $str = "";

        foreach ($data as $key => $value) {
            if ($value != '' && $key != 'sign') {
                $str .= $key . '=' . $value . '&';
            }
        }

        return trim($str, '&');
    }



    //生成请求发送表单

    public function setHtml($tjurl, $arraystr, $test = false, $method = "post")
    {
        $str = '<form id="Form1" name="Form1" method="post" action="' . $tjurl . '">';

        foreach ($arraystr as $key => $val) {
            $str = $str . "<input type='hidden' name='" . $key . "' value='" . $val . "'>";
        }

        $str = $str . '</form>';

        $str = $str . '<script>';

        $str = $str . 'document.Form1.submit();';

        $str = $str . '</script>';

        exit($str);
    }



    //生成流水号

    public function createMerorderno($leng)
    {
        $merorderno = randpw($leng, 'NUMBER');

        //如果存在相同的系统订单号则直接报错

        $num = M("emailapi")->where("merorderno='" . $merorderno . "'")->count();

        if ($num >= 1) {
            return false;
        }

        return $merorderno;
    }



    /**

     * 2019-1-17 任梦龙：数组转码

     */

    public function arrayUtf8($array)
    {
        array_walk($array, function (&$value) {
            $value = iconv('gbk', 'utf-8', $value);
        });



        return $array;
    }



    /**

     * 2019-1-23 任梦龙：记录用户登录时的错误信息

     */

    public function userLoginError($error_data)
    {
        UserloginerrorModel::addErrorMsg($error_data);
    }



    /**

     * 2019-1-24 任梦龙：判断登录ip的方法

     */

    public function isAllowIp($ip, $where)
    {
        $ip_arr = IpaccesslistModel::getIpList($where);

        if ($ip_arr) {
            if (in_array($ip, $ip_arr)) {
                return true;
            }

            return false;
        }

        return true;
    }



    //2019-1-28 任梦龙：删除封装判断登录次数的方法



    /**

     * 2019-3-11 任梦龙：判断登录IP黑名单

     */

    public function isBlackIp($ip)
    {
        $count = M('blackip')->count();

        if ($count) {
            $find = M('blackip')->where("ip='" . $ip . "'")->field('id')->find();

            if ($find) {
                return false;
            }

            return true;
        }

        return true;
    }



    //2019-4-1 任梦龙：找回密码页面

    public function resetPwd()
    {
        $this->display();
    }



    //2019-4-1 任梦龙：点击表单提交按钮时，发送邮件

    //2019-4-2 任梦龙：修改

    public function sendMail()
    {
        $email = I('post.email', '', 'trim');

        $user_id = UserinfoModel::getUserid($email);

        if (!$user_id) {
            $this->ajaxReturn(['status' => 'no', 'msg' => '该邮箱尚未注册！']);
        }

        //将发送邮箱的时间记录,便于在点击邮箱链接时，验证链接失效时间

        UserinfoModel::editEamiltime($user_id);

        //组合验证码

        $token = $this->getToken($user_id);

        //构造URL

        $url = "https://" . $_SERVER['HTTP_HOST'] . U('user/UserLogin/findPwd') . "?email=" . $email . "&token=" . $token;

        $time = date('Y-m-d H:i');

        $config = [

            'smtp' => 'smtp.qq.com',

            'dk' => '465',

            'email' => '1453191896@qq.com',

            'em_code' => 'zxkqokahjlxfhadf',

            'user_name' => '武汉爱码农网络科技公司',

            'receive_email' => $email,

            'title' => '找回密码',

            'content' => "亲爱的" . $email . "：<br/>您在" . $time . "提交了找回密码请求。请点击下面的链接重置密码（链接24小时内有效）。<br/><a href='" . $url . "'target='_blank'>" . $url . "</a>"

        ];

        $result = sendmail($config);

        //邮件发送成功

        if ($result) {
            $msg = '系统已向您的邮箱发送了一封邮件';

            $this->ajaxReturn(['status' => 'ok', 'msg' => $msg]);
        } else {
            $this->ajaxReturn(['status' => 'no', 'msg' => '邮箱发送失败,请重试！']);
        }
    }



    //找回密码页面

    //2019-4-2 任梦龙：修改

    public function findPwd()
    {
        $token = stripslashes(trim($_GET['token']));

        $email = stripslashes(trim($_GET['email']));

        $user_id = UserinfoModel::getUserid($email);

        if (!$user_id) {
            exit('错误的链接！');
        }

        //验证邮箱链接失效时间(24小时)（当前时间-记录时间）

        $email_time = UserinfoModel::getEamiltime($user_id);

        $start_time = strtotime($email_time);

        $left_time = time() - $start_time;

        if ($left_time >= 86400) {
            exit('链接已失效！');
        }

        $re_token = $this->getToken($user_id);

        //需要再次通过邮箱获取用户信息和用户密码

        if ($re_token == $token) {
            $this->assign('token', $token);

            $this->assign('email', $email);

            $this->display();
        } else {
            exit('无效的链接！');
        }
    }



    //确定重置密码

    public function editLoginpwd()
    {
        $new_pwd = I('post.pwd', '', 'trim');

        $re_pwd = I('post.repwd', '', 'trim');

        $email = I('post.email');

        $token = I('post.token');

        $user_id = UserinfoModel::getUserid($email);

        if (!$user_id) {
            $this->ajaxReturn(['status' => 'no', 'msg' => '请勿非法操作']);
        }

        $old_token = $this->getToken($user_id);

        if ($token != $old_token) {
            $this->ajaxReturn(['status' => 'no', 'msg' => '请勿非法操作']);
        }

        //验证密码的合法性

        if (!$new_pwd) {
            $this->ajaxReturn(['status' => 'no', 'msg' => '请输入新密码']);
        }

        $old_user_pwd = UserpasswordModel::getLoginPwd($user_id);

        if (MD5($new_pwd) == $old_user_pwd) {
            $this->ajaxReturn(['status' => 'no', 'msg' => '密码与原密码一致，请确认']);
        }

        $pwd_rule = '/^[\w\d]{6,16}$/';

        if (!preg_match($pwd_rule, $new_pwd)) {
            $this->ajaxReturn(['status' => 'no', 'msg' => '密码长度在6-16个字符之间(英文,数字,下划线组成)']);
        }

        if (!$re_pwd) {
            $this->ajaxReturn(['status' => 'no', 'msg' => '请确认密码']);
        }

        if ($new_pwd != $re_pwd) {
            $this->ajaxReturn(['status' => 'no', 'msg' => '密码不一致,请重新输入']);
        }

        $user_name = UserModel::getUsername($user_id);

        $res = UserpasswordModel::editUserpwd($user_id, ['loginpassword' => md5($new_pwd)]);

        $msg = '用户[' . $user_name . ']重置了自己的登录密码:';

        if ($res) {
            $this->addUserOperate($user_id, $msg . '修改成功');

            $this->ajaxReturn(['status' => 'ok', 'msg' => '修改成功']);
        }

        $this->addUserOperate($user_id, $msg . '修改失败');

        $this->ajaxReturn(['status' => 'no', 'msg' => '修改失败']);
    }



    public function addUserOperate($user_id, $content)
    {
        $data = [

            'userid' => $user_id,

            'userip' => getIp(),

            'operatedatetime' => date('Y-m-d H:i:s'),

            'content' => $content,

            'child_id' => 0,  //2019-3-20 任梦龙：添加子账号id，便于区分

        ];

        D('useroperaterecord')->add($data);
    }



    //2019-4-2 任梦龙：封装获取TOKEN

    public function getToken($user_id)
    {
        $user_name = UserModel::getUsername($user_id);

        $user_pwd = UserpasswordModel::getLoginPwd($user_id);

        $re_token = md5($user_id . $user_name . $user_pwd);

        return $re_token;
    }
}
