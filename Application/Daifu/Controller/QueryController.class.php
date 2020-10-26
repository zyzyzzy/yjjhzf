<?php

namespace Daifu\Controller;

use Admin\Model\PayapiaccountModel;

use Admin\Model\SecretkeyModel;

use Daifu\Model\BlackbanknumModel;

use Daifu\Model\BlackidcardModel;

use Daifu\Model\BlacktelModel;

use Daifu\Model\SettleconfigModel;

use Daifu\Model\SettleModel;

use Daifu\Model\UsermoneyModel;

use Think\Controller;

class QueryController extends Controller
{
    protected $error = [];

    private $mustFields = [

        "userordernumber",          //结算订单号

        "memberid",         //商户号

        "nonce_str",

        'type',

        'sign'

    ];

    protected $settle;

    protected $nonce_str;

    protected $memberid;

    protected $userid;



    protected $type;

    protected $signStr;

    protected $sign;

    protected $signature;



    public function index()
    {
        $data = $_POST;

        $this->checkParam($data);



        if (!$this->Sign()) {
            $this->ajaxReturn([

                "data" => [],

                'status' => '03',

                'msg' => '签名验证失败!',

            ], "json", JSON_UNESCAPED_UNICODE);
        }

        $response = [

            "ordernumber"=>$this->settle['ordernumber'],

            "userordernumber"=>$this->settle['userordernumber'],

            "memberid"=>$this->settle['memberid'],

            "applytime"=>$this->settle['applytime'],

            "ordermoney"=>$this->settle['ordermoney'],

            "remarks"=>$this->settle['remarks'],

        ];

        if ($this->settle['status']==0) {
            $response['status'] ='0';

            $response['order_msg'] ='未处理';
        } elseif ($this->settle['status']==1) {
            $response['status'] ='1';

            $response['order_msg'] ='处理中';
        } elseif ($this->settle['status']==2) {
            $response['status'] ='2';

            $response['order_msg'] ='已打款';
        } elseif ($this->settle['status']==3) {
            $response['status'] ='3';

            $response['order_msg'] ='退款中';
        } elseif ($this->settle['status']==4) {
            $response['status'] ='4';

            $response['order_msg'] ='已退款';

            $response['refundmoney'] =$this->settle['refundmoney'];
        }





        ksort($response);

        $md5key = SecretkeyModel::getUserMd5Key($this->userid);

        $signstr = '';

        foreach ($response as $key => $val) {
            if ($val == "" || $val == null) {
                continue;
            }

            $signstr .= $key."=".$val."&";
        }

        $signstr = trim($signstr, '&');

        $response['sign'] = strtoupper(md5($signstr.$md5key));









        $this->ajaxReturn($response, 'json', JSON_UNESCAPED_UNICODE);
    }



    public function checkParam($data)
    {
        ksort($this->mustFields);

        $str = '';

        foreach ($this->mustFields as $field) {
            if (isset($data[$field])) {
                if ($data[$field] == null || $data[$field] == '') {
                    $this->error = [

                        "data" => [],

                        'status' => '01',

                        'msg' => '参数' . $field . '不为空!',

                    ];
                } else {
                    $fun = "check" . ucfirst($field);

                    $param = trim($data[$field]);

                    $this->$fun($param);
                }
            } else {
                $this->error = [

                    "data" => [],

                    'status' => '01',

                    'msg' => '参数' . $field . '必传',

                ];
            }

            if (count($this->error) > 0) {
                $this->ajaxReturn($this->error, "json", JSON_UNESCAPED_UNICODE);
            }

            if ($field != 'sign' && $field != 'type') {
                $str .= $field . '=' . $data[$field] . '&';
            }
        }



        $this->signStr = trim($str, '&');
    }





    protected function checkUserordernumber($userordernumber)
    {
        $result = SettleModel::getInfoByUserordernumber($userordernumber);

        if (!$result) {
            $this->error = [

                "data" => [

                ],

                'status' => '02',

                'msg' => '订单号不存在!',

            ];
        }

        $this->settle = $result;

        return $this;
    }



    protected function checkMemberid($memberid)
    {
        $secretkey = SecretkeyModel::getSecretkeyByMemberid($memberid);

        if (!$secretkey) {
            $this->error = [

                "data" => [

                    'memberid' => $memberid

                ],

                'status' => '02',

                'msg' => '商户号错误!',

            ];
        }

        $this->memberid = $memberid;

        $this->userid = $secretkey['userid'];

        return $this;
    }



    public function CheckType($type)
    {
        if ($type != 'MD5' && $type != 'RSA') {
            $this->error = [

                "data" => [

                    'type' => $type

                ],

                'status' => '02',

                'msg' => '参数type错误!',

            ];
        }

        $this->type = $type;

        return $this;
    }



    public function checkNonce_str($nonce_str)
    {
        $this->nonce_str = $nonce_str;

        return $this;
    }



    public function checkSign($sign)
    {
        $this->sign = $sign;

        return $this;
    }





    public function Sign()
    {
        if ($this->type == 'MD5') {
            $md5key = SecretkeyModel::getUserMd5Key($this->userid);

            $sign = md5($this->signStr . "&key=" . $md5key);

            $this->signature = $sign;

            if ($sign == $this->sign) {
                return true;
            } else {
                return false;
            }
        } elseif ($this->type == 'RSA') {
            $pubKey = SecretkeyModel::getUserPubKey($this->userid);

            $data = $this->signStr;

            $priKey = SecretkeyModel::getUserPriKey($this->userid);

            openssl_sign($data, $sign, $priKey);



            $this->signature = base64_encode($sign);

            return $result = (bool)openssl_verify($data, base64_decode($this->sign), $pubKey);
        }
    }
}
