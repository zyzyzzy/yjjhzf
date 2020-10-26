<?php
namespace Admin\Controller;

use Admin\Model\DaifuModel;
use Admin\Model\SettleModel;
use Admin\Model\SettlemoneyModel;
use Think\Controller;


//2019-1-21 任梦龙：将Controller更改为CommonController
class SendDFController extends CommonController
{
    public function sendDF($id)
    {
        $settle = SettleModel::getInfo($id);
        if(!$settle){
            $this->ajaxReturn(["status"=>"error","msg"=>"代付订单不存在!"],"json");
        }
        $settlemoney = SettlemoneyModel::getInfo($settle['ordernumber']);
        if(!$settlemoney){
            $this->ajaxReturn(["status"=>"error","msg"=>"代付订单数据错误!"],"json");
        }
        $daifu = DaifuModel::getInfo($settlemoney['daifuid']);
        if(!$daifu){
            $this->ajaxReturn(["status"=>"error","msg"=>"代付通道错误"],"json");
        }

        $result = A('DaiFu/'.$daifu['en_payname'])->Pay(array_merge($settle,$settlemoney));

    }
}