<?php
/**
 * AdaPay SDK 商户开户和进件
 * Date: 2019/09/05 18:05
 */

namespace AdaPayMerchant;

use AdaPay\AdaPay;

class MerchantUser extends AdaPay {

    public $endpoint = "/v1/batchEntrys/userEntry";
    public $no_endpoint = "/v1/batchEntrys/userNoCardEntry";

    public function __construct()
    {
        parent::__construct();
    }

    //商户开户
    public function create($params=array()){
        $request_params = $params;
        if (isset($request_params['isNoCard']) && $request_params['isNoCard'] == 1){
            $req_url = self::$gateWayUrl.$this->no_endpoint;
            unset($request_params['isNoCard']);
        }else{
            $req_url = self::$gateWayUrl.$this->endpoint;
        }
        $header =  $this->get_request_header($req_url, $request_params, self::$header);
        $this->result = $this->ada_request->curl_request($req_url, $request_params, $header, $is_json=true);
    }

    public function query($params=array()){
        $request_params = $params;
        $req_url = self::$gateWayUrl.$this->endpoint;
        $header = $this->get_request_header($req_url, http_build_query($request_params),  self::$headerText);
        $this->result = $this->ada_request->curl_request($req_url. '?' .http_build_query($request_params), '', $header, false);
    }
}