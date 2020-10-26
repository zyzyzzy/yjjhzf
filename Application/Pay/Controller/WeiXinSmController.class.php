<?php

namespace Pay\Controller;

use Think\Controller;


class WeiXinSmController extends PayController {

    protected $PayName = "WeiXinSm";  //通道编码
    protected $CheckDomain = false;  //是否判断提交域名
    protected $checkStatus = true;  //通道状态
    protected $sysordernumberleng = 32; //体系订单号长度
    public function __construct()
    {
        parent::__construct();

    }

    public function pay($parameter){
        Vendor('WeiXin.WxPayApi');
        Vendor('WeiXin.WxPayNativePay');
        $notify = new \NativePay($parameter["account_key"]["memberid"],$parameter["account_key"]["account"],$parameter["account_key"]["md5keystr"]);
        $input = new \WxPayUnifiedOrder();
        $input->SetBody("付款");
        $input->SetAttach("");
        $input->SetOut_trade_no(trim($parameter["sysordernumber"]));
        $input->SetTotal_fee(floatval($parameter["ordermoney"])*100);
        $input->SetTime_start(date("YmdHis"));
        $input->SetTime_expire(date("YmdHis", time() + 600));
        $input->SetGoods_tag("");
        $input->SetNotify_url($parameter["notifyurl"]);
        $input->SetTrade_type("NATIVE");
        $input->SetProduct_id(randpw(32));
        $result = $notify->GetPayUrl($input);
        $url2 = $result["code_url"];
     //   exit($url2);
        parent::Qrcode($url2,$parameter["sysordernumber"],$parameter["userordernumber"],$this->PayName,$parameter["ordermoney"]);
    }

    public function notifyurl(){  //异步回调地址
        libxml_disable_entity_loader(true);
        $arr = json_decode(json_encode(simplexml_load_string($GLOBALS['HTTP_RAW_POST_DATA'], 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        $out_trade_no = $arr["out_trade_no"];
        $account_key = parent::getPayapiAccountKey($out_trade_no);
        Vendor('WeiXin.WxPay');
        $wxpay = new \WxPay();
        if($arr["return_code"] == "SUCCESS" and $wxpay->CheckSign($account_key,$arr) and $arr["result_code"] == "SUCCESS"){
            $return_echo = $wxpay->ToXml(['return_code'=>'SUCCESS','return_msg'=>'OK']);
            parent::editMoney($out_trade_no, $this->PayName, 0, $arr["total_fee"]/100, $return_echo);
        }

    }

    public function callbackurl(){  //同步跳转地址

    }

    public function queryPayOrder($sysordernumber){
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
        $arr["sign"] = $wxpay->MakeSign($account_key,$arr,$arr["sign_type"]);
        $xml = $wxpay->ToXml($arr);
        $return_xml = $wxpay->postXmlCurl($account_key,$xml,$url);
        libxml_disable_entity_loader(true);
        $arr = json_decode(json_encode(simplexml_load_string($return_xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        if($arr["return_code"] == "SUCCESS" and $wxpay->CheckSign($account_key,$arr) and $arr["result_code"] == "SUCCESS" and $arr["trade_state"] == "SUCCESS"){
            return true;
        }else{
            return false;
        }
    }


}