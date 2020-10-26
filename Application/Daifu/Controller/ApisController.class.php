<?php
namespace Daifu\Controller;

use Admin\Model\PayapiaccountModel;
use Admin\Model\SecretkeyModel;
use Daifu\Model\BlackbanknumModel;
use Daifu\Model\BlackdomainModel;
use Daifu\Model\BlackidcardModel;
use Daifu\Model\BlackipModel;
use Daifu\Model\BlacktelModel;
use Daifu\Model\DaifuModel;
use Daifu\Model\SettleconfigModel;
use Daifu\Model\SettleModel;
use Daifu\Model\UsermoneyModel;
use Think\Controller;

class ApisController extends Controller
{
    private $mustFields = [
        'memberid',
        'time',
        'content',
        'type',
        'sign'
    ];
    private $memberid;
    private $content;
    private $type;
    private $sign;

    private $userid;
    private $daifu;

    public $msgs = [];
    public $data = [
        "userordernumber" => '',          //结算订单号
        "ordernumber" => '',          //结算订单号
        "memberid" => '',         //商户号
        "bankname" => '',         //银行名称
        "bankzhiname" => '',          //银行支行
        "bankcode" => '',         //银行编码
        "bankcardnumber" => '',           //银行卡号
        "bankusername" => '',         //银行卡户名
        "identitynumber" => '',           //身份证号
        "phonenumber" => '',          //手机号
        "province" => '',         //省
        "city" => '',         //市

        "banknumber" => '',           //银行联行号
        "applytime" => '',            //datetime	申请时间
        "ordermoney" => '',           //decimal	15	4	提款金额
        "remarks" => '',          //text	65535备注

        "userid" => '',           //用户id
        //"dealtime" => '',         //datetime	处理时间
        "type" => 0,         //tinyint0	类型  0：T + 0   1：T + 1
        "status" => 0,           //tinyint0	状态 0：未处理 1：处理中  2：已打款
    ];

    protected $contents = [
        "userordernumber",          //结算订单号
        "bankname",         //银行名称
        "bankzhiname",          //银行支行
        "bankcode",         //银行编码
        "bankcardnumber",           //银行卡号
        "bankusername",         //银行卡户名
        "identitynumber",           //身份证号
        "phonenumber",          //手机号
        "province",         //省
        "city",         //市
        "ordermoney",
    ];
    protected $settleMoney = [
        "ordernumber" => '',  //结算订单号
        "ordermoney" => '',   //提款金额
        "traderate" => '',    //交易费率
        "moneytrade" => '',   //交易手续费
        "costrate" => '',     //成本费率
        "moneycost" => '',    //成本金额
        "profit" => '',       //利润
        "money" => '',        //到账金额
        "shangjiaid" => '',   //商家id
        "accountid" => '',    //商家账号id
        "daifuid" => '',      //商家代付通道id
        "deduction_type" => '',   //手续费扣款类型 0：内扣 1：外扣

    ];
    protected $changeMoney;
    private $settleconfig;
    private $usermoney;
    protected $error = [];

    protected $time;
    protected $signStr;
    protected $signature;
    private $noMustFields = [
        'remarks', 'banknumber',
    ];


