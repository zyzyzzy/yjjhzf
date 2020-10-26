<?php

namespace Pay\Controller;

use Think\Controller;


class HuiFuWxGzhController extends PayController
{
    protected $PayName = "HuiFuWxGzh";  //通道编码
    protected $CheckDomain = true;  //是否判断提交域名
    protected $CheckIp = true;  //是否判断提交ip
    protected $checkStatus = true;  //通道状态
    protected $sysOrdernumberLeng = 32; //体系订单号长度
    protected $callbackurl = "callbackurl";
    protected $notifyurl = "notifyurl";
    protected $tjurl;  //提交地址

    public function __construct()
    {
        parent::__construct();
    }


    public function pay($parameter){

        $appid = "wxe652f6d7c154c7b3";

        $website = M("website")->field("pay_domain,pay_http")->find();

        $REDIRECT_URI = ($website["pay_http"]==1?"http://":"https://").$website["pay_domain"].U($this->PayName."/wxpay");



        $scope='snsapi_base';

        $url='https://open.weixin.qq.com/connect/oauth2/authorize?appid='.$appid.'&redirect_uri='.urlencode($REDIRECT_URI).'&response_type=code&scope='.$scope.'&state='.$parameter["sysordernumber"]."#wechat_redirect";

        //加缓存 随机数

    //    header("Location:".$url);


        if(I('qrcode') == 'url'){
            $this->ajaxReturn([
                'qrcode' => $url
            ], 'json');
            exit;
        }
        if(parent::isMobile()){
            if($this->judgment() == 2){
                header("Location:".$url);
            }else{
                exit("只能在微信里调起");
            }
        }else{
            parent::Qrcode($url,$parameter["sysordernumber"],$parameter["userordernumber"],$this->PayName,$parameter["ordermoney"]);
        }
    }

    /**
     * 发起支付
     * @param $parameter：传到通道所需要的参数
     */
    public function wxpay()
    {

        $appid = "wxe652f6d7c154c7b3";

        $secret = "c4bfcd8394786d699069fcc9d1d6daba";

        $code = $_GET["code"];

        $get_token_url = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid='.$appid.'&secret='.$secret.'&code='.$code.'&grant_type=authorization_code';

        $ch = curl_init();

        curl_setopt($ch,CURLOPT_URL,$get_token_url);

        curl_setopt($ch,CURLOPT_HEADER,0);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1 );

        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);

        $res = curl_exec($ch);

        curl_close($ch);

        $json_obj = json_decode($res,true);

        //根据openid和access_token查询用户信息

        $access_token = $json_obj['access_token'];

        $openid = $json_obj['openid'];

        $parameter = parent::getPayapiAccountKey(I("get.state"));

        Vendor('adapay.AdapaySdk.init');
        $config_array = [
            'api_key_live' => $parameter['md5keystr'],
            'rsa_public_key' => $parameter['upstreamkeystr'],
            'rsa_private_key' => $parameter['privatekeystr']
        ];
        \AdaPay\AdaPay::init($config_array, "live", true);
        $payment = new \AdaPaySdk\Payment();

        $website = M("website")->field("back_domain,back_http")->find();
        $ordermoney = M('ordermoney')->where("sysordernumber='".I("get.state")."'")->getField("true_ordermoney");
# 支付设置
        $payment_params = array(
            'app_id'=> $parameter["memberid"],
            'order_no'=> I("get.state"),
            'pay_channel'=> 'wx_pub',
            'pay_amt'=> sprintf("%.2f",$ordermoney),
            'goods_title'=> '订单:'.I("get.state"),
            'goods_desc'=> '订单:'.I("get.state"),
            'notify_url' => ($website["back_http"]==1?"http://":"https://").$website["back_domain"].U($this->PayName."/notifyurl"),
            'expend' => [
                'open_id' => $openid,
                "is_raw"=>"1",
                "callback_url"=>($website["back_http"]==1?"http://":"https://").$website["back_domain"].U($this->PayName."/callbackurl")."?orderid=".I("get.state"),

            ]
        );
      //  dump($payment_params);
# 发起支付
        $payment->create($payment_params);
   //     dump($payment);
