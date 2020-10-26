<?php
namespace Daifu\Controller;

use Admin\Model\SecretkeyModel;
use Daifu\Model\SettleModel;
use Think\Controller;

class RefundController extends Controller
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
    protected $memberid;
    protected $userordernumber;
    protected $userid;
    protected $nonce_str;

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
        //逻辑处理完成  将订单状态改为 3 (退款中)  等待管理员审核退款
        $status = M('settle')->where([
            'userordernumber' => ['eq', $this->userordernumber],
            'userid' => ['eq', $this->userid],
        ])->getField('status');

        if ($status != 3) {
            $result = M('settle')->where([
                'userordernumber' => ['eq', $this->userordernumber],
                'userid' => ['eq', $this->userid],
            ])->setField('status', 3);
            if ($result) {
                $this->ajaxReturn([
                    "data" => [
                        'userordernumber' => $this->userordernumber,
                        'memberid' => $this->memberid,
                        'nonce_str' => $this->nonce_str,
                    ],
                    'status' => '00',
                    'msg' => '发起退款成功!',
                ], "json", JSON_UNESCAPED_UNICODE);
            } else {
                $this->ajaxReturn([
                    "data" => [
                    ],
                    'status' => '04',
                    'msg' => '发起退款失败!',
                ], "json", JSON_UNESCAPED_UNICODE);
            }
        }else{
            $this->ajaxReturn([
                "data" => [
                    'userordernumber' => $this->userordernumber,
                    'memberid' => $this->memberid,
                    'nonce_str' => $this->nonce_str,
                ],
                'status' => '01',
                'msg' => '请勿重复发起退款!',
            ], "json", JSON_UNESCAPED_UNICODE);
        }
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
        $this->userordernumber = $userordernumber;
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