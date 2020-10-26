<?php
/**
 * AdaPay SDK 支付设置
 * Date: 2019/09/05 18:05
 */

namespace AdaPayMerchant;

use AdaPay\AdaPay;

class MerchantConf extends AdaPay {

    public $endpoint = "/v1/batchInput/merConf";
    public $m_endpoint = "/v1/batchInput/merResidentModify";

    public function __construct()
    {
        parent::__construct();
    }

    public function create($params=array()){
        $request_params = $params;
        $req_url = self::$gateWayUrl.$this->endpoint;
        $header =  $this->get_request_header($req_url, $request_params, self::$header);
        $this->result = $this->ada_request->curl_request($req_url, $request_params, $header, $is_json=true);
    }

    public function query($params=array()){
        $request_params = $params;
        $req_url = self::$gateWayUrl.$this->endpoint;
        $header = $this->get_request_header($req_url, http_build_query($request_params),  self::$headerText);
        $this->result = $this->ada_request->curl_request($req_url. '?' .http_build_query($request_params), '', $header, false);
    }

    public function modify($params=array()){
        $request_params = $params;
        $req_url = self::$gateWayUrl.$this->m_endpoint;
        $header =  $this->get_request_header($req_url, $request_params, self::$header);
        $this->result = $this->ada_request->curl_request($req_url, $request_params, $header, $is_json=true);
    }
}