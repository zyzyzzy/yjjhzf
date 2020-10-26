<?php

/*

 * 后台登陆入口控制器

 * user 任梦龙

 * time 2018/11/16 23:50

 */



namespace Admin\Controller;

use Think\Controller;

use Admin\Model\AdminuserModel;

use Admin\Model\UsersessionidModel;

use Admin\Model\AdminloginerrorModel;

use Admin\Model\LogintemplateModel;

use Admin\Model\BlackipModel;

class AdminLoginController extends Controller
{

    /**

     * 登录页面显示

     */

    public function index()
    {

        //修改管理后台登录页面模板,查询是否有设置默认模板，有则使用默认模板页面，没有则直接给一个固定的页面

        $where = [

            'type' => 1,  //管理后台

            'default' => 1,  //默认模板

//            'del' => 0,  //删除状态

        ];

        $info = LogintemplateModel::getTempName($where);

        $tem_path = 'Application/Admin/View/AdminLogin/' . $info['temp_name'] . '.html';

        if ($info['temp_name'] && file_exists($info['img_path']) && file_exists($tem_path)) {
            $name = $info['temp_name'];
        } else {
            $name = 'index5';
        }

        if ($_SESSION['admin_info']) {
            $this->redirect('Index/index');
        }

        $this->display($name);
    }



    /**

     * 登录操作

     */

    public function doLogin()
    {
        $info = I('post.');

        $ip = getIp();  //获取客户端ip

        $ip_area = getAreaByApi($ip);

        $error_data = [

            'admin_id' => 0,

            'login_name' => $info['name'],

            'login_pwd' => $info['pwd'],

            'login_ip' => $ip,

            'login_address' => $ip_area,

            'login_time' => date('Y-m-d H:i:s')

        ];

        //2019-4-17 rml：添加登录次数限制逻辑

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

        //判断登录次数放在最前面，针对同一个ip，计算在login_time 在当前时间和指定时间段之间的登录次数，如果大于等于指定的次数，则禁止登录

        $make_time = time() - (intval($set_time) * 60);

        $where_count = [

            'login_ip' => $ip,

            'login_time' => ['between', [date('Y-m-d H:i:s', $make_time), date('Y-m-d H:i:s', time())]],

        ];

        $allow_count = AdminloginerrorModel::getCount($where_count);

        if ($allow_count >= $login_count) {
            $this->ajaxReturn(array('status' => 'no', 'msg' => '您登录次数过于频繁，请' . $set_time . '分钟后再试'));
        }

        /************************************************************************/



        if (!($info['name'] && $info['pwd'] && $info['code'])) {
            $this->ajaxReturn(array('status' => 'no', 'msg' => '请输入账号信息'));
        }

        $admin_info = AdminuserModel::findUser($info['name'], $info['pwd']);

        //当登录次数达到一定的次数，还没登录进去时，拦截提示:还没做



        //2019-3-11 任梦龙：判断ip黑名单

        $black_ip = BlackipModel::isBlackIp($ip);

        if (!$black_ip) {
            $msg = '该登录IP地址已被限制，请联系超管！';

            $error_data['error_msg'] = $msg;

            $this->adminLoginError($error_data);

            $this->ajaxReturn(array('status' => 'no', 'msg' => $msg));
        }



        if (!$admin_info) {
            $msg = '用户名或密码不正确，请重新输入！';

            $error_data['error_msg'] = $msg;

            $this->adminLoginError($error_data);

            $this->ajaxReturn(array('status' => 'no', 'msg' => $msg));
        }



        //2019-1-21 任梦龙：添加判断管理员的状态和删除状态的条件

        /*****************************************/

        if ($admin_info['status'] != 1) {
            $msg = '该管理员被禁用，请联系超管！';

            $error_data['error_msg'] = $msg;

            $error_data['admin_id'] = $admin_info['id'];

            $this->adminLoginError($error_data);

            $this->ajaxReturn(array('status' => 'no', 'msg' => $msg));
        }

        if ($admin_info['del'] != 0) {
            $msg = '该管理员不存在，请联系超管！';

            $error_data['error_msg'] = $msg;

            $error_data['admin_id'] = $admin_info['id'];

            $this->adminLoginError($error_data);

            $this->ajaxReturn(array('status' => 'no', 'msg' => $msg));
        }

        /*****************************************/



        //判断验证码

        if (!$this->checkVerify($info['code'])) {
            $msg = '验证码错误！';

            $error_data['error_msg'] = $msg;

            $error_data['admin_id'] = $admin_info['id'];

            $this->adminLoginError($error_data);

            $this->ajaxReturn(array('status' => 'no', 'msg' => $msg));
        }

        //2019-4-11 rml：判断该管理员是否有权限

//        if(!$admin_info['menu_json']){

//            $msg = '该管理员未分配权限,请联系超管！';

//            $error_data['error_msg'] = $msg;

//            $error_data['admin_id'] = $admin_info['id'];

//            $this->adminLoginError($error_data);

//            $this->ajaxReturn(array('status' => 'no', 'msg' => $msg));

//        }



        //2019-2-13 任梦龙：将admin_user_id字段名称修改为admin_id。保持一致性，便于理解区分

        $data = [

            'admin_id' => $admin_info['id'],

            'login_datetime' => date('Y-m-d H:i:s'),

            'login_ip' => $ip,

            'login_address' => $ip_area

        ];

        M('adminloginrecord')->add($data);

        //修改最后一次登陆时间

        AdminuserModel::updateLoginTime($admin_info['id']);

        /***************************************************************************/

        //2019-2-15 任梦龙：管理员挤下线

        $session_id = session_id();

        $where_session = [

            'admin_id' => $admin_info['id'],

            'user_id' => 0,

            'child_id' => 0,

        ];

        //2019-4-1 任梦龙：修改

        //每一次登录时,先去获取session_id记录，有则修改，没有则添加

        $session_find = UsersessionidModel::getSessionCount($where_session);

        if ($session_find) {
            UsersessionidModel::editSessionid(['id' => $session_find['id']], ['session_id' => $session_id]);
        } else {
            $data = [

                'admin_id' => $admin_info['id'],

                'user_id' => 0,

                'child_id' => 0,

                'session_id' => $session_id,

            ];

            UsersessionidModel::addSessionId($data);
        }

        /***************************************************************************/

        //2019-4-22 任梦龙：用原生的session

//        $_SESSION['admin_name'] = $admin_info['user_name'];

        session('admin_name', $admin_info['user_name']);

//        $_SESSION['admin_time'] = time();

        session('admin_time', time());

        unset($admin_info['manage_pwd']);

        unset($admin_info['password']);

        unset($admin_info['menu_json']);

        unset($admin_info['menu_path']);

//        $_SESSION['admin_info'] = $admin_info;

        session("admin_info", $admin_info);

        $this->ajaxReturn(array('status' => 'ok', 'msg' => '登录成功，即将跳转！'));
    }