# 对支付结果进行处理
        if ($payment->isError()){
            //失败处理
            var_dump($payment->result);
        } else {
            //成功处理
         //   dump($payment->result['expend']['pay_info']);
            M()->startTrans();
            $order = M('order');
            if(M('order')->lock(true)->where("sysordernumber='" . I("get.state") . "'")->save(['shangyouordernumber'=>$payment->result['id']])){
                M()->commit();
            }else{
                M()->rollback();
                exit('写入上游商家订单号失败');
            }
            $arr = json_decode($payment->result['expend']['pay_info'],true);
?>
            <script>
                function onBridgeReady(){
                    WeixinJSBridge.invoke(
                        'getBrandWCPayRequest', {
                            "appId":"<?php echo($arr['appId']); ?>",     //公众号名称，由商户传入
                            "timeStamp":"<?php echo($arr['timeStamp']); ?>",         //时间戳，自1970年以来的秒数
                            "nonceStr":"<?php echo($arr['nonceStr']); ?>", //随机串
                            "package":"<?php echo($arr['package']); ?>",
                            "signType":"<?php echo($arr['signType']); ?>",         //微信签名方式：
                            "paySign":"<?php echo($arr['paySign']); ?>" //微信签名
                        },
                        function(res){
                            if(res.err_msg == "get_brand_wcpay_request:ok" ){
                                // 使用以上方式判断前端返回,微信团队郑重提示：
                                //res.err_msg将在用户支付成功后返回ok，但并不保证它绝对可靠。
                            }
                        });
                }
                if (typeof WeixinJSBridge == "undefined"){
                    if( document.addEventListener ){
                        document.addEventListener('WeixinJSBridgeReady', onBridgeReady, false);
                    }else if (document.attachEvent){
                        document.attachEvent('WeixinJSBridgeReady', onBridgeReady);
                        document.attachEvent('onWeixinJSBridgeReady', onBridgeReady);
                    }
                }else{
                    onBridgeReady();
                }
            </script>
<?php

        }
        //--------------------------------------分割线---------------------------------------------------

    }


    /**
     * 异步回调地址
     */
    public function notifyurl()
    {

//file_put_contents('huifuaabbbbbbbbbbbbbbbbbbbbbbbbbbbbbbbb.txt',json_encode($_POST)."\n",FILE_APPEND);

        $arr_data = json_decode($_POST['data'],true);
        $config = parent::getPayapiAccountKey($arr_data['order_no']);
        $key = "-----BEGIN PUBLIC KEY-----\n".wordwrap($config['upstreamkeystr'], 64, "\n", true)."\n-----END PUBLIC KEY-----";
        if (openssl_verify($_POST['data'], base64_decode($_POST['sign']), $key)){
          //  file_put_contents('huifuaa1111111111111111111111111111111111111.txt',json_encode($_POST)."\n",FILE_APPEND);
            parent::editMoney($arr_data['order_no'], $this->PayName, 0, $arr_data['pay_amt'], 'RECV_ORD_ID_'.substr($arr_data['id'],0,-32));
        }

    }

    /**
     * 同步跳转地址
     */
    public function callbackurl()
    {
        echo('正在处理中.....');

        $sysordernumber = I('orderid');

        $find = M('order')->where("sysordernumber='".$sysordernumber."'")->find();
        if($find){
            parent::editMoney($sysordernumber, $this->PayName, 1, $find['ordermoney'], 'success');
        }else{
            exit('获取订单状态失败');
        }
    }

    public function queryPayOrder($sysordernumber)
    {    //查询订单状态
        Vendor('adapay.AdapaySdk.init');
        $config = parent::getPayapiAccountKey($sysordernumber);
        $config_array = [
            'api_key_live' => $config['md5keystr'],
            'rsa_public_key' => $config['upstreamkeystr'],
            'rsa_private_key' => $config['privatekeystr']
        ];
        \AdaPay\AdaPay::init($config_array, "live", true);
        # 初始化支付类
        $payment = new \AdaPaySdk\Payment();
#发起支付订单查询
        $shangyouordernumber = M('order')->where("sysordernumber='" . $sysordernumber . "'")->getField('shangyouordernumber');

        $payment->orderQuery(['payment_id'=> $shangyouordernumber]);

# 对关单结果进行处理
        if ($payment->isError()){
            //失败处理
            return false;
        } else {
            //成功处理
           if($payment->result['status'] == 'succeeded'){
               return true;
           }else{
               return false;
           }

        }
        //---------------------------------分割线-----------------------------------
    }

    private function judgment()
    {
        //判断是不是支付宝
        if (strpos($_SERVER['HTTP_USER_AGENT'], 'AlipayClient') !== false) {
            return 1;
        }
        //判断是不是微信
        if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false) {
            return 2;
        }
        //判断是不是QQ
        if (strpos($_SERVER['HTTP_USER_AGENT'], 'QQ') !== false) {
            return 3;
        }
        //哪个都不是
        return 0;
    }


}