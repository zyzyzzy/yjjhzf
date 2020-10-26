<?php
namespace AdaPaySdk;
use AdaPay\AdaPay;

class Payment extends AdaPay{

    public $endpoint = "/v1/payments";
    public $payPayments = NULL;
    public $closeOrder = NULL;
    public $paymentOrder = NULL;

    public function __construct()
    {
        parent::__construct();
    }

    public function create($params=array()){
        $params['currency'] = 'cny';
        $params['sign_type'] = 'RSA2';
        $request_params = $params;
        $request_params = $this->do_empty_data($request_params);
        $req_url =  self::$gateWayUrl .$this->endpoint;
        $header =  $this->get_request_header($req_url, $request_params, self::$header);
        $this->result = $this->ada_request->curl_request($req_url, $request_params, $header, $is_json=true);

    }
    
    public function orderClose($params=array()){
        $request_params = $params;
        $id = isset($params['payment_id']) ? $params['payment_id'] : '';
        $request_params = $this->do_empty_data($request_params);
        $req_url =  self::$gateWayUrl .$this->endpoint."/". $id. "/close";
        $header =  $this->get_request_header($req_url, $request_params, self::$header);
        $this->result = $this->ada_request->curl_request($req_url, $request_params, $header, $is_json=true);
    }

    public function orderQuery($params=array()){
        $id = isset($params['payment_id']) ? $params['payment_id'] : '';
        $req_url =  self::$gateWayUrl .$this->endpoint."/".$id;
        $header = $this->get_request_header($req_url, "",  self::$headerText);
        $this->result = $this->ada_request->curl_request($req_url, null, $header, false);
    }

    public function createReverse($params=array()){
        $request_params = $params;
        $req_url =  self::$gateWayUrl .$this->endpoint."/reverse";
        $header =  $this->get_request_header($req_url, $request_params, self::$header);
        $this->result = $this->ada_request->curl_request($req_url, $request_params, $header, $is_json=true);
    }

    public function queryReverse($params=array()){
        ksort($params);
        $request_params = $params;
        $req_url =  self::$gateWayUrl . $this->endpoint . "/reverse/" . $request_params['reverse_id'];
        $header = $this->get_request_header($req_url, http_build_query($request_params), self::$headerText);
        $this->result = $this->ada_request->curl_request($req_url . "?" . http_build_query($request_params), "", $header, false);
    }

    public function queryReverseList($params=array()){
        ksort($params);
        $request_params = $params;
        $request_params = $this->do_empty_data($request_params);
        $req_url =  self::$gateWayUrl . $this->endpoint . "/reverse/list";
        $header = $this->get_request_header($req_url, http_build_query($request_params), self::$headerText);
        $this->result = $this->ada_request->curl_request($req_url . "?" . http_build_query($request_params), "", $header, false);
    }

    public function createConfirm($params=array()){
        $request_params = $params;
        $req_url =  self::$gateWayUrl .$this->endpoint."/confirm";
        $header =  $this->get_request_header($req_url, $request_params, self::$header);
        $this->result = $this->ada_request->curl_request($req_url, $request_params, $header, $is_json=true);
    }

    public function queryConfirm($params=array()){
        ksort($params);
        $request_params = $params;
        $request_params = $this->do_empty_data($request_params);
        $req_url =  self::$gateWayUrl . $this->endpoint . "/confirm/" . $request_params['payment_confirm_id'];
        $header = $this->get_request_header($req_url, http_build_query($request_params), self::$headerText);
        $this->result = $this->ada_request->curl_request($req_url . "?" . http_build_query($request_params), "", $header, false);
    }

    public function queryConfirmList($params=array()){
        ksort($params);
        $request_params = $params;
        $request_params = $this->do_empty_data($request_params);
        $req_url =  self::$gateWayUrl . $this->endpoint . "/reverse/list";
        $header = $this->get_request_header($req_url, http_build_query($request_params), self::$headerText);
        $this->result = $this->ada_request->curl_request($req_url . "?" . http_build_query($request_params), "", $header, false);
    }

    public function notifyurlData(){
        $this->result = $_POST;
    }
}