    /**

     * 生成验证码

     */

    public function verify()
    {
        ob_clean();

        $verify = new \Think\Verify();

        $verify->length = 4;

        $verify->expire = 60;  //验证码的有效期（60秒）

        $verify->entry();
    }



    /**

     * 退出登录

     */

    public function logout()
    {
        session('admin_info', null);

        session('admin_name', null);

        session('admin_time', null);

        $this->ajaxReturn(['msg' => '退出成功，即将跳转！', 'status' => 'ok']);
    }



    public function addAdminLog($name, $pwd, $ip, $count, $content)
    {
        $log_data = [

            'user_name' => $name,

            'password' => $pwd,

            'ip' => $ip,

            'count' => $count,

            'log_content' => $content,

            'date_time' => date('Y-m-d H:i:s'),

        ];

        M('loginlog')->add($log_data);

        return true;
    }



    //2019-3-11 任梦龙：修改方法名，方法名为小驼峰

    //验证码检测

    public function checkVerify($code, $id = '')
    {
        $verify = new \Think\Verify();

        //2019-3-11 任梦龙：添加验证码的有效期

        $verify->fontSize = 60;

        $verify->length = 4;

        $verify->expire = 60;  //有效期，单位：秒

        $verify->useNoise = false;

        return $verify->check($code, $id);
    }



    /**

     * 2019-1-22 任梦龙：封装获取ip归属地

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

     * 2019-1-22 任梦龙：数组转码

     */

    public function arrayUtf8($array)
    {
        array_walk($array, function (&$value) {
            $value = iconv('gbk', 'utf-8', $value);
        });



        return $array;
    }



    /**

     * 2019-2-15 任梦龙：添加登录错误记录表

     */

    public function adminLoginError($error_data)
    {
        AdminloginerrorModel::addErrorMsg($error_data);
    }



    /**

     *2019-3-12 任梦龙：判断IP黑名单

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
}
