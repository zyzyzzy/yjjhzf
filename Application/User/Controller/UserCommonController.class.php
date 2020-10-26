<?php

namespace User\Controller;

use Think\Controller;
use User\Model\ChilduserModel;
use User\Model\UserModel;
use User\Model\UseroperateModel;
use User\Model\UserpasswordModel;
use User\Model\UsersessionidModel;
use User\Model\GooglecodeModel;

class UserCommonController extends Controller
{
    //后面这三个属性需要删除
//    protected $userid;
//    protected $id;
//    protected $child_id;

//2019-4-22 rml：session过期时间不起效，而且还需要判断是主用户下线还是子账号下线
    public function __construct()
    {
        parent::__construct();
        //先去判断用户或子账号是否允许同一处登录(0=允许；1=不允许)，如果不允许，则去判断是否有踢下线，如果允许了则不作判断
        if (!empty(session('child_info'))) {
            $same_num = ChilduserModel::getSameChild(session('child_info.id'));
            $old_sessionid = UsersessionidModel::getSessionId(['admin_id' => 0, 'user_id' => 0, 'child_id' => session('child_info.id')]);
        } else {
            $same_num = UserModel::getSameUser(session('user_info.id'));
            $old_sessionid = UsersessionidModel::getSessionId(['admin_id' => 0, 'user_id' => session('user_info.id'), 'child_id' => 0]);
        }
        if ($same_num == 1) {
            if ($old_sessionid != session_id()) {
                session('user_info', null);

                exit("<script>alert('对不起，账号在另一处登录,您被迫下线');window.location.href='" . U('UserLogin/index') . "';</script>");
            }
        }


        if (!session('user_info')) {
            $this->redirect('UserLogin/index');
        }

       /* if ((time() - $_SESSION['user_time']) >= C('SESSION_OPTIONS')['expire']) {
            //不能用session_destroy()，因为这会吧所有的session清除，导致管理后台也掉线
            session('user_info', null);
            $this->redirect('UserLogin/index');
        } else {
            $_SESSION['user_time'] = time();
        }*/

        //利用登录时，是否存在子账号id来判断是主账号登录还是子账号登录: 只需要对子账号进行权限判断
        if (!empty(session('child_info'))) {
            $name = CONTROLLER_NAME . '/' . ACTION_NAME;
            if (CONTROLLER_NAME != 'Index') {
                //实例化自带的权限控制器：引入用户的权限类文件
                Vendor('UserAuth.UserAuth');
                $user_auth = new \UserAuth();
                $auth_result = $user_auth->check(session('user_info.id'), $name, session('child_info.id'));
                if ($auth_result === false) {
                    if (IS_AJAX) {
                        //当方法有走ajax时，给一个权限码
                        $this->ajaxReturn(['status' => 'no_auth', 'msg' => '对不起，您没有权限，请联系管理员123！']);
                    } else {
                        //没有时，则让他直接显示没有权限查看内容
                        echo '对不起，您没有权限，请联系管理员456！';
                        exit;
                    }
                }
            }

        }

    }

    /**
     * 添加操作记录并输出信息
     * @param $userid  用户id
     * @param $content  操作记录内容
     * @param $status  输出状态
     * @param $msg  输出内容
     */
    public function operateReturn($userid, $content, $status, $msg)
    {
        UseroperateModel::addOperateRecord($userid, $content);
        $this->ajaxReturn([
            'status' => $status,
            'msg' => $msg
        ]);
    }

    /**
     * 2019-1-16 任梦龙：修改用户操作记录方法,将用户id直接写入
     * 添加操作记录并输出信息
     * @param $this ->userid  用户id
     * @param $content  操作记录内容
     * @param $status  输出状态
     * @param $msg  输出内容
     */
    public function userOperateReturn($content, $status, $msg)
    {

        UseroperateModel::addUserOperateRecord(session('user_info.id'), $content);
        $this->ajaxReturn([
            'status' => $status,
            'msg' => $msg
        ]);
    }

    /**
     * 2019-3-15 任梦龙：修改：封装生成用户（包括子账号）的菜单文件路径,因为好多地方需要生成文件路径，所以用一个方法统一生成，方便管理，后面如果有看到原始的，可以更改过来
     * @param $user_id：主用户id
     * @param $id：主用户下的子账号id
     * @return string：返回子账号的菜单文件路径
     */
    //2019-3-27 任梦龙：修改,添加跟路径是否存在的判断
    public function getUserMenuPath($user_id, $child_id)
    {
        $file_name = 'childmenujson-' . $user_id . '-' . $child_id;  //拼接文件名称
        $file_name = hash('sha1', $file_name);  //文件名加盐哈希加密
        $file_path = C('USER_MENU_PATH') . $file_name . '.json';    //拼接文件路径
        return $file_path;
    }

    /**
     * 2019-2-20 任梦龙：建公共的二次验证方法，以后尽量用这个方法，方便统一管理
     */
    public function veryGoogleCode($type, $user_id, $google_code)
    {
        $secret = GooglecodeModel::getSecret($type, $user_id);
        $res = GooglecodeModel::verifyCode($secret, $google_code);
        return $res;
    }

