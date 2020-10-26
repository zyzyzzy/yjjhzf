<?php
namespace AdaPaySdk;
use AdaPay\AdaPay;


class Refund extends AdaPay
{

    public $refundOrder = NULL;
    public $refundOrderQuery = NULL;
    public $endpoint = "/v1/payments";

    public function __construct()
    {
        parent::__construct();
    }

    public function orderRefund($params=array()){
        $request_params = $params;
        $charge_id = isset($params['payment_id']) ? $params['payment_id'] : '';
        $request_params = $this->do_empty_data($request_params);
        $req_url = self::$gateWayUrl .$this->endpoint."/". $charge_id. "/refunds";
        $header =  $this->get_request_header($req_url, $request_params, self::$header);
        $this->result = $this->ada_request->curl_request($req_url, $request_params, $header, $is_json=true);
    }

    public function orderRefundQuery($params=array()){
        $request_params = $params;
        $request_params = $this->do_empty_data($request_params);
        $req_url = self::$gateWayUrl .$this->endpoint."/refunds";
        $header = $this->get_request_header($req_url, http_build_query($request_params), self::$headerText);
        $this->result = $this->ada_request->curl_request($req_url."?".http_build_query($request_params), "", $header, false);
    }
}