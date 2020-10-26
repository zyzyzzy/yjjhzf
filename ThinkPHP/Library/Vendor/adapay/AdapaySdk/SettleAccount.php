<?php

namespace AdaPaySdk;
use AdaPay\AdaPay;

class SettleAccount extends AdaPay
{
    public $endpoint = "/v1/settle_accounts";
    public $cash_endpoint = "/v1/cashs";
    public $settle = NULL;

    public function __construct()
    {
        parent::__construct();
    }

    public function create_settle($params=array()){
        $request_params = $params;
        $request_params = $this->do_empty_data($request_params);
        $req_url = self::$gateWayUrl.$this->endpoint;
        $header =  $this->get_request_header($req_url, $request_params, self::$header);
        $this->result = $this->ada_request->curl_request($req_url, $request_params, $header, $is_json=true);
    }

    public function query_settle($params=array()){
        $request_params = $params;
        $settle_account_id = isset($params['settle_account_id']) ? $params['settle_account_id']: '';
        ksort($request_params);
        $request_params = $this->do_empty_data($request_params);
        $req_url = self::$gateWayUrl.$this->endpoint."/" . $settle_account_id;
        $header = $this->get_request_header($req_url, http_build_query($request_params), self::$headerText);
        $this->result = $this->ada_request->curl_request($req_url."?".http_build_query($request_params), "", $header, false);
    }

    public function delete_settle($params=array()){
        $request_params = $params;
        $request_params = $this->do_empty_data($request_params);
        $req_url = self::$gateWayUrl.$this->endpoint."/delete";
        $header = $this->get_request_header($req_url, $request_params, self::$header);
        $this->result = $this->ada_request->curl_request($req_url, $request_params, $header, $is_json=true);
    }

    public function query_settle_details($params=array()){
        $request_params = $params;
        ksort($request_params);
        $request_params = $this->do_empty_data($request_params);
        $req_url = self::$gateWayUrl.$this->endpoint."/settle_details";
        $header = $this->get_request_header($req_url, http_build_query($request_params), self::$headerText);
        $this->result = $this->ada_request->curl_request($req_url."?".http_build_query($request_params), "", $header, false);
    }

    public function modify_settle($params=array()){
        $request_params = $params;
        $request_params = $this->do_empty_data($request_params);
        $req_url = self::$gateWayUrl.$this->endpoint."/modify";
        $header =  $this->get_request_header($req_url, $request_params, self::$header);
        $this->result = $this->ada_request->curl_request($req_url, $request_params, $header, $is_json=true);
    }

    public function query_balance($params=array()){
        $request_params = $params;
        ksort($request_params);
        $request_params = $this->do_empty_data($request_params);
        $req_url = self::$gateWayUrl.$this->endpoint."/balance";
        $header = $this->get_request_header($req_url, http_build_query($request_params), self::$headerText);
        $this->result = $this->ada_request->curl_request($req_url."?".http_build_query($request_params), "", $header, false);
    }

    public function draw_cash($params=array()){
        $request_params = $params;
        $request_params = $this->do_empty_data($request_params);
        $req_url = self::$gateWayUrl.$this->cash_endpoint;
        $header =  $this->get_request_header($req_url, $request_params, self::$header);
        $this->result = $this->ada_request->curl_request($req_url, $request_params, $header, $is_json=true);
    }
}