    /**
     * 2019-02-27汪桂芳:用户后台添加操作记录
     * @param $content
     */
    //2019-3-15 任梦龙：用户id只有一个，用type来区分主次之分
    //2019-3-18 任梦龙：修改
    public function userOperateAdd($user_id, $content, $type)
    {
        if (!empty(session('child_info'))) {
            $user_id = session('child_info.id');
            $type = 1;
        } else {
            $user_id = session('user_info.id');
            $type = 0;
        }
        UseroperateModel::addUserOperateRecord($user_id, $content, $type);
    }

    /**
     * 2019-3-19 任梦龙:用户后台操作记录（有区分主用户和子账号）,后期全部用这个方法,因为前面的几个都没有判断主用户和子账号类型
     * $content: 操作信息
     */
    //2019-3-20 任梦龙：删除type字段，添加child_id字段
    public function addUserOperate($content)
    {
        if (!empty(session('child_info'))) {
            $child_id = session('child_info.id');
        } else {
            $child_id = 0;
        }
        $data = [
            'userid' => session('user_info.id'),
            'userip' => getIp(),
            'operatedatetime' => date('Y-m-d H:i:s'),
            'content' => $content,
            'child_id' => $child_id,
        ];
        M('useroperaterecord')->add($data);
    }

    /**
     * 2019-3-18 任梦龙：封装验证遮罩层时，验证码的验证功能
     */
    public function checkLayerCode($verfiy_code, $code_type)
    {
        $this->verifyEmpty($verfiy_code);   //判断空值
        $return_res = $this->returnCodeRes($verfiy_code, $code_type);
        if ($return_res) {
            session('switch_code', null);  //验证成功时将session值删除
            $this->ajaxReturn(['status' => 'ok', 'msg' => '验证成功']);
        }
        $this->ajaxReturn(['status' => 'no', 'msg' => '验证码错误,请重试']);
    }

    /**
     * 2019-3-18 任梦龙：封装验证码为空的情况
     */
    public function verifyEmpty($verfiy_code)
    {
        if (!$verfiy_code) {
            $this->ajaxReturn(['status' => 'no', 'msg' => '请填写验证码']);
        }
    }

    /**
     * 封装遮罩层的类型
     * $code_type ： 1=只验证管理密码；2=同时验证
     */
    //2019-5-8 rml:优化代码
    public function returnCodeRes($verfiy_code, $code_type)
    {
        if (!empty(session('child_info'))) {
            $manage_pwd = ChilduserModel::getUserManagepwd(session('child_info.id'));
        } else {
            $manage_pwd = UserpasswordModel::getManagePwd(session('user_info.id'));
        }
        $res_pwd = md5($verfiy_code) == $manage_pwd ? true : false;
        switch ($code_type) {
            case 1:
                return $res_pwd;
                break;
            case 2:
                if (!empty(session('child_info'))) {
                    $google = $this->veryGoogleCode('child', session('child_info.id'), $verfiy_code);
                } else {
                    $google = $this->veryGoogleCode('user', session('user_info.id'), $verfiy_code);
                }
                $res_google = $google ? true : false;
                if ($res_pwd || $res_google) {
                    $res = true;
                } else {
                    $res = false;
                }
                return $res;
                break;
        }

    }

    /**
     * 2019-3-18 任梦龙：当前操作者的二次验证与管理密码的开启状态
     */
    //2019-3-27 任梦龙：修改逻辑
    public function getCodeType()
    {
        $code_type = 1;   //默认开启管理密码
        if (!empty(session('child_info'))) {
            $child_google = GooglecodeModel::getInfo('child', session('child_info.id'));
            if ($child_google && $child_google['status'] == 1) {
                $code_type = 2;   //由于管理密码没有状态,所以表示开启了二次验证和管理密码
            }
        } else {
            $user_google = GooglecodeModel::getInfo('user', session('user_info.id'));
            if ($user_google && $user_google['status'] == 1) {
                $code_type = 2;
            }
        }
        return $code_type;
    }

    /**
     * 2019-3-12 任梦龙：封装修改数据时，验证码的验证功能
     */
    //2019-3-27 任梦龙：删除操作记录,因为如果添加的话会增加不必要的数据
    public function checkVerifyCode($verfiy_code, $code_type, $msg = '')
    {
        $this->verifyEmpty($verfiy_code);   //判断空值
        $return_res = $this->returnCodeRes($verfiy_code, $code_type);
        if (!$return_res) {
//            $this->addUserOperate($msg . '验证码错误');
            $this->ajaxReturn(['status' => 'no', 'msg' => '验证码错误,请重试']);
        }
    }

    /**
     * 2019-3-25 任梦龙：封装主用户或者子账号的谷歌验证状态
     * 0=还未开通 1= 开通未开启  2= 开启
     */
    public function getUserGoogle()
    {
        $google_code = 0;
        if (empty(session('child_info'))) {
            $google = GooglecodeModel::getInfo('user', session('user_info.id'));
        } else {
            $google = GooglecodeModel::getInfo('child', session('child_info.id'));
        }
        if ($google) {
            if ($google['status'] != 1) {
                $google_code = 1;
            } else {
                $google_code = 2;
            }
        }
        return $google_code;
    }

    /**
     * 2019-3-25 任梦龙:封装区分主用户和子账号的标识码
     */
    public function verifyUsertype()
    {
        if (empty(session('child_info'))) {
            $user_type = 1;   //主用户
        } else {
            $user_type = 2;   //子账号
        }
        return $user_type;
    }
}