    public function index()
    {
        $data = $_POST;

        //验证代付时间
//        $checkIsBetweenTime = $this->checkIsBetweenTime($this->settleconfig['day_start'], $this->settleconfig['day_end']);
//
//        if (!$checkIsBetweenTime) {
//            $this->ajaxReturn([
//                "data" => [],
//                'status' => '03',
//                'msg' => '结算功能关闭!请在结算时间申请结算!',
//            ], "json", JSON_UNESCAPED_UNICODE);
//        }

        $this->checkParam($data);

        if (!$this->Sign()) {
            $this->ajaxReturn([
                "data" => [],
                'status' => '03',
                'msg' => '签名验证失败!',
            ], "json", JSON_UNESCAPED_UNICODE);
        }

        $this->checkDaifu();
        if($this->daifu['status']==0){
            $this->ajaxReturn([
                'status' => '04',
                'msg' => '结算通道未开启!请联系管理员!',
                'data' => []
            ],'json',JSON_UNESCAPED_UNICODE);
        }

        $account = PayapiaccountModel::getInfo($this->settleconfig['account_id']);

        if($account['status']==0){
            $this->ajaxReturn([
                'status' => '04',
                'msg' => '结算账号未开启!请联系管理员!',
                'data' => []
            ],'json',JSON_UNESCAPED_UNICODE);
        }

        $arr=[];
        foreach ($this->content as $item) {
            foreach ($this->contents as $key=>$value) {
                if (!isset($item[$value])) {
                    $this->msgs = [
                        'status' => '04',
                        'msg' => '参数' . $value . '未传!',
                        'userordernumber' => $item['userordernumber'],
                    ];
                    break;
                }
                if ($item[$value] == null || $item[$value] == '') {
                    $this->msgs = [
                        'status' => '04',
                        'msg' => '参数' . $value . '不为空!',
                        'userordernumber' => $item['userordernumber'],
                    ];
                    break;
                }

                $this->data[$value] = '';
                $fun = "check" . ucfirst($value);
                $param = trim($item[$value]);
                $this->$fun($param);

                if (count($this->msgs) > 0) {
                    break;
                }
            }
            foreach ($this->noMustFields as $field) {
                $fun = "check" . ucfirst($field);
                $param = trim($item[$field]);
                $this->$fun($param);
            }

            if (count($this->msgs) > 0) {
                $arr[]=$this->msgs;
                $this->msgs=[];
                continue;
            }

            $this->data['applytime'] = date('Y-m-d H:i:s');
            $this->data['memberid'] = $this->memberid;
            $this->data['userid'] = $this->userid;


            M()->startTrans();
            $settle = M('settle')->add($this->data);

            $usermoney = M('usermoney')->where([
                'userid' => ['eq', $this->data['userid']]
            ])->save(['money' => $this->usermoney['money'] - $this->changeMoney]);
            $settlemoney = M('settlemoney')->add($this->settleMoney);
            $money_change = M('moneychange')->add([
                'userid' => $this->data['userid'],     //0	√用户ID
                'oldmoney' => $this->usermoney['money'],       //	15	4	√原始金额
                'changemoney' => $this->changeMoney,        //	15	4	√改变金额
                'nowmoney' => $this->usermoney['money'] - $this->changeMoney,       //	15	4	√改变后的金额
                'datetime' => date('Y-m-d H:i:s'),       //	√添加时间
                'transid' => $this->data['ordernumber'],        //	100√订单号
                'payapiid' => $this->settleMoney['daifuid'],       //0	√通道id
                'accountid' => $this->settleMoney['accountid'],      //0	√账号id
                'changetype' => 5,     //	100√金额变动类型
                'tcuserid' => '',       //0	√提成用户id
                'tcdengji' => '',       //0	√提成等级
                'orderid' => '',        //	100√提成订单号
                'remarks' => '代付接口生成订单',        //	500√备注
            ]);
            if ($settle && $usermoney && $settlemoney && $money_change) {
                M()->commit();
                //代付订单生成

                $settleconfig = SettleconfigModel::getInfo($this->data['userid']);

                if($settleconfig['auto_type']==1){
                    $result = A("Daifu/".$this->daifu['en_payname'], 'Controller');
                    //dump($result);
                }


                $arr[]=[
                    'status' => '00',
                    'msg' => '申请成功!',
                    'userordernumber'=>$this->data['userordernumber'],
                ];


            } else {
                M()->rollback();
            }
        }
        $this->ajaxReturn([
            'status' => '00',
            'msg' => '批量代付申请成功!',
            'data' => $arr
        ],'json',JSON_UNESCAPED_UNICODE);
    }

