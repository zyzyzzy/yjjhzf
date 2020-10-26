<?php



namespace Pay\Controller;

use Think\Controller;

class WeiXinJSAPIController extends PayController
{
    protected $PayName = "WeiXinJSAPI";  //通道编码

    protected $CheckDomain = false;  //是否判断提交域名

    protected $checkStatus = true;  //通道状态

    protected $sysordernumberleng = 32; //体系订单号长度

    protected $CheckIp = false; //体系订单号长度

    public function __construct()
    {
        parent::__construct();
    }



    public function pay($parameter)
    {
        Vendor('WeiXin.WxPayApi');

        Vendor('WeiXin.JsApi');

        $tools = new \JsApiPay();

        $website = M("website")->field("pay_domain,pay_http")->find();

        $arr  = [

          "baseUrl" => $website["pay_http"]==1?"http://":"https://".$website["pay_domain"].U($this->PayName."/wxpay"),

            "state" => $parameter["sysordernumber"]

        ];

        $tools->GetOpenid($parameter["account_key"], $arr);
    }

    public function wxpay()
    {
        Vendor('WeiXin.WxPayApi');

        Vendor('WeiXin.JsApi');

        $parameter = parent::GetParameter(I("get.state"));

        // dump($parameter);

        $tools = new \JsApiPay();

        $openId = $tools->GetOpenid($parameter["account_key"]);

        $input = new \WxPayUnifiedOrder();

        $input->SetBody("付款");

        $input->SetAttach("");

        $input->SetOut_trade_no(trim($parameter["sysordernumber"]));

        $input->SetTotal_fee(floatval($parameter["ordermoney"])*100);

        $input->SetTime_start(date("YmdHis"));

        $input->SetTime_expire(date("YmdHis", time() + 600));

        $input->SetGoods_tag("");

        $input->SetNotify_url($parameter["notifyurl"]);

        $input->SetTrade_type("JSAPI");

        $input->SetOpenid($openId);

        //  dump($input);

        $config = new \WxPayConfig($parameter["account_key"]["memberid"], $parameter["account_key"]["account"], $parameter["account_key"]["md5keystr"], $parameter["account_key"]["upstreamkeystr"]);

        //  dump($config);exit;

        $order = \WxPayApi::unifiedOrder($config, $input);

//        dump($order);die;

        //2019-4-24 rml：缺少了prepay_id参数，导致报错

        $jsApiParameters = $tools->GetJsApiParameters($order, $parameter["account_key"]);



        //  dump($jsApiParameters);die;

        //获取共享收货地址js函数参数

        $editAddress = $tools->GetEditAddressParameters($parameter["account_key"]);



        $this->assign("jsApiParameters", $jsApiParameters);

        $this->assign("editAddress", $editAddress);



        $website = M('website')->where('id=1')->field('pay_domain,pay_http')->find();

        //获取网站的支付域名

        if ($website['pay_http']==1) {
            $http = "http://";
        }

        if ($website['pay_http']==2) {
            $http = "https://";
        }

        //2019-04-02汪桂芳添加:查询订单状态的请求路径

        $queryorderurl = $http.$website['pay_domain'].U('Pay/Pay/queryOrder');

        $this->assign("queryorderurl", $queryorderurl);

        $this->assign("sysordernumber", $parameter["sysordernumber"]);

        //2019-04-02汪桂芳添加同步跳转路径

        $success_jump = U('callbackurl');

        $this->assign("success_jump", $success_jump);

        $this->display();

        /// ///////////////////////////////////////////////
    }







    public function notifyurl()
    {  //异步回调地址

        libxml_disable_entity_loader(true);

        $arr = json_decode(json_encode(simplexml_load_string($GLOBALS['HTTP_RAW_POST_DATA'], 'SimpleXMLElement', LIBXML_NOCDATA)), true);

        $out_trade_no = $arr["out_trade_no"];

        $account_key = parent::getPayapiAccountKey($out_trade_no);

        Vendor('WeiXin.WxPay');

        $wxpay = new \WxPay();

        if ($arr["return_code"] == "SUCCESS" and $wxpay->CheckSign($account_key, $arr) and $arr["result_code"] == "SUCCESS") {
            $return_echo = $wxpay->ToXml(['return_code'=>'SUCCESS','return_msg'=>'OK']);

            parent::editMoney($out_trade_no, $this->PayName, 0, $arr["total_fee"]/100, $return_echo);
        }
    }





    public function callbackurl()
    {  //同步跳转地址

        //判断订单状态

        $sysordernumber = I('get.orderid');

        $order = M('orderinfo')->where(['sysordernumber'=>$sysordernumber])->field('status,true_ordermoney')->find();

        if ($order['status']>0) {

            //调用父类editMoney方法,目的是走一下回调判断是否需要跳转到广告页面

            parent::editMoney($sysordernumber, $this->PayName, 1, $order['true_ordermoney'], 'SUCCESS');
        } else {
            exit('支付失败');
        }
    }



    public function queryPayOrder($sysordernumber)
    {
        $url = "https://api.mch.weixin.qq.com/pay/orderquery";

        $account_key = parent::getPayapiAccountKey($sysordernumber);

        $arr = [

            'appid' => $account_key["memberid"],

            'mch_id' => $account_key["account"],

            'out_trade_no' => $sysordernumber,

            'nonce_str' => randpw(32),

            'sign_type' => 'HMAC-SHA256',

        ];

        Vendor('WeiXin.WxPay');

        $wxpay = new \WxPay();

        $arr["sign"] = $wxpay->MakeSign($account_key, $arr, $arr["sign_type"]);

        $xml = $wxpay->ToXml($arr);

        $return_xml = $wxpay->postXmlCurl($account_key, $xml, $url);

        libxml_disable_entity_loader(true);

        $arr = json_decode(json_encode(simplexml_load_string($return_xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);

        if ($arr["return_code"] == "SUCCESS" and $wxpay->CheckSign($account_key, $arr) and $arr["result_code"] == "SUCCESS" and $arr["trade_state"] == "SUCCESS") {
            return true;
        } else {
            return false;
        }
    }



    //2019-04-02汪桂芳:支付成功页面

    public function paySuccess()
    {
        $orderid = I('orderid');

        //查询订单信息

        $info = M('order')->where(['sysordernumber'=>$orderid])->field('userordernumber,successtime,true_ordermoney')->find();

        $this->assign('info', $info);

        $this->display();
    }
}
