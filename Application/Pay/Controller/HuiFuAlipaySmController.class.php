<?php

namespace Pay\Controller;

use Think\Controller;


class HuiFuAlipaySmController extends PayController
{
    protected $PayName = "HuiFuAlipaySm";  //通道编码
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


    /**
     * 发起支付
     * @param $parameter：传到通道所需要的参数
     */
    public function pay($parameter)
    {

        Vendor('adapay.AdapaySdk.init');
        $config_array = [
            'api_key_live' => $parameter['account_key']['md5keystr'],
            'rsa_public_key' => $parameter['account_key']['upstreamkeystr'],
            'rsa_private_key' => $parameter['account_key']['privatekeystr']
        ];
        \AdaPay\AdaPay::init($config_array, "live", true);
        $payment = new \AdaPaySdk\Payment();

# 支付设置
        $payment_params = array(
            'app_id'=> $parameter["account_key"]["memberid"],
            'order_no'=> $parameter['sysordernumber'],
            'pay_channel'=> 'alipay_qr',
            'pay_amt'=> sprintf("%.2f",$parameter["ordermoney"]),
            'goods_title'=> '订单:'.$parameter['userordernumber'],
            'goods_desc'=> '订单:'.$parameter['userordernumber'],
            'notify_url' => $parameter["notifyurl"]
        );
# 发起支付
        $payment->create($payment_params);

# 对支付结果进行处理
        if ($payment->isError()){
            //失败处理
            dump($payment_params);
            var_dump($payment->result);
        } else {
            //成功处理
            M()->startTrans();
            $order = M('order');
            if(M('order')->lock(true)->where("sysordernumber='" . $parameter["sysordernumber"] . "'")->save(['shangyouordernumber'=>$payment->result['id']])){
                M()->commit();
            }else{
                M()->rollback();
                exit('写入上游商家订单号失败');
            }
            if(I('qrcode') == 'url'){
                $this->ajaxReturn([
                    'qrcode' => $payment->result['expend']['qrcode_url']
                ], 'json');
            }else{

                if(parent::isMobile()){
                    $alipayurl = 'alipayqr://platformapi/startapp?saId=10000007&clientVersion=3.7.0.0718&qrcode='.$payment->result['expend']['qrcode_url'];
                    header("Location:".$alipayurl);
                }else{
                    parent::Qrcode($payment->result['expend']['qrcode_url'],$parameter["sysordernumber"],$parameter["userordernumber"],$this->PayName,$parameter["ordermoney"]);
                }


            }

        }
        //--------------------------------------分割线---------------------------------------------------

    }


    /**
     * 异步回调地址
     */
    public function notifyurl()
    {

//file_put_contents('huifuaa.txt',json_encode($_POST)."\n",FILE_APPEND);

        $arr_data = json_decode($_POST['data'],true);
        $config = parent::getPayapiAccountKey($arr_data['order_no']);
        $key = "-----BEGIN PUBLIC KEY-----\n".wordwrap($config['upstreamkeystr'], 64, "\n", true)."\n-----END PUBLIC KEY-----";
        if (openssl_verify($_POST['data'], base64_decode($_POST['sign']), $key)){
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


}