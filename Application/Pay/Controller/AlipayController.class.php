<?php
namespace Pay\Controller;

use Think\Controller;

class AlipayController extends PayController
{
    protected $PayName = "Alipay";  //通道编码
    protected $CheckDomain = true;  //是否判断提交域名
    protected $checkStatus = true;  //通道状态  //2019-3-28 任梦龙：属性名称修改为checkStatus
    protected $sysordernumberleng = 32; //体系订单号长度
    protected $tjurl = '';

    public function __construct()
    {
        parent::__construct();
    }

    public function pay()
    {
        echo "已经进入通道";
    }

    //异步回调
    public function notifyurl()
    {

    }

    //同步回调
    public function callbackurl()
    {

    }
}