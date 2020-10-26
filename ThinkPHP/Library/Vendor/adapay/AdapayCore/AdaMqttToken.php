<?php
namespace AdaPay;

class AdaMqttToken extends AdaPay
{

    public $endpoint = "/v1/token/apply";

    public function __construct()
    {
        parent::__construct();
    }

    public function getToken(){
        $token = "";
        $request_params = array("expire_time"=>time()+86400);
        $req_url = AdaPay::$gateWayUrl.$this->endpoint;

        $header = $this->get_request_header($req_url, $request_params, self::$header);
        $result = $this->ada_request->curl_request($req_url, $request_params, $header, $is_json=true);

        if (isset($result[0]) && isset($result[1]) && $result[0] == 200){
            $result_data = [];
            $temp_result = json_decode($result[1], true);
            if (isset($temp_result['data'])){
                $result_data = json_decode($temp_result['data'] , true);
            }
            if(isset($result_data['token'])){
                $token = $result_data['token'];
            }
        }
        return $token;
    }
}