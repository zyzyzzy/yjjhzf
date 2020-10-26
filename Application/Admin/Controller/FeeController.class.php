<?php
namespace Admin\Controller;

use Admin\Model\UserModel;
use Admin\Model\UserpayapiclassModel;
use Admin\Model\UserpayapifeeModel;
use Think\Controller;


//2019-1-21 任梦龙：将Controller更改为CommonController
class FeeController extends CommonController
{
    public function ShowUserFees()
    {
        $payapis = UserpayapiclassModel::getUserPayapis(I("userid"));
        $this->assign('payapis',$payapis);
        $this->assign('userid',I('userid'));
        $this->display();
    }

    public function loadUserFees()
    {
        $fees = UserpayapifeeModel::getUserFees(I("userid"),I('payapiid'));
        $this->ajaxReturn([
            'code' => 0,
            'msg'  => '数据加载成功',//响应结果
            'count' => count($fees),//总页数
            'data'  => $fees
        ],"json");
    }
    public function showFee()
    {
        $fee = UserpayapifeeModel::getFee(I('id'));
        $this->assign('fee',$fee);
        $this->display();
    }

    public function showCreateFrom()
    {
        $user = UserModel::getInfo(I('userid'));
        $payapis = UserpayapiclassModel::getUserPayapis(I('userid'));
        $this->assign('user',$user);
        $this->assign('payapis',$payapis);
        $this->display();
    }

    public function addFee()
    {
        $this->ajaxReturn(addSave('userpayapifee','add','添加短信接口信息'),"json");
    }

    public function ShowEditFee()
    {
        $fee = UserpayapifeeModel::getFee(I('id'));
        $this->assign('fee',$fee);
        $this->display();
    }

    public function editFee()
    {
        $this->ajaxReturn(addSave('userpayapifee','save','修改短信接口信息'),"json");
    }

}