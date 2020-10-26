<?php
namespace Admin\Controller;

use Admin\Model\SmsModel;
use Think\Controller;


//2019-1-21 任梦龙：将Controller更改为CommonController
class SmsSetingController extends CommonController
{
    public function SmsSeting()
    {
        $this->display();
    }

    public function LoadSmsSeting()
    {
        $appname = I('app_name');
        $page = I('page');
        $limit = I('limit');
        $sms = SmsModel::getSms($appname,$page,$limit);
        $this->ajaxReturn([
            'code' => 0,
            'msg'  => '数据加载成功',//响应结果
            'count' => count($sms),//总页数
            'data'  => $sms
        ],"json");

    }

    public function SmsSetingAdd()
    {
        $this->display();
    }

    public function SmsCreate()
    {
        $this->ajaxReturn(AddSave('sms','add','添加短信接口'));
    }

    public function SmsEdit()
    {
        $sms = SmsModel::getInfo(I('id'));
        $this->assign('sms',$sms);
        $this->display();
    }

    public function updateSms()
    {
        $this->ajaxReturn(AddSave('sms','save','修改短信接口'));
    }

    public function DelSmsSeting()
    {
        $id = I('id');
        $res = M('sms')->where("id='".$id."'")->delete();
        if($res){
            exit('ok');
        }
        exit('no');
    }

    public function test()
    {
        dump(regDomain());
    }
}