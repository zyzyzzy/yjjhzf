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
use User\Model\SettledateModel;
use Think\Controller;

class ApiController extends Controller
{
    private $data = [
        "userordernumber" => '',          //结算订单号
        "ordernumber" => '',          //系统订单号
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
        "type" => 0,         //tinyint0	类型  0：T + 0   1：T + 1
        "status" => 0,           //tinyint0	状态 0：未处理 1：处理中  2：已打款
    ];
    protected $settleMoney = [
        "userordernumber" => '',  //结算订单号
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
    private $mustFields = [
        "userordernumber",          //结算订单号
        "memberid",         //商户号
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
        'type',
        'sign'
    ];
    protected $type;
    protected $signStr;
    protected $sign;
    protected $signature;
    private $noMustFields = [
        'remarks', 'banknumber',
    ];

    public function index($data = null)
    {
        $data = $data == null ? $_POST : $data;
        $this->checkParam($data);
        if (!$this->Sign()) {
            $this->ajaxReturn([
                "data" => [],
                'status' => '03',
                'msg' => '签名验证失败!',
            ], "json", JSON_UNESCAPED_UNICODE);
        }
        foreach ($this->noMustFields as $field) {
            if (array_key_exists($field, $data)) {
                $this->data[$field] = $data[$field];
            }
        }
        $this->data['applytime'] = date('Y-m-d H:i:s');
        $checkIsBetweenTime = $this->checkIsBetweenTime($this->settleconfig['day_start'], $this->settleconfig['day_end']);
        if (!$checkIsBetweenTime) {
            $this->ajaxReturn([
                "data" => [],
                'status' => '03',
                'msg' => '结算功能关闭!请在结算时间申请结算!',
            ], "json", JSON_UNESCAPED_UNICODE);
        }
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
            'tcuserid' => '',       //0	√提成用户i
            'tcdengji' => '',       //0	√提成等级
            'orderid' => '',        //	100√提成订单号
            'remarks' => '代付接口生成订单',        //	500√备注
        ]);
        if ($settle && $usermoney && $settlemoney && $money_change) {
            M()->commit();
            //是否自动代付
            $settleconfig = SettleconfigModel::getInfo($this->data['userid']);
            $daifu = DaifuModel::getInfo($this->settleMoney['daifuid']);
            if ($settleconfig['auto_type'] == 1) {
                $result = A("Daifu/" . $daifu['en_payname'], 'Controller');
//                dump($result);
            }
        } else {
            M()->rollback();
        }
        $this->ajaxReturn([
            "data" => [
                "userordernumber" => $this->data['userordernumber'],
                "ordermoney" => $this->data['ordermoney'],
                "remarks" => $this->data['remarks'],
            ],
            'status' => '00',
            'msg' => '申请结算成功!',
        ], "json", JSON_UNESCAPED_UNICODE);
    }

    public function checkParam($data)
    {
        //2019-03-13汪桂芳:添加ip和域名的黑名单判断
        $this->checkIp();
        $this->checkDomain();
        $this->checkDate();
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

    //2019-03-13汪桂芳添加:ip黑名单的判断
    public function checkIp()
    {
        $user_ip = getIp();
        $result = BlackipModel::getIp($user_ip);
        if ($result) {
            $this->error = [
                'status' => '02',
                'msg' => '当前IP已进入黑名单,无法代付!',
            ];
            addBlackRecord($this->data['userid'], 'user', 1, $user_ip, 3, json_encode($_POST));
            $this->ajaxReturn($this->error, "json", JSON_UNESCAPED_UNICODE);
        }
    }

    //2019-03-13汪桂芳添加:域名黑名单的判断
    public function checkDomain()
    {
        $user_domain = $_SERVER['HTTP_REFERER'];
        $result = BlackdomainModel::checkDomain($user_domain);
        if ($result) {
            $this->error = [
                'status' => '02',
                'msg' => '当前域名已进入黑名单,无法代付!',
            ];
            addBlackRecord($this->data['userid'], 'user', 2, $user_domain, 3, json_encode($_POST));
            $this->ajaxReturn($this->error, "json", JSON_UNESCAPED_UNICODE);
        }
    }

    public function checkOrdermoney($ordermoney)
    {
        $usermoney = UsermoneyModel::getInfo($this->data['userid']);
        $this->usermoney = $usermoney;
        $settleconfig = SettleconfigModel::getInfo($this->data['userid']);
        if (!$settleconfig || $settleconfig['user_type'] == 0) {
            $settleconfig = SettleconfigModel::getInfo(0);
        }
        $this->settleconfig = $settleconfig;
        $payapiaccount = PayapiaccountModel::getInfo($settleconfig['account_id']);
        if ($payapiaccount['status'] == 0) {
            $this->ajaxReturn([
                "data" => [],
                'status' => '03',
                'msg' => '代付通道对应账号关闭,请联系管理员处理!',
            ], "json", JSON_UNESCAPED_UNICODE);
        }
        $daifu = DaifuModel::getInfo($settleconfig['daifu_id']);
        if ($daifu['status'] == 0) {
            $this->ajaxReturn([
                "data" => [],
                'status' => '03',
                'msg' => '代付通道未开启,请联系管理员处理!',
            ], "json", JSON_UNESCAPED_UNICODE);
        }
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
            $this->ajaxReturn([
                "data" => [],
                'status' => '03',
                'msg' => '结算金额小于最低结算金额,最小结算金额为' . $settleconfig['min_money'] . '元',
            ], "json", JSON_UNESCAPED_UNICODE);
        }
        if ($jsje > $settleconfig['max_money']) {
            $this->ajaxReturn([
                "data" => [],
                'status' => '03',
                'msg' => '结算金额大于最大结算金额,最大结算金额为' . $settleconfig['max_money'] . '元',
            ], "json", JSON_UNESCAPED_UNICODE);
        }
        if ($jsje > $usermoney['money']) {
            $this->ajaxReturn([
                "data" => [],
                'status' => '03',
                'msg' => '用户可用余额不足!',
            ], "json", JSON_UNESCAPED_UNICODE);
        }
        if ($settleconfig['status'] == 0) {
            $this->ajaxReturn([
                "data" => [],
                'status' => '03',
                'msg' => '当前结算功能未开启!',
            ], "json", JSON_UNESCAPED_UNICODE);
        }
        $count = SettleModel::getCountSettleByUser($this->data['userid']);
        if ($count > $settleconfig['day_maxnum']) {
            $this->ajaxReturn([
                "data" => [],
                'status' => '03',
                'msg' => '今日结算总次数' . $settleconfig['day_maxnum'] . "已用完,如还需结算请联系管理员!",
            ], "json", JSON_UNESCAPED_UNICODE);
        }
        $sum_money = SettleModel::getSumMoneyByUser($this->data['userid']);
        if ($sum_money + $jsje > $settleconfig['day_maxmoney']) {
            $this->ajaxReturn([
                "data" => [],
                'status' => '03',
                'msg' => '今日结算总金额' . $settleconfig['day_maxmoney'] . "已用完,如还需结算请联系管理员!",
            ], "json", JSON_UNESCAPED_UNICODE);
        }
        $this->data['ordermoney'] = $ordermoney;
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

    protected function checkUserordernumber($userordernumber)
    {
        $result = SettleModel::checkRepeat($userordernumber, $this->data['userid']);
        if ($result) {
            $this->error = [
                "data" => [
                    'userordernumber' => $userordernumber
                ],
                'status' => '02',
                'msg' => '订单号重复!',
            ];
        }
        $ordernumber = date('YmdHis') . randpw(5);
        $this->data['userordernumber'] = $userordernumber;
        $this->data['ordernumber'] = $ordernumber;
        $this->settleMoney['ordernumber'] = $ordernumber;
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
        $this->data['memberid'] = $memberid;
        $this->data['userid'] = $secretkey['userid'];
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
            $this->error = [
                "data" => [
                    'bankcardnumber' => $bankcardnumber
                ],
                'status' => '02',
                'msg' => '黑名单卡号,无法代付!',
            ];
            //2019-03-13汪桂芳:添加黑名单记录表
            addBlackRecord($this->data['userid'], 'user', 5, $bankcardnumber, 3, json_encode($_POST));
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
            $this->error = [
                "data" => [
                    'identitynumber' => $identitynumber
                ],
                'status' => '02',
                'msg' => '身份证号错误!',
            ];
        }
        $idcard = BlackidcardModel::getIdcard($identitynumber);
        if ($idcard) {
            $this->error = [
                "data" => [
                    'identitynumber' => $identitynumber
                ],
                'status' => '02',
                'msg' => '身份证号已经纳入黑名单,无法提现!',
            ];
            //2019-03-13汪桂芳:添加黑名单记录表
            addBlackRecord($this->data['userid'], 'user', 4, $identitynumber, 3, json_encode($_POST));
        }
        $this->data['identitynumber'] = $identitynumber;
        return $this;
    }

    protected function checkPhonenumber($phonenumber)
    {
        if (!preg_match("/^1[34578]\d{9}$/", $phonenumber)) {
            $this->error = [
                "data" => [
                    'phonenumber' => $phonenumber
                ],
                'status' => '02',
                'msg' => '手机号错误!',
            ];
        }
        $tel = BlacktelModel::getTel($phonenumber);
        if ($tel) {
            $this->error = [
                "data" => [
                    'phonenumber' => $phonenumber
                ],
                'status' => '02',
                'msg' => '手机号已经纳入黑名单,无法提现!',
            ];
            //2019-03-13汪桂芳:添加黑名单记录表
            addBlackRecord($this->data['userid'], 'user', 3, $phonenumber, 3, json_encode($_POST));
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

    public function checkSign($sign)
    {
        $this->sign = $sign;
    }

    public function checkIsBetweenTime($start, $end)
    {
        $curTime = strtotime(date('H:i:s'));
        $assignTime1 = strtotime($start);//获得指定分钟时间戳，00:00
        $assignTime2 = strtotime($end);//获得指定分钟时间戳，01:00
        $result = false;
        if ($curTime > $assignTime1 && $curTime < $assignTime2) {
            $result = true;
        }
        return $result;
    }

    public function Sign()
    {
        if ($this->type == 'MD5') {
            $md5key = SecretkeyModel::getUserMd5Key($this->data['userid']);
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

    //2019-5-8 rml：整理
    //判断结算节假日：不能结算的日期:周末+节假日  type=1:排除 2=添加
    //如果为周六或者周日:判断是否在排除节假日内:如果存在,则允许代付
    //如果不是：判断是否在添加节假日内：如果存在,则不允许代付
    public function checkDate()
    {
        $date = date('Y-m-d');
        $today = date('w');  //数字表示的星期几,从 0 （星期日） 到 6 （星期六）
        if ($today == 0 || $today == 6) {
            $count = SettledateModel::getDateCount($date, 1);
            if ($count) {
                return true;
            }
            $this->error = [
                'status' => '02',
                'msg' => '今日为节假日,结算功能关闭,无法代付!',
            ];
            $this->ajaxReturn($this->error, "json", JSON_UNESCAPED_UNICODE);

        } else {
            $count = SettledateModel::getDateCount($date, 2);
            if ($count) {
                $this->error = [
                    'status' => '02',
                    'msg' => '今日为休息日,结算功能关闭,无法代付!',
                ];
                $this->ajaxReturn($this->error, "json", JSON_UNESCAPED_UNICODE);
            }
            return true;

        }


//        $t = true;
//        if ($today == 'Saturday' || $today == 'Sunday') {
//            $t = false;
//            //查询所有排除的日期
//            $all_remove = SettledateModel::getAllRemove();
//            if ($all_remove) {
//                foreach ($all_remove as $v) {
//                    if ($date == $v) {
//                        $t = true;
//                    }
//                }
//            }
//        }
        //判断是否在节假日里面
//        if ($t) {
//            //查询所有的节假日
//            $all_holiday = SettledateModel::getAllHoliday();
//            if ($all_holiday) {
//                $a = false;
//                foreach ($all_holiday as $val) {
//                    if ($date == $val) {
//                        $a = true;
//                    }
//                }
//                if ($a) {
//                    $this->error = [
//                        'status' => '02',
//                        'msg' => '今日为节假日,结算功能关闭,无法代付!',
//                    ];
//                    $this->ajaxReturn($this->error, "json", JSON_UNESCAPED_UNICODE);
//                }
//            }
//        } else {
//            $this->error = [
//                'status' => '02',
//                'msg' => '今日为休息日,结算功能关闭,无法代付!',
//            ];
//
//            $this->ajaxReturn($this->error, "json", JSON_UNESCAPED_UNICODE);
//
//        }
    }
}