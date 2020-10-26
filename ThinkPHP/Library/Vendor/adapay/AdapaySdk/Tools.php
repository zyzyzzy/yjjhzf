<?php

namespace AdaPaySdk;
use AdaPay\AdaPay;

class Tools extends AdaPay
{
    public $endpoint = "/v1/bill/download";
    public $union_endpoint = "/v1/union/user_identity";
    public $billDownload = NULL;
    public function __construct()
    {
        parent::__construct();
    }

    public function download($params=array()) {
        $request_params = $params;
        $request_params = $this->do_empty_data($request_params);
        $req_url = AdaPay::$gateWayUrl . $this->endpoint;
        $header =  $this->get_request_header($req_url, $request_params, AdaPay::$header);
        $this->result = $this->ada_request->curl_request($req_url, $request_params, $header, $is_json=true);
    }

    public function unionUserId($params=array()){
        $request_params = $params;
        $request_params = $this->do_empty_data($request_params);
        $req_url =  self::$gateWayUrl . $this->union_endpoint;
        $header =  $this->get_request_header($req_url, $request_params, self::$header);
        $this->result = $this->ada_request->curl_request($req_url, $request_params, $header, $is_json=true);
    }
}