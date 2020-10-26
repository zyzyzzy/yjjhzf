<?php
require_once "WxPay.Config.php";
/**
 * 
 * 刷卡支付实现类
 * @author widyhu
 *
 */
class NativePay
{
	/**
	 * 
	 * 生成扫描支付URL,模式一
	 * @param BizPayUrlInput $bizUrlInfo
	 */
    private $appid = "";
    private $mchid = "";
    private $key = "";
    private $appsecret = "";
    public function __construct($appid="",$mchid="",$key="",$appsecret="")
    {
        $this->appid = $appid;
        $this->mchid = $mchid;
        $this->key = $key;
        $this->appsecret = $appsecret;

    }
	public function GetPrePayUrl($productId)
	{
		$biz = new WxPayBizPayUrl();
		$biz->SetProduct_id($productId);
		try{
			$config = new WxPayConfig($this->appid,$this->mchid,$this->key,$this->appsecret);
			$values = WxpayApi::bizpayurl($config, $biz);
		} catch(Exception $e) {
			Log::ERROR(json_encode($e));
		}
		$url = "weixin://wxpay/bizpayurl?" . $this->ToUrlParams($values);
		return $url;
	}
	
	/**
	 * 
	 * 参数数组转换为url参数
	 * @param array $urlObj
	 */
	private function ToUrlParams($urlObj)
	{
		$buff = "";
		foreach ($urlObj as $k => $v)
		{
			$buff .= $k . "=" . $v . "&";
		}
		
		$buff = trim($buff, "&");
		return $buff;
	}
	
	/**
	 * 
	 * 生成直接支付url，支付url有效期为2小时,模式二
	 * @param UnifiedOrderInput $input
	 */
	public function GetPayUrl($input)
	{
		if($input->GetTrade_type() == "NATIVE")
		{
			try{
				$config = new WxPayConfig($this->appid,$this->mchid,$this->key,$this->appsecret);
				$result = WxPayApi::unifiedOrder($config, $input);
				return $result;
			} catch(Exception $e) {
				Log::ERROR(json_encode($e));
			}
		}
		return false;
	}
}