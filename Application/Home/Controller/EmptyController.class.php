<?php
namespace Home\Controller;

use Think\Controller;

class EmptyController extends Controller
{

    public function index()
    {
        header("Location: /404.html");
        exit;
    }

    public function _empty($Activate)
    {
        header("Location: /404.html");
        exit;
        $controllername = CONTROLLER_NAME;
        if ($controllername == "Activate") {
            $User = M("User");
            $username = $User->where("activate='" . $Activate . "'")->getField("username");
            if ($username) {

                $status = $User->where("activate='" . $Activate . "'")->getField("status");
                $activatedatetime = $User->where("activate='" . $Activate . "'")->getField("activatedatetime");
                $usertype = $User->where("activate='" . $Activate . "'")->getField("usertype");
                switch ($usertype) {
                    case 1:
                        $user_type = "系统子管理员";
                        break;
                    case 2:
                        $user_type = "分站管理员";
                        break;
                    case 3:
                        $user_type = "分站子管理员";
                        break;
                    case 4:
                        $user_type = "普通商户";
                        break;
                    case 5:
                        $user_type = "普通代理商";
                        break;
                    case 6:
                        $user_type = "独立代理商";
                        break;
                }
                if ($status == 0) {
                    $activatedatetime = date("Y-m-d H:i:s");
                    $User->status = 1;
                    $User->activatedatetime = $activatedatetime;
                    $User->where("activate='" . $Activate . "'")->save();
                    $this->assign("t", "ok");
                    $this->assign("strtitle", "账号激活成功！");
                    $this->assign("strcontent", "激活账号：【" . $username . "】<br> 用户类型：【" . $user_type . "】<br> 激活时间：【" . $activatedatetime . "】");
                } else {
                    $this->assign("t", "no");
                    $this->assign("strtitle", "账号激活失败！");
                    $this->assign("strcontent", $username . " 账号已激活，激活时间：" . $activatedatetime . " 请不要重复激活！");
                    // /////////////
                }
            } else {
                $this->assign("t", "no");
                $this->assign("strtitle", "账号激活失败！");
                $this->assign("strcontent", "激活账号不存在！");
                // ///////////
            }
        } else {
            $this->assign("t", "no");
            $this->assign("strtitle", "非法操作");
            $this->assign("strcontent", "请不要非法操作！");
        }

        $this->display("/Index/activate");
    }
}
?>
