<?php

namespace Admin\Controller;

use Admin\Model\WebsiteModel;
use Admin\Model\UserstatusModel;

class SystemSetingController extends CommonController
{
    //基本设置页面
    public function BasicsSeting()
    {
        session('code_switch', 1);
        $this->display();
    }

    //网站设置页面
    public function WebSiteSeting()
    {
        $code_type = $this->getCodeType();
        $this->assign('code_type', $code_type);
        $website = WebsiteModel::getWebsite();
        $this->assign('website', $website);
        $this->display();
    }

    //域名设置页面
    public function DomainSeting()
    {
        $code_type = $this->getCodeType();
        $this->assign('code_type', $code_type);
        $website = WebsiteModel::getWebsite();
        $this->assign('website', $website);
        $this->display();
    }

    //开关设置页面
    public function SwitchSeting()
    {
        $code_type = $this->getCodeType();
        $this->assign('code_type', $code_type);
        $website = WebsiteModel::getWebsite();
        $this->assign('website', $website);
        $this->display();
    }

    //提成等级页面
    public function commissionLevel()
    {
        $code_type = $this->getCodeType();
        $this->assign('code_type', $code_type);
        $website = WebsiteModel::getWebsite();
        $this->assign('website', $website);
        $this->display();
    }

    //修改网站设置
    public function editWebSite()
    {
        $msg = '修改网站设置:';
        $this->editBasicsSeting($msg);
    }

    //修改域名设置
    public function editDomainSet()
    {
        $msg = '修改域名设置:';
        $this->editBasicsSeting($msg);
    }

    //修改开关设置
    public function editSwitchSet()
    {
        $msg = '修改开关设置:';
        $this->editBasicsSeting($msg);
    }

    //修改提成等级
    public function editLevel()
    {
        $msg = '修改提成等级设置:';
        $this->editBasicsSeting($msg);
    }

    /**
     * 基本设置里的页面修改操作都走同一个方法,但是为了以后做权限，分别写不同的修改方法名称
     */
    public function editBasicsSeting($msg)
    {
        //检测验证码的代码
        $verfiy_code = I('post.verfiy_code', '', 'trim');
        $code_type = I('post.code_type', 0, 'intval');
        $this->checkVerifyCode($verfiy_code, $code_type, $msg);
        $return = AddSave('website', 'save', '修改');
        //添加结算设置的判断
        //2019-4-19 rml：注释：因为这里只是一个开关，结算设置的开关去结算设置页面设置也可以，不必再这里进行设置,而且写法也有问题，如果修改操作不成功,那么这个操作自然就执行不了
//        if ($msg == '修改开关设置:') {
//            SettleconfigModel::editSysSettleStatus(I('settle_valve'));
//        }
        $this->addAdminOperate($msg . $return['msg']);
        $this->ajaxReturn($return, 'json');
    }

    //2019-3-22 任梦龙：登录设置页面
    //2019-4-17 rml：重新梳理逻辑
    public function loginSeting()
    {
        $login_set = C('LOGIN_SET');
        $this->assign('login_set', $login_set);
        $website = WebsiteModel::getWebsite();
        $this->assign('website', $website);
        $code_type = $this->getCodeType();
        $this->assign('code_type', $code_type);
        $this->display();
    }

    /**
     * 2019-3-22 任梦龙：修改登陆次数
     */
    //2019-4-17 rml：重新梳理逻辑
    public function editLoginSeting()
    {
        $msg = '修改登录限制设置:';
        $this->editBasicsSeting($msg);
    }

    //2019-4-11 rml：注册设置:设置用户在注册时的默认状态：未激活，正常，禁用
    public function registerSeting()
    {
        $website = WebsiteModel::getWebsite();
        $this->assign('website', $website);
        $list = UserstatusModel::selectUserStatus();
        $this->assign('list', $list);
        $code_type = $this->getCodeType();
        $this->assign('code_type', $code_type);
        $this->display();
    }

    //确认修改注册设置
    public function editRegSeting()
    {
        $msg = '修改注册设置:';
        $this->editBasicsSeting($msg);
    }


}