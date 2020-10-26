<?php



namespace Pay\Controller;

use Think\Controller;

class AlipaySmController extends PayController
{
    protected $PayName = "AlipaySm";  //通道编码

    protected $CheckDomain = true;  //是否判断提交域名

    protected $CheckIp = true;  //是否判断提交ip

    protected $checkStatus = true;  //通道状态

    protected $sysOrdernumberLeng = 32; //体系订单号长度

    protected $callbackurl = "callbackurl";

    protected $notifyurl = "notifyurl";

    protected $tiurl;  //提交地址



    public function __construct()
    {
        parent::__construct();
    }





    /**

     * 发起支付

     * @param $parameter：传到通道所需要的参数

     */

    public function pay($parameter)
    {
        $config = array(

            //应用ID,您的APPID。

            'app_id' => $parameter["account_key"]["memberid"],

            //商户私钥

            'merchant_private_key' => $parameter["account_key"]["privatekeystr"],

            //异步通知地址

            'notify_url' => $parameter["notifyurl"],

            //同步跳转

            'return_url' =>  $parameter["callbackurl"],

            //编码格式

            'charset' => "UTF-8",

            //签名方式

            'sign_type'=>"RSA2",

            //支付宝网关

            'gatewayUrl' => "https://openapi.alipay.com/gateway.do",

            //支付宝公钥,查看地址：https://openhome.alipay.com/platform/keyManage.htm 对应APPID下的支付宝公钥。

            'alipay_public_key' => $parameter["account_key"]["upstreamkeystr"],

        );

        Vendor('Alipay.pagepay.service.AlipayTradeService');

        Vendor('Alipay.pagepay.buildermodel.AlipayTradePagePayContentBuilder');

        //商户订单号，商户网站订单系统中唯一订单号，必填

        $out_trade_no = trim($parameter["sysordernumber"]);

        //订单名称，必填

        $subject = trim("付款");

        //付款金额，必填

        $total_amount = trim($parameter["ordermoney"]);

        //商品描述，可空

        $body = trim("");

        $payRequestBuilder = new \AlipayTradePagePayContentBuilder();

        $payRequestBuilder->setBody($body);

        $payRequestBuilder->setSubject($subject);

        $payRequestBuilder->setTotalAmount($total_amount);

        $payRequestBuilder->setOutTradeNo($out_trade_no);

        $aop = new \AlipayTradeService($config);

        $response = $aop->pagePay($payRequestBuilder, $config['return_url'], $config['notify_url']);

        //输出表单

        exit($response);



        //调用具体模板，发给
    }





    /**

     * 异步回调地址

     */

    public function notifyurl()
    {
        Vendor('Alipay.pagepay.service.AlipayTradeService');

        $out_trade_no =I("post.out_trade_no");

        $config = parent::getAlipayConfig($out_trade_no);

        $alipaySevice = new \AlipayTradeService($config);

        $result = $alipaySevice->check($_POST);

        if ($result) {//验证成功

            $trade_status = I("post.trade_status");

            if ($trade_status == 'TRADE_FINISHED' or $trade_status == 'TRADE_SUCCESS') {
                parent::editMoney($out_trade_no, $this->PayName, 0, I("post.total_amount"), 'success');
            }
        } else {
            echo "fail";
        }
    }



    /**

     * 同步跳转地址

     */

    public function callbackurl()
    {
        Vendor('Alipay.pagepay.service.AlipayTradeService');

        $out_trade_no =htmlspecialchars(I("get.out_trade_no"));

        $config = parent::getAlipayConfig($out_trade_no);

        $alipaySevice = new \AlipayTradeService($config);

        $result = $alipaySevice->check(I("get."));

        if ($result) {//验证成功

            parent::editMoney($out_trade_no, $this->PayName, 1, I("get.total_amount"), 'success');
        } else {
            echo "fail";
        }
    }



    public function queryPayOrder($sysordernumber="FBftbKka27rJWvzz3Ah7PCYZdT073wDX")
    {    //查询订单状态

        Vendor('Alipay.pagepay.service.AlipayTradeService');

        Vendor('Alipay.pagepay.buildermodel.AlipayTradeQueryContentBuilder');

        $config = parent::getAlipayConfig($sysordernumber);

        $RequestBuilder = new \AlipayTradeQueryContentBuilder();

        $RequestBuilder->setOutTradeNo($sysordernumber);

        $RequestBuilder->setTradeNo("");

        $aop = new \AlipayTradeService($config);

        $response = $aop->Query($RequestBuilder);

        if ($response->code == '10000' and $response->trade_status == 'TRADE_SUCCESS') {
            return true;
        } else {
            return false;
        }
    }
}