    public function checkParam($data)
    {
        //网站设置判断  总开关是否开启  结算开关是否开启
//        $this->checkWebsite();
        $this->checkIp();
        $this->checkDomain();
        sort($this->mustFields);
        $str = '';
        foreach ($this->mustFields as $field) {
            if (isset($data[$field])) {
                if ($data[$field] == null || $data[$field] == '') {
                    $this->error = [
                        "data" => [],
                        'status' => '01',
                        'msg' => '参数' . $field . '不得为空!',
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

    public function checkType($type)
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

    public function checkSign($sign)
    {
        $this->sign = $sign;
    }

    public function checkContent($content)
    {
        $content = json_decode(base64_decode($content), true);
        if (count($content) < 1) {
            $this->error = [
                "data" => [],
                'status' => '01',
                'msg' => '参数content格式错误!',
            ];
        }
        $this->content = $content;
        return $this;
    }

    public function checkTime($time)
    {
        if (!preg_match('#\d{4}\-[0-1][0-9]\-[0-3][0-9]\s[0-2][0-9]:[0-6][0-9]:[0-6][0-9]#', $time, $match)) {
            $this->error = [
                "data" => [],
                'status' => '01',
                'msg' => '参数content格式错误!',
            ];
        }
        $this->time = $time;
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
            $pubKey = SecretkeyModel::getUserPubKey($this->data['userid']);
            $data = $this->signStr;
            $priKey = SecretkeyModel::getUserPriKey($this->data['userid']);
            openssl_sign($data, $sign, $priKey);

            $this->signature = base64_encode($sign);
            return $result = (bool)openssl_verify($data, base64_decode($this->sign), $pubKey);
        }
    }

    public function checkDaifu()
    {
        $settleconfig = SettleconfigModel::getInfo($this->userid);
        if (!$settleconfig || $settleconfig['user_type'] == 0) {
            $settleconfig = SettleconfigModel::getInfo(0);
        }
        $daifu = DaifuModel::getInfo($settleconfig['daifu_id']);

        $this->daifu =$daifu;
        $this->settleconfig =$settleconfig;
        return $this;
    }

    public function checkAccount()
    {
        return PayapiaccountModel::getInfo($this->settleconfig['account_id']);
    }

    protected function checkUserordernumber($userordernumber)
    {
        $result = SettleModel::checkRepeat($userordernumber,$this->userid);
        if ($result) {
            $this->msgs = [
                'userordernumber' => $userordernumber,
                'status' => '04',
                'msg' => '订单号重复!',
            ];

        }
        $ordernumber =date('YmdHis').randpw(5);
        $this->settleMoney['ordernumber'] = $ordernumber;
        $this->data['userordernumber'] = $userordernumber;
        $this->data['ordernumber'] = $ordernumber;
        return $this;
    }

    public function checkOrdermoney($ordermoney)
    {
        $usermoney = UsermoneyModel::getInfo($this->userid);
        $this->usermoney = $usermoney;

        $settleconfig=$this->settleconfig;
        $payapiaccount = PayapiaccountModel::getInfo($settleconfig['account_id']);

        $cb = $ordermoney * $payapiaccount['settle_cbfeilv'];
        if ($cb < $payapiaccount['settle_min_cbfeilv']) {
            $cb = $payapiaccount['settle_min_cbfeilv'];
        }

        $settle_min_feilv = $settleconfig['settle_min_feilv'];
        $rate = $ordermoney * $settleconfig['settle_feilv'];

        $sxf = $rate;
        if ($settle_min_feilv > $rate) {
            $sxf = $settle_min_feilv;
        }
        $jsje = $ordermoney;    //结算金额
        $money = $ordermoney - $sxf;  //到账金额
        //手续费扣款类型 0：内扣 1：外扣
        if ($settleconfig['deduction_type'] == 1) {
            $jsje = $ordermoney + $sxf;
            $money = $ordermoney;
        }

        if ($settleconfig['min_money'] > $jsje) {
            $this->msgs = [
                'userordernumber' => $this->data['userordernumber'],
                'status' => '04',
                'msg' => '结算金额小于最低结算金额,最小结算金额为' . $settleconfig['min_money'] . '元',
            ];
            return $this;
        }
        if ($jsje > $settleconfig['max_money']) {
            $this->msgs = [
                'userordernumber' => $this->data['userordernumber'],
                'status' => '03',
                'msg' => '结算金额大于最大结算金额,最大结算金额为' . $settleconfig['max_money'] . '元',
            ];
            return $this;
        }
        if ($jsje > $usermoney['money']) {
            $this->msgs = [
                'userordernumber' => $this->data['userordernumber'],
                'status' => '03',
                'msg' => '用户可用余额不足!',
            ];
            return $this;
        }

        if ($settleconfig['status'] == 0) {
            $this->msgs = [
                'userordernumber' => $this->data['userordernumber'],
                'status' => '03',
                'msg' => '当前结算功能未开启!',
            ];
            return $this;
        }

        $count = SettleModel::getCountSettleByUser($this->userid);
        if ($count > $settleconfig['day_maxnum']) {
            $this->msgs = [
                'userordernumber' => $this->data['userordernumber'],
                'status' => '03',
                'msg' => '今日结算总次数' . $settleconfig['day_maxnum'] . "已用完,如还需结算请联系管理员!",
            ];
            return $this;
        }

        $sum_money = SettleModel::getSumMoneyByUser($this->userid);
        if ($sum_money + $jsje > $settleconfig['day_maxmoney']) {
            $this->msgs = [
                'userordernumber' => $this->data['userordernumber'],
                'status' => '03',
                'msg' => '今日结算总金额' . $settleconfig['day_maxmoney'] . "已用完,如还需结算请联系管理员!",
            ];
            return $this;
        }

        $this->data['ordermoney'] = $ordermoney;;

        $this->settleMoney['ordermoney'] = $ordermoney;
        $this->settleMoney['traderate'] = $settleconfig['settle_feilv'];
        $this->settleMoney['moneytrade'] = $sxf;
        $this->settleMoney['money'] = $money;
        $this->settleMoney['accountid'] = $settleconfig['account_id'];
        $this->settleMoney['deduction_type'] = $settleconfig['deduction_type'];
        $this->settleMoney['daifuid'] = $settleconfig['daifu_id'];
        $this->settleMoney['accountid'] = $settleconfig['account_id'];
        $this->settleMoney['shangjiaid'] = $payapiaccount['payapishangjiaid'];
        $this->settleMoney['costrate'] = $payapiaccount['settle_cbfeilv'];
        $this->settleMoney['moneycost'] = $cb;
        $this->settleMoney['profit'] = $sxf - $cb;
        $this->changeMoney = $jsje;
        return $this;
    }

    protected function checkBankname($bankname)
    {
        $this->data['bankname'] = $bankname;
        return $this;
    }

    protected function checkBankzhiname($bankzhiname)
    {
        $this->data['bankzhiname'] = $bankzhiname;
        return $this;
    }

    protected function checkBankcode($bankcode)
    {
        $this->data['bankcode'] = $bankcode;
        return $this;
    }

    protected function checkBankcardnumber($bankcardnumber)
    {
        $result = BlackbanknumModel::getBanks($bankcardnumber);

        if ($result) {
            $this->msgs = [
                'userordernumber' => $this->data['userordernumber'],
                'status' => '02',
                'msg' => '黑名单卡号,无法代付!',
            ];

        }
        $this->data['bankcardnumber'] = $bankcardnumber;
        return $this;
    }

    protected function checkBankusername($bankusername)
    {
        $this->data['bankusername'] = $bankusername;
        return $this;
    }

    protected function checkIdentitynumber($identitynumber)
    {
        if (!preg_match("/(^\d{15}$)|(^\d{18}$)|(^\d{17}(\d|X|x)$)/", $identitynumber)) {
            $this->msgs = [
                'userordernumber' => $this->data['userordernumber'],
                'status' => '02',
                'msg' => '身份证号错误!',
            ];
            return $this;
        }
        $idcard = BlackidcardModel::getIdcard($identitynumber);
        if ($idcard) {
            $this->msgs = [
                'userordernumber' => $this->data['userordernumber'],
                'status' => '02',
                'msg' => '身份证号已经纳入黑名单,无法提现!',
            ];
            return $this;
        }
        $this->data['identitynumber'] = $identitynumber;
        return $this;
    }

    protected function checkPhonenumber($phonenumber)
    {
        if (!preg_match("/^1[34578]\d{9}$/", $phonenumber)) {
            $this->msgs = [
                'userordernumber' => $this->data['userordernumber'],
                'status' => '02',
                'msg' => '手机号错误!',
            ];
            return $this;
        }
        $tel = BlacktelModel::getTel($phonenumber);
        if ($tel) {
            $this->msgs = [
                'userordernumber' => $this->data['userordernumber'],
                'status' => '02',
                'msg' => '手机号已经纳入黑名单,无法提现!',
            ];
            return $this;
        }
        $this->data['phonenumber'] = $phonenumber;
        return $this;
    }

    protected function checkProvince($province)
    {
        $this->data['province'] = $province;
        return $this;
    }

    protected function checkCity($city)
    {
        $this->data['city'] = $city;
        return $this;
    }


    public function checkIsBetweenTime($start, $end)
    {
        $date = date('H:i');
        $curTime = strtotime($date);//当前时分
        $assignTime1 = strtotime($start);//获得指定分钟时间戳，00:00
        $assignTime2 = strtotime($end);//获得指定分钟时间戳，01:00
        $result = 0;
        if ($curTime > $assignTime1 && $curTime < $assignTime2) {
            $result = 1;
        }
        return $result;
    }

    public function checkRemarks($remarks)
    {
        $this->data['remarks'] = $remarks;
        return $this;
    }

    public function checkBanknumber($banknumber)
    {
        $this->data['banknumber'] = $banknumber;
        return $this;
    }

    public function checkIp()
    {
        $user_ip = getIp();
        $result =BlackipModel::getIp($user_ip);

        if($result){
            $this->error = [
                'status' => '02',
                'msg' => '黑名单ip,无法代付!',
            ];
            addBlackRecord($this->data['userid'],'user',1,$user_ip,3,json_encode($_POST));
            $this->ajaxReturn($this->error, "json", JSON_UNESCAPED_UNICODE);
        }
    }

    public function checkDomain()
    {
        $user_domain = $_SERVER['HTTP_REFERER'];
        $result = BlackdomainModel::checkDomain($user_domain);
        if($result){
            $this->error = [
                'status' => '02',
                'msg' => '黑名单域名,无法代付!',
            ];
            addBlackRecord($this->data['userid'],'user',2,$user_domain,3,json_encode($_POST));
            $this->ajaxReturn($this->error, "json", JSON_UNESCAPED_UNICODE);
        }
    }

    public function checkWebsite()
    {
        $website = M('website')->find(1);
        if($website['all_valve']==1){
            $this->ajaxReturn([
                'status'=>'99',
                'msg'=>'禁止结算!如需结算请联系管理员!'
            ], "json", JSON_UNESCAPED_UNICODE);
        }
        if($website['settle_valve']==0){
            $this->ajaxReturn([
                'status'=>'99',
                'msg'=>'结算功能关闭!如需结算请联系管理员!'
            ], "json", JSON_UNESCAPED_UNICODE);
        }
    }

}