<?php

namespace AdaPaySdk;
use AdaPay\AdaPay;

class Member extends AdaPay
{
    public $endpoint = "/v1/members";
    public $customer = NULL;

    public function __construct()
    {
        parent::__construct();
    }

    public function create($params=array()){
        $request_params = $params;
        $request_params = $this->do_empty_data($request_params);
        $req_url =  self::$gateWayUrl . $this->endpoint;
        $header =  $this->get_request_header($req_url, $request_params, self::$header);
        $this->result = $this->ada_request->curl_request($req_url, $request_params, $header, $is_json=true);
    }

    public function query($params=array()){
        $request_params = $params;
        ksort($request_params);
        $request_params = $this->do_empty_data($request_params);
        $req_url =  self::$gateWayUrl . $this->endpoint . "/" . $request_params['member_id'];
        $header = $this->get_request_header($req_url, http_build_query($request_params), self::$headerText);
        $this->result = $this->ada_request->curl_request($req_url . "?" . http_build_query($request_params), "", $header, false);
    }

    public function update($params=array()){
        $request_params = $params;
        $request_params = $this->do_empty_data($request_params);
        $req_url =  self::$gateWayUrl . $this->endpoint . '/update';
        $header =  $this->get_request_header($req_url, $request_params, self::$header);
        $this->result = $this->ada_request->curl_request($req_url, $request_params, $header, $is_json=true);
    }

    public function query_list($params=array()){
        $request_params = $params;
        $req_url =  self::$gateWayUrl . $this->endpoint . "/list";
        $header = $this->get_request_header($req_url, http_build_query($request_params), self::$headerText);
        $this->result = $this->ada_request->curl_request($req_url . "?" . http_build_query($request_params), "", $header, false);
    }

}