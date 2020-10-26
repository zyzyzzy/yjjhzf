<?php
/**
 * 利用tp框架自带的auth控制器判断权限，然后所有控制器都继承此公共控制器
 */

namespace Admin\Controller;

use Think\Controller;
use Think\Auth;
use Admin\Model\UsersessionidModel;
use Admin\Model\GooglecodeModel;
use Admin\Model\AdminoperaterecordModel;
use Admin\Model\AdminuserModel;

class CommonController extends Controller
{
    protected $del = '删除';
    protected $recovery = '恢复';
    protected $admin_id;  //2019-2-13 任梦龙：添加，方便写id

    /*
     * 构造函数，利用tp框架自带的auth控制器判断权限
     */
    public function __construct()
    {
        parent::__construct();
        //2019-4-1 任梦龙：1. 先去判断该管理是否允许同一账号在线(0=允许；1=不允许),如果允许则不做判断,否则判断踢下线
        /***************************************************/
        $same_admin = AdminuserModel::getSameAdmin(session('admin_info.id'));
        if ($same_admin == 1) {
            $old_sessionid = UsersessionidModel::getSessionId(session('admin_info.id'));
            if ($old_sessionid != session_id()) {
                session('admin_info', null); // 2019-4-22 rml:发现系统和用户只要有一个下线，那么另一个也下线，可能是因为将session全部删除了,所以只删除对应的session
                exit("<script>alert('对不起，账号在另一处登录,您被迫下线');window.location.href='" . U('AdminLogin/index') . "';</script>");
            }
        }
//        dump($_SESSION);
        /***************************************************/
        if (!session('admin_info')) {
            $this->redirect('AdminLogin/index');
        }

        if ((time() - session('admin_time')) >= C('SESSION_OPTIONS')['expire']) {
            session('admin_info', null);
            $this->redirect('AdminLogin/index');
        } else {
            session('admin_time', time());
        }

        $this->admin_id = session('admin_info.id');
        //2019-3-28 任梦龙：只有超管不需要权限控制
        if (session('admin_info.super_admin') != 1) {
            //获取当前控制器和方法名
            $name = CONTROLLER_NAME . '/' . ACTION_NAME;
            if (CONTROLLER_NAME != 'Index') {
                //实例化自带的权限控制器
                $auth = new Auth();
                $auth_result = $auth->check($name, session('admin_info.id'));
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

    /*
     * 删除menu_json文件
     */
    function unlinkMenuJson()
    {
        //退出时删除menu_json文件
        if (file_exists("Public/menujson/menu.json")) {
            unlink("Public/menujson/menu.json");
        }
    }


    /**
     * 2019-1-15 任梦龙：封装回收站的返回信息
     * @param $res ：删除或恢复的结果
     * @param $msg：删除  恢复
     */
    protected function recoveryReturn($res, $msg)
    {
        if ($res) {
            $this->ajaxReturn(['status' => 'ok', 'msg' => $msg . '成功！'], 'JSON');
        } else {
            $this->ajaxReturn(['status' => 'no', 'msg' => $msg . '失败，请重试！'], 'JSON');
        }
    }

    /**
     * 2019-2-21 任梦龙：封装公共的验证二次验证的方法
     */
    public function veryGoogleCode($type, $user_id, $google_code)
    {
        $secret = GooglecodeModel::getSecret($type, $user_id);
        $res = verifcode_googlecode($secret, $google_code);
        return $res;
    }

    /**
     * 2019-2-26 任梦龙：添加管理员操作记录
     */
    public function addAdminOperate($msg)
    {
        $data = [
            'admin_id' => session('admin_info.id'),  //2019-3-21 任梦龙：修改为session取值
            'admin_ip' => getIp(),
            'msg' => $msg,
            'date_time' => date('Y-m-d H:i:s')
        ];
        AdminoperaterecordModel::addLoginTemp($data);
    }

    /**
     * 2019-3-11 任梦龙：根据当前管理员二次验证与管理密码的开启状态，判断验证码类型
     */
    public function getCodeType()
    {
        $google = GooglecodeModel::getInfo('admin', session('admin_info.id'));
        $manage_status = AdminuserModel::getManageStatus(session('admin_info.id'));
        //二次验证级别高于管理密码，标志状态：1=以管理密码页面遮罩; 2=以二次验证页面遮罩 3=以二次验证遮罩层（当两者都开启的情况下）
        $code_type = 1;
        if ($google) {
            if ($google['status'] == 1) {
                $code_type = 2;
            }
            if ($google['status'] == 1 && $manage_status == 1) {
                $code_type = 3;
            }
        }
        return $code_type;
    }

    /**
     * 2019-3-11 任梦龙：封装公共的判断流程
     */
    //2019-3-26 任梦龙：修改逻辑,先去判断遮罩层类型,再去判断验证码的正确性，这样就单独判断，而不是先做变量处理，再去判断了
    public function returnCodeRes($verfiy_code, $code_type)
    {
        switch ($code_type) {
            //只验证管理密码
            case 1:
                $manage_pwd = AdminuserModel::getManagePwd(session('admin_info.id'));
                $res = md5($verfiy_code) == $manage_pwd ? true : false;
                break;
            //只验证二次验证码
            case 2:
                $res = $this->veryGoogleCode('admin', session('admin_info.id'), $verfiy_code) ? true : false;
                break;
            //同时验证
            case 3:
                $manage_pwd = AdminuserModel::getManagePwd(session('admin_info.id'));
                $res_manage = md5($verfiy_code) == $manage_pwd ? true : false;
                $res_google = $this->veryGoogleCode('admin', session('admin_info.id'), $verfiy_code) ? true : false;
                if ($res_manage || $res_google) {
                    $res = true;
                } else {
                    $res = false;
                }
                break;
        }
        return $res;
    }

    /**
     * 2019-3-11 任梦龙：判断验证码的否为空
     */
    public function verifyEmpty($verfiy_code)
    {
        if (!$verfiy_code) {
            $this->ajaxReturn(['status' => 'no', 'msg' => '请填写验证码']);
        }
    }

    /**
     * 2019-3-12 任梦龙：封装修改数据时，验证码的验证功能
     */
    public function checkVerifyCode($verfiy_code, $code_type, $msg = '')
    {
        $this->verifyEmpty($verfiy_code);   //判断空值
        $return_res = $this->returnCodeRes($verfiy_code, $code_type);
        if (!$return_res) {
//            $this->addAdminOperate($msg . '验证码错误');
            $this->ajaxReturn(['status' => 'no', 'msg' => '验证码错误,请重试']);
        }
    }

    /**
     * 2019-3-12 任梦龙:封装验证遮罩层时，验证码的验证功能
     */
    public function checkLayerCode($verfiy_code, $code_type)
    {
        $this->verifyEmpty($verfiy_code);   //判断空值
        $return_res = $this->returnCodeRes($verfiy_code, $code_type);
        if ($return_res) {
            session('code_switch', null);  //验证成功时将session值删除
            $this->ajaxReturn(['status' => 'ok', 'msg' => '验证成功']);
        }
        $this->ajaxReturn(['status' => 'no', 'msg' => '验证码错误,请重试']);
    }

    /**
     * 2019-3-21 任梦龙：修改：封装生成管理员的菜单文件路径,因为好多地方需要生成文件路径，所以用一个方法统一生成，方便管理，后面如果有看到原始的，可以更改过来
     * @param $admin_id：管理员id
     * @return string：返回管理员的菜单文件路径
     */
    public function getAdminMenuPath($admin_id)
    {
        $file_name = 'adminmenujson-' . $admin_id;  //拼接文件名称  'adminmenujson-1'
        $file_name = hash('sha1', $file_name);  //文件名加盐哈希加密
        $file_path = C('ADMIN_MENU_PATH') . $file_name . '.json';    //拼接文件路径
        return $file_path;
    }

    /**
     * 2019-3-25 任梦龙：封装判断当前管理员是否开通谷歌验证
     * 0=未开通 1=开通未开启 2=开启
     */
    public function isExistGoogle()
    {
        $google_code = 0;
        $google = GooglecodeModel::getInfo('admin', session('admin_info.id'));
        if ($google) {
            if ($google['status'] != 1) {
                $google_code = 1;
            } else {
                $google_code = 2;
            }
        }
        return $google_code;
    }

    //2019-4-9 任梦龙：获取用户菜单路径(usermenu.json)
    public function getUserMenupath()
    {
        if (!file_exists(C('USER_MENU_PATH'))) {
            mkdir(C('USER_MENU_PATH'), '0777', true);
        }
        $main_file_name = hash('sha1', 'usermenu');
        $main_menu_path = C('USER_MENU_PATH') . $main_file_name . '.json';
        return $main_menu_path;
    }

}