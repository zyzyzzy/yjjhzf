<?php

namespace Settlement\Controller;

use Think\Controller;
use Admin\Model\SecretkeyModel;
use Settlement\Model\BlackbanknumModel;
use Settlement\Model\BlackdomainModel;
use Settlement\Model\BlackidcardModel;
use Settlement\Model\BlackipModel;
use Settlement\Model\BlacktelModel;
use Settlement\Model\DaifuModel;
use Settlement\Model\SettleconfigModel;
use Settlement\Model\SettleModel;
use Settlement\Model\UsermoneyModel;
use User\Model\SettledateModel;

class SettleController extends Controller
{
    protected $returnJson = [];  //返回输出的数组
    protected $obj;  //具体的某一通道
    protected $payaccount;  //通道账号信息
    protected $payparameter = []; //返回给具体通道的参数
    //父类也给默认属性值，先会去判断子类有没有属性，没有则去父类中判断
    protected $CheckDomain = true;  //是否判断提交域名
    protected $CheckIp = false;  //是否判断提交ip
    protected $sysordernumberleng = 32; //系统订单号长度
    protected $freezeordernumberleng = 36; //冻结订单号长度
    protected $autotype = 0;   //手动提款0 自动提款1
    protected $daifuid = 0; //代付通道ID
    protected $accountid = 0; //代付通道账号ID
    protected $sysordernumber;   //系统订单号

    public function __construct()
    {
        parent::__construct();
        header("Content-type: text/html; charset=utf-8");
        $this->returnJson["status"] = "error";
    }

    final protected function indexsettle($parameter)
    {
        if ($parameter['formatdata']['query'] == 'query') {  //执行查询操作
            $this->Query($parameter["formatdata"]);
            //dump($parameter);
        } else {
            if (
                $this->checkParameter($parameter['formatdata']) //判断收到的格式化的参数是否合法
                && $this->CheckSettelDate()   //判断是否节假日
                && $this->checkBankcardnumber($parameter['formatdata']['bankcardnumber'],$parameter["formatdata"]["userid"]) //判断是否银行卡黑名单
                && $this->checkIdentitynumber($parameter['formatdata']['identitynumber'],$parameter["formatdata"]["userid"])  //判断是否身份证黑名单
                && $this->checkPhonenumber($parameter['formatdata']['phonenumber'],$parameter["formatdata"]["userid"])  //判断是否手机号黑名单
                && $this->CheckUserPayStatus($parameter["formatdata"]["userid"])   //判断用户的结算状态
                && $this->CheckSettleObject($parameter["formatdata"]["userid"])   //实例化具体的通道
                && $this->CheckObj()   //判断通道实例是否合法
                && $this->CheckDomain($parameter["formatdata"]["userid"])   //判断是否限制域名
                && $this->CheckIp($parameter["formatdata"]["userid"])   //判断是否限制ip 2019-04-09汪桂芳添加
                && $this->GetAccount($parameter["formatdata"]["userid"])  //获取通道的账号,2018-12-28 张杨 添加了一个参数通道类别编码
                && $this->CreateOrder($parameter) //产生订单记录
                && $this->CreateParameter($parameter)  //生成调用通道所需要的参数
            ) {
                if($this->autotype == 1){ //调用代付通道
                    call_user_func(array($this->obj, "Pay"), $this->payparameter);
                }else{
                    $response_array = $this->createResponseArray($this->sysordernumber);
                    $response_array['status'] = 1;   //手动打款处理中
                    $response_array['msg'] = '结算申请成功，正在处理中';
                    SettleModel::updateSettleStatus($this->sysordernumber, 1, '手动打款处理中');
                    $this->responseFromData($response_array);

                }

            } else {
                //2019-01-08汪桂芳修改,加了一个参数方便查看报错信息
                unset($this->returnJson["parameter"]);
                unset($this->returnJson["decryptdata"]);
                unset($this->returnJson["formatdata"]);
                $this->ajaxReturn($this->returnJson);
                //exit(json_encode($this->returnJson, JSON_UNESCAPED_UNICODE));  //输出JSON格式的错误信息
            }
        }


    }


    //2019-08-28 张杨   判断收到的格式化的参数是否合法
    private function checkParameter($formatdata){
        $parameterArray = [
            'version',
            'userordernumber', //用户提交的结算订单号
            'memberid',  //商户号
            'bankname',  //银行名称
            'bankcardnumber', //银行卡号
            'bankusername', //银行卡开户人姓名
            'applytime',   //申请时间
            'ordermoney',  //结算金额
            'userid', //用户ID
            'type', //
            ];

        foreach ($parameterArray as $key) {

            //2019-08-15 张杨 优化判断方法
            if(!array_key_exists($key,$formatdata) or $formatdata[$key] == ""){
                $this->returnJson["msg"] = "参数 $key 不能为空";
                return false;
                break;
            }
        }

        return true;
    }

    /**
     * 2019-02-24 张杨添加的交易查询接口
     * @param $parameter
     */
    public function Query($parameter)
    {
        $userid = $parameter["userid"];
        $orderid = $parameter["orderid"];
        $sysorderid = $parameter['sysorderid'];
        $where_array = [
            'userid' => $userid,
            'userordernumber' => $orderid,
        ];
        $parameter['sysorderid']?$where_array['ordernumber']=$parameter['sysorderid']:'';
        $sysordernumber = M('settle')->where($where_array)->getField('ordernumber');

        $response_array = $this->createResponseArray($sysordernumber);
        if($response_array['notifyurl']){
            $return =  $this->responseFromData($response_array,'notifyurl');
        }
        $this->responseFromData($response_array,'queryExit');


    }

    //前台查询订单状态
    public function queryOrder()
    {
        $orderid = I("orderid");
        $status = M("order")->where("sysordernumber='" . $orderid . "'")->getField("status");
        $res = $status > 0 ? "ok" : "no";
        $this->ajaxReturn(['status' => $res], 'json');
    }

    /**
     * 2019-08-19 张杨 判断结算状态
     */
    final private function CheckUserPayStatus($userid)
    {

        $user_status = M('user')->where(['id' => $userid, 'del' => 0])->field('status,order')->find();
        if ($user_status['status'] != 2) {
            $this->returnJson['status'] = "error";
            $this->returnJson["msg"] = "用户状态异常,请检查";
            return false;
        }

        $user_settle_status = M('settleconfig')->where(['user_id'=>$userid])->field('user_type,status,day_start,day_end,auto_type')->find();

        if($user_settle_status["user_type"] == 1){
            if($user_settle_status['status'] == 0){
                $this->returnJson['status'] = "error";
                $this->returnJson["msg"] = "结算状态已关闭";
                return false;
            }else{
                $checkIsBetweenTime = $this->checkIsBetweenTime($user_settle_status['day_start'], $user_settle_status['day_end']);
                if (!$checkIsBetweenTime) {
                    $this->returnJson['status'] = "error";
                    $this->returnJson["msg"] = "非结算时间";
                    return false;
                }else{
                    $this->autotype = $user_settle_status['auto_type'];
                }
            }
        }else{
            $settle = M('settleconfig')->where(['user_id'=>0])->field('status,day_start,day_end')->find();
            if($settle['status'] == 0){
                $this->returnJson['status'] = "error";
                $this->returnJson["msg"] = "结算状态已关闭";
                return false;
            }else{
                $checkIsBetweenTime = $this->checkIsBetweenTime($settle['day_start'], $settle['day_end']);
                if (!$checkIsBetweenTime) {
                    $this->returnJson['status'] = "error";
                    $this->returnJson["msg"] = "非结算时间";
                    return false;
                }else{
                    $this->autotype = $settle['auto_type'];
                }
            }
        }

        return true;
    }

   //2019-08-25 张杨   判断节假日
   final private function CheckSettelDate()
    {
        $date = date('Y-m-d');
        $today = date('w');  //数字表示的星期几,从 0 （星期日） 到 6 （星期六）
        if ($today == 0 || $today == 6) {
            $count = SettledateModel::getDateCount($date, 1);
            if ($count) {
                //正常可结算
                return true;
            }else{
                //节假日
                return true;
            }

        } else {
            $count = SettledateModel::getDateCount($date, 2);
            if ($count) {
                //节假日
                return true;
            }else{
                //正常可结算
                return true;
            }

        }
    }

    /**
     * 实例化代付通道
     * $userid：用户id
     */
    final private function CheckSettleObject($userid)
    {
        if($this->autotype == 0){  //如果手动打款，不用判断是否设置代付通道
            return true;
        }

        $user_settle_status = M('settleconfig')->where(['user_id'=>$userid])->field('user_type,daifu_id')->find();

        if($user_settle_status["user_type"] == 1){
            if(!$user_settle_status['daifu_id']){
                $this->returnJson['status'] = "error";
                $this->returnJson["msg"] = "未设置结算通道";
                return false;
            }else{
                $daifu_id = $user_settle_status['daifu_id'];
            }
        }else{
            $settle_daifu_id = M('settleconfig')->where(['user_id'=>0])->getField('daifu_id');
            if(!$settle_daifu_id){
                $this->returnJson['status'] = "error";
                $this->returnJson["msg"] = "未设置结算通道";
                return false;
            }else{
                $daifu_id = $settle_daifu_id;
            }
        }

        $en_payname = M("daifu")->where(['id'=>$daifu_id,'del'=>'0','status'=>1])->getField('en_payname');

        if(!$en_payname){
            $this->returnJson['status'] = "error";
            $this->returnJson["msg"] = "结算通道不存在或已禁用";
            return false;
        }else{
            $this->daifuid = $daifu_id;
            //实例化具体的通道控制器
            $this->obj = A("Settlement/" . $en_payname);
            return true;
        }

    }

    //2019-08-26 张杨 判断是否黑名单银行卡号
    final private function checkBankcardnumber($bankcardnumber,$userid)
    {
        $result = BlackbanknumModel::getBanks($bankcardnumber);
        if ($result) {
            //2019-03-13汪桂芳:添加黑名单记录表
            addBlackRecord($userid, 'user', 5, $bankcardnumber, 3, json_encode($this->returnJson["parameter"]));
            $this->returnJson['status'] = "error";
            $this->returnJson["msg"] = "银行卡[".$bankcardnumber."]已被加入黑名单,无法结算!";
            return false;
        }
        return true;
    }

  //2019-08-26 张杨 判断身份证号是否黑名单
    final private function checkIdentitynumber($identitynumber,$userid)
    {
        if (!preg_match("/(^\d{15}$)|(^\d{18}$)|(^\d{17}(\d|X|x)$)/", $identitynumber)) {
            $this->returnJson['status'] = "error";
            $this->returnJson["msg"] = "身份证号格式错误!";
            return false;
        }
        $idcard = BlackidcardModel::getIdcard($identitynumber);
        if ($idcard) {
            //2019-03-13汪桂芳:添加黑名单记录表
            addBlackRecord($userid, 'user', 4, $identitynumber, 3, json_encode($this->returnJson["parameter"]));
            $this->returnJson['status'] = "error";
            $this->returnJson["msg"] = "身份证号[".$identitynumber."]已经纳入黑名单,无法提现!";
            return false;

        }
        return true;
    }

    //2019-08-26 张杨 判断手机号是否黑名单
    final private function checkPhonenumber($phonenumber,$userid)
    {
        if (!preg_match("/^1[34578]\d{9}$/", $phonenumber)) {
            $this->returnJson['status'] = "error";
            $this->returnJson["msg"] = "手机号格式错误!";
            return false;
        }
        $tel = BlacktelModel::getTel($phonenumber);
        if ($tel) {
            //2019-03-13汪桂芳:添加黑名单记录表
            addBlackRecord($userid, 'user', 3, $phonenumber, 3, json_encode($this->returnJson["parameter"]));
            $this->returnJson['status'] = "error";
            $this->returnJson["msg"] = "手机号已经纳入黑名单,无法提现";
            return false;

        }
        return true;
    }



    /**
     * 异步跳转地址
     */
    public function notifyurl()
    {  //异步回调地址

    }

    /**
     * 同步跳转地址，还没写
     */

    public function callbackurl()
    {

    }


    /**
     * 检测是否通道已经实例化
     */
    //2019-4-24 rml：优化
    final private function CheckObj()
    {
        if($this->autotype == 0){  //如果手动打款，不用判断是否设置代付通道
            return true;
        }

        if (!is_object($this->obj)) {
            $this->returnJson["msg"] = "通道实例化失败";
            return false;
        } else {
            if (!$this->obj->checkStatus) {
                $this->returnJson["msg"] = "通道中设置的状态为禁用!";
                return false;
            }
            return true;
        }

    }


    /**
     * 2018-12-10 任梦龙修改
     * $userid：用户id
     * 判断是否限制域名，由于通道控制器继承pay控制器，所以会先自动判断子类属性，再去判断父类的
     */
    //2019-4-24 rml:这里的逻辑还需要考虑
    final private function CheckDomain($userid)
    {
        $user_domain = $_SERVER['HTTP_REFERER'];
        $result = BlackdomainModel::checkDomain($user_domain);
        if ($result) {
            addBlackRecord($userid, 'user', 2, $user_domain, 3, json_encode($this->returnJson["parameter"]));
            $this->returnJson['status'] = "error";
            $this->returnJson["msg"] = "当前域名已进入黑名单,无法代付!";
            return false;
        }
        //如果存在，判断是否开启验证域名，否则直接通过
        if ($this->obj->CheckDomain) {
            //如果子类或者父类开启验证，判单当前用户是否绑定了域名组
            $res = $this->checkDomainStatus($userid);
            if ($res) {
                return true;
            } else {
                return false;
            }
        }
        return true;
    }


    /**
     * 判断请求域名是否合法
     */
    //2019-4-24 rml:优化
    //2019-4-28 rml:问题：获取域名的方式和交易入口处不一致，需选择
    final private function checkDomainStatus($userid)
    {
        $domain = M('domain')->where(['userid' => $userid])->getField('domain', true);  //获取用户绑定的域名组  2019-4-25 rml:修改
        $now_domain = $_SERVER['SERVER_NAME'];  //获取服务器上的地址
        if (!$domain) {
            return true;  //当没有绑定域名组时直接通过
        } else {
            $domain_res = false;
            //只有请求域名存在域名组中才通过
            foreach ($domain as $val) {
                if (strpos($val, $now_domain) !== false) {
                    $domain_res = true;
                    break;
                }
            }
            if ($domain_res) {
                return true;
            } else {
                $this->returnJson['status'] = "error";
                $this->returnJson["msg"] = "域名非法!$now_domain";
                return false;
            }
        }
    }

    /**
     * 2019-04-09 汪桂芳添加
     * $userid：用户id
     * 判断是否限制域名，由于通道控制器继承pay控制器，所以会先自动判断子类属性，再去判断父类的
     */

    //2019-4-24 rml：优化,逻辑还需要考虑
    final private function CheckIp($userid)
    {

        $user_ip = getIp();
        $result = BlackipModel::getIp($user_ip);
        if ($result) {
            addBlackRecord($userid, 'user', 1, $user_ip, 3, json_encode($this->returnJson["parameter"]));
            $this->returnJson['status'] = "error";
            $this->returnJson["msg"] = "当前IP已进入黑名单,无法代付!";
            return false;
        }

        //如果存在，判断是否开启验证ip，否则直接通过
        if ($this->obj->CheckIp) {
            //如果子类或者父类开启验证，判单当前用户是否绑定了域名组
            $res = $this->checkIpStatus($userid);
            if ($res) {
                return true;
            } else {
                return false;
            }
        }
        return true;
    }


    //2019-4-25 rml：这里获取的IP是否和在交易接口版本中获取的IP一致？如果一致，则这里需要再获取IP了，如此节省了开销
    final private function checkIpStatus($userid)
    {
        $ip = M('ipaccesslist')->where([
            'user_id' => ['eq', $userid],
            'admin_id' => ['eq', 0],
            'child_id' => ['eq', 0],
        ])->getField('ip', true);  //获取用户绑定的域名组
        $now_ip = getIp();  //获取ip
        if (!$ip) {
            return true;  //当没有绑定ip组时直接通过
        } else {
            $ip_res = false;
            //只有请求域名存在域名组中才通过
            foreach ($ip as $val) {
                if ($now_ip == $val) {
                    $ip_res = true;
                    break;
                }
            }

            if ($ip_res) {
                return true;
            } else {
                $this->returnJson['status'] = "error";
                $this->returnJson["msg"] = "来源ip非法!";
                return false;
            }
        }
    }


    /**
     * 获取账号
     * @param $userid ：用户id
     * @param $ordermoney ：订单金额
     * @param $payapiclassbm : 通道类别编码 2018-12-28
     * @return mixed
     */

    final private function GetAccount($userid)
    {
        if($this->autotype == 0){  //如果手动打款，不用判断是否设置代付通道
            return true;
        }

        $user_settle_status = M('settleconfig')->where(['user_id'=>$userid])->field('user_type,account_id')->find();

        if($user_settle_status["user_type"] == 1){
            if(!$user_settle_status['account_id']){
                $this->returnJson['status'] = "error";
                $this->returnJson["msg"] = "未设置结算通道账号";
                return false;
            }else{
                $account_id = $user_settle_status['account_id'];
            }
        }else{
            $settle_account_id = M('settleconfig')->where(['user_id'=>0])->getField('account_id');
            if(!$settle_account_id){
                $this->returnJson['status'] = "error";
                $this->returnJson["msg"] = "未设置结算通道账号";
                return false;
            }else{
                $account_id = $settle_account_id;
            }
        }

        $find_account = M('payapiaccount')->where(['id'=>$account_id,'del'=>'0','status'=>1])->find();

        if(!$find_account){
            $this->returnJson['status'] = "error";
            $this->returnJson["msg"] = "结算通道账号不存在或已被禁用";
            return false;
        }

        $this->accountid = $account_id;
        $this->payaccount = $find_account;
        return true;

    }


    /**
     * 生成订单
     * $parameter：标准数据
     * 问题：获取IP的方法记得老系统里有，但是得找找
     */

    final private function CreateOrder($parameter)
    {
        M()->startTrans();    //开启事务
        //生成系统订单号
        $sysordernumber =  randpw($this->obj->sysordernumberleng?$this->obj->sysordernumberleng:32, 'ALL');
        //2019-4-28 rml:添加判断：判断订单号是否重复
        if (M("settle")->lock(true)->where(['ordernumber' => $sysordernumber])->count() > 0) {
            $this->returnJson['status'] = "error";
            $this->returnJson["msg"] = "系统订单号重复";
            return false;
        }
        $ordermoney = $parameter['formatdata']['ordermoney']; //提款金额
        $usermoney = UsermoneyModel::getInfo($parameter["formatdata"]["userid"]);
        $yusermoney = $usermoney['money']; //用户余额
        $settleconfig = SettleconfigModel::getInfo($parameter["formatdata"]["userid"]);
        if (!$settleconfig || $settleconfig['user_type'] == 0) {
            $settleconfig = SettleconfigModel::getInfo(0);
        }

        if($this->autotype == 1){
            //$payapiaccount = PayapiaccountModel::getInfo($settleconfig['account_id']);
            $payapiaccount = M('payapiaccount')->where('id='.$settleconfig['account_id'])->find();
           // $daifu = DaifuModel::getInfo($settleconfig['daifu_id']);
            $cb = $ordermoney * $payapiaccount['settle_cbfeilv'];
            $cbfl = $payapiaccount['settle_cbfeilv'];
            if ($cb < $payapiaccount['settle_min_cbfeilv']) {
                $cb = $payapiaccount['settle_min_cbfeilv'];
            }
        }else{
            $cbfl = 0;
            $cb = 0;
        }

        $settle_min_feilv = $settleconfig['settle_min_feilv'];
        $rate = $ordermoney * $settleconfig['settle_feilv'];
        $sxf = $rate;
        if ($settle_min_feilv > $rate) {
            $sxf = $settle_min_feilv;
        }

        //手续费扣款类型 0：内扣 1：外扣
        if ($settleconfig['deduction_type'] == 1) {
            $jsje = $ordermoney + $sxf;
            $money = $ordermoney;
        }elseif($settleconfig['deduction_type'] == 0){
            $jsje = $ordermoney;    //结算金额
            $money = $ordermoney - $sxf;  //到账金额
        }

        if ($money <= 0) {
            $this->returnJson['status'] = "error";
            $this->returnJson["msg"] = '结算最低手续费（'.$sxf.'元）比结算金额('.$jsje.'元)大';
            return false;
        }

        if ($settleconfig['min_money'] > $jsje) {
            $this->returnJson['status'] = "error";
            $this->returnJson["msg"] = '结算金额小于最低结算金额,最小结算金额为' . $settleconfig['min_money'] . '元';
            return false;
        }
        if ($jsje > $settleconfig['max_money']) {
            $this->returnJson['status'] = "error";
            $this->returnJson["msg"] = '结算金额大于最大结算金额,最大结算金额为' . $settleconfig['max_money'] . '元';
            return false;
        }
        if ($jsje > $yusermoney) {
            $this->returnJson['status'] = "error";
            $this->returnJson["msg"] = '用户可用余额不足!';
            return false;
        }
        if ($settleconfig['status'] == 0) {
            $this->returnJson['status'] = "error";
            $this->returnJson["msg"] = '当前结算功能未开启!';
            return false;
        }
        $count = SettleModel::getCountSettleByUser($parameter['formatdata']['userid']);
        if ($count > $settleconfig['day_maxnum']) {
            $this->returnJson['status'] = "error";
            $this->returnJson["msg"] = '今日结算总次数' . $settleconfig['day_maxnum'] . "已用完,如还需结算请联系管理员!";
            return false;
        }
        $sum_money = SettleModel::getSumMoneyByUser($parameter['formatdata']['userid']);
        if ($sum_money + $jsje > $settleconfig['day_maxmoney']) {
            $this->returnJson['status'] = "error";
            $this->returnJson["msg"] = '今日结算总金额' . $settleconfig['day_maxmoney'] . "已用完,如还需结算请联系管理员!";
            return false;
        }

        $settle_data = [
            'ordernumber' => $sysordernumber,
            'userordernumber' => $parameter['formatdata']['userordernumber'],
            'userid' => $parameter['formatdata']['userid'],
            'memberid' => $parameter['formatdata']['memberid'],
            'bankname' => $parameter['formatdata']['bankname'],
            'bankzhiname' => $parameter['formatdata']['bankzhiname'],
            'banknumber' => $parameter['formatdata']['banknumber'],
            'bankcode' => $parameter['formatdata']['bankcode'],
            'bankcardnumber' => $parameter['formatdata']['bankcardnumber'],
            'bankusername' => $parameter['formatdata']['bankusername'],
            'identitynumber' => $parameter['formatdata']['identitynumber'],
            'phonenumber' => $parameter['formatdata']['phonenumber'],
            'province' => $parameter['formatdata']['province'],
            'city' =>  $parameter['formatdata']['city'],
            'applytime' => $parameter['formatdata']['applytime'],
          //  'dealtime' => $parameter['formatdata']['dealtime'],
            'type' => $parameter['formatdata']['type'],
            'status' => 0,  //初始状态为未处理
            'ordermoney' => $ordermoney,
            'refundmoney' => 0,
            'notifyurl' => $parameter['formatdata']['notifyurl'],
            'remarks' => $parameter['formatdata']['remarks'],
        ];

//
//        if($this->autotype == 1){
//            $settle_data['status'] = 1;
//        }else{
//            $settle_data['status'] = 0;
//        }

        //2019-08-31 张杨   记录结算ttel状态变更记录
        $settlestatuslist_data = [
            'userid' => $parameter['formatdata']['userid'],
            'sysordernumber' => $sysordernumber,
            'userordernumber' => $parameter['formatdata']['userordernumber'],
            'datetime' => date("Y-m-d H:i:s"),
            'oldstatus' => 0,
            'newstatus' => 0,
            'remarks' => '第一次生成订单',
        ];

        $settelstatuslist_save = M('settlestatuslist')->add($settlestatuslist_data);


        $settlemoney_data = [
            'ordernumber' => $sysordernumber,
            'ordermoney' => $jsje,
            'traderate' => $settleconfig['settle_feilv'],
            'moneytrade' => $sxf,
            'costrate' => $cb,
            'moneycost' => $cbfl,
            'profit' => $sxf-$cb,
            'money' => $money,
            'shangjiaid' => $this->daifuid!=0?M('daifu')->where('id='.$this->daifuid)->getField('payapishangjiaid'):0,
            'accountid' => $this->accountid,
            'daifuid' => $this->daifuid,
            'deduction_type' => $settleconfig['deduction_type'],
        ];



        //变更用户余额
        $usermoney_save = M('usermoney')->where([
            'userid' => ['eq', $parameter['formatdata']['userid']]
        ])->save(['money' => $yusermoney - $jsje]);
        $settle = M('settle')->add($settle_data);
        $settlemoney_save = M('settlemoney')->add($settlemoney_data);

        //资金变动记录
        $moneychange_save = M('moneychange')->add([
            'userid' => $parameter['formatdata']['userid'],     //0	√用户ID
            'oldmoney' => $yusermoney,       //	15	4	√原始金额
            'changemoney' => $jsje,        //	15	4	√改变金额
            'nowmoney' => $yusermoney - $jsje,       //	15	4	√改变后的金额
            'datetime' => date('Y-m-d H:i:s'),       //	√添加时间
            'transid' => $sysordernumber,        //	100√订单号
            'payapiid' => $this->daifuid,       //0	√通道id
            'accountid' => $this->accountid,      //0	√账号id
            'changetype' => 5,     //	100√金额变动类型
            'tcuserid' => '',       //0	√提成用户i
            'tcdengji' => '',       //0	√提成等级
            'orderid' => '',        //	100√提成订单号
            'remarks' => $parameter['formatdata']['remarks'],        //	500√备注
        ]);

        if($settle && $usermoney_save && $settlemoney_save && $moneychange_save && $settelstatuslist_save){
            M()->commit();
            $this->sysordernumber = $sysordernumber;
            return true;
        }else{
            M()->rollback();
            $this->returnJson['status'] = "error";
            $this->returnJson["msg"] = '生成结算订单失败！';
            return false;
        }


    }





    //判断是否为手机端访问
    public function isMobile()
    {
        // 如果有HTTP_X_WAP_PROFILE则一定是移动设备
        if (isset ($_SERVER['HTTP_X_WAP_PROFILE']))
            return true;

        //此条摘自TPM智能切换模板引擎，适合TPM开发
        if (isset ($_SERVER['HTTP_CLIENT']) && 'PhoneClient' == $_SERVER['HTTP_CLIENT'])
            return true;
        //如果via信息含有wap则一定是移动设备,部分服务商会屏蔽该信息
        if (isset ($_SERVER['HTTP_VIA']))
            //找不到为flase,否则为true
            return stristr($_SERVER['HTTP_VIA'], 'wap') ? true : false;
        //判断手机发送的客户端标志,兼容性有待提高
        if (isset ($_SERVER['HTTP_USER_AGENT'])) {
            $clientkeywords = array(
                'nokia', 'sony', 'ericsson', 'mot', 'samsung', 'htc', 'sgh', 'lg', 'sharp', 'sie-', 'philips', 'panasonic', 'alcatel', 'lenovo', 'iphone', 'ipod', 'blackberry', 'meizu', 'android', 'netfront', 'symbian', 'ucweb', 'windowsce', 'palm', 'operamini', 'operamobi', 'openwave', 'nexusone', 'cldc', 'midp', 'wap', 'mobile'
            );
            //从HTTP_USER_AGENT中查找手机浏览器的关键字
            if (preg_match("/(" . implode('|', $clientkeywords) . ")/i", strtolower($_SERVER['HTTP_USER_AGENT']))) {
                return true;
            }
        }
        //协议法，因为有可能不准确，放到最后判断
        if (isset ($_SERVER['HTTP_ACCEPT'])) {
            // 如果只支持wml并且不支持html那一定是移动设备
            // 如果支持wml和html但是wml在html之前则是移动设备
            if ((strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') !== false) && (strpos($_SERVER['HTTP_ACCEPT'], 'text/html') === false || (strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') < strpos($_SERVER['HTTP_ACCEPT'], 'text/html')))) {
                return true;
            }
        }
        return false;
    }




    /**
     * 生成订单日志
     * $user_id：用户id
     * $user_order_num：用户订单号
     * $sys_order_num：系统订单号
     * $error_content：日志信息
     * $return_msg ：用户回调返回信息
     */

    final private function orderLogAdd($user_id, $user_order_num, $sys_order_num, $error_content, $return_msg = '')
    {
        $order_log = M('orderlog');
        $data = [
            'user_id' => $user_id,
            'user_order_num' => $user_order_num,//2019-03-04汪桂芳添加
            'sys_order_num' => $sys_order_num,
            'content_log' => $error_content,
            'at_time' => date('Y-m-d H:i:s'),
            'return_msg' => $return_msg,
        ];
        $order_log->add($data);
    }




    /**
     * 生成调用通道所需要的参数 ，还需将通道二维码页面模板数据传过去
     * @return bool
     */
    final public function GetParameter($sysordernumber)
    {
        $tj_parameter = M('userparameter')->where("sys_order_num='" . $sysordernumber . "'")->getField("tj_parameter");
        return json_decode($tj_parameter, true);
    }

    final private function CreateParameter($parameter)
    {

       // $par = $parameter['formatdata'];

        $settle_find = M('settle')->where("ordernumber='".$this->sysordernumber."'")->find();

        $settlemoney_find = M('settlemoney')->where("ordernumber='".$this->sysordernumber."'")->find();

        if($this->autotype == 1){  //调代付接口处理

            $account_find = M('payapiaccount')->where('id=' . $this->payaccount["id"])->field("memberid,account")->find();

            $account_key_str = M("payapiaccountkeystr")->where("payapi_account_id=" . $this->payaccount["id"])->find();

            $account_key = [

                'publickeystr_path' => $account_key_str["publickeystr_path"],

                'privatekeystr_path' => $account_key_str["privatekeystr_path"],

                'upstreamkeystr_path' => $account_key_str["upstream_keystr_path"],

                'publickeystr' => $account_key_str["publickeystr"],

                'privatekeystr' => $account_key_str["privatekeystr"],

                'upstreamkeystr' => $account_key_str["upstream_keystr"],

                'memberid' => $account_find["memberid"],

                'account' => $account_find["account"],

                'md5keystr' => $account_key_str["md5keystr"]

            ];
        }else{
            $account_key = [];
        }



        $this->payparameter = [

            'userordernumber' => $settle_find['userordernumber'], //用户订单号

            'shangjiaid' => $this->payaccount["payapishangjiaid"], //商家ID

            'payapiid' => $this->payaccount["payapiid"], //通道ID

            'payapiaccountid' => $this->payaccount["id"], //通道账号ID

            'userid' => $settle_find['userid'], //用户ID

            'ordermoney' => sprintf("%.2f", $settle_find['ordermoney']),  //提交金额

            'settle_money' => sprintf("%.2f", $settlemoney_find['ordermoney']),  //实际提交金额

            'callbackurl' => $this->getReturnUrl($this->obj->callbackurl ? $this->obj->callbackurl : "callbackurl"),  //同步回调地址

            'notifyurl' => $this->getReturnUrl($this->obj->callbackurl ? $this->obj->notifyurl : "notifyurl"), //异步回调地址

            'account_key' => $account_key, //账号密钥

            'sysordernumber' => $this->sysordernumber,  //系统订单号

            'memberid' => $account_find["memberid"],   //商户号

            'settle_bank' => $settle_find, //结算银行信息

        ];

        M('userparameter')->add([
            'sys_order_num' => $this->sysordernumber,
            'parameter' => json_encode($parameter),
            'tj_parameter' => json_encode($this->payparameter),
            'user_id' => $settle_find['userid']
        ]);

        return true;

    }


    //2019-4-2 任梦龙：虽然pay_website只有一条数据，但是防止意外，所以不用id=1这种直接方式，而是按照id倒序查询
    final private function getReturnUrl($retunname)
    {

        $find = M("website")->order('id DESC')->field("back_domain,back_http")->find();

        return ($find["back_http"] == 2 ? "https" : "http") . "://" . $find["back_domain"] . U("Pay/" . $this->obj->PayName . "/" . $retunname);

    }



    /**
     * 拼接加密
     */

    public function getSign($data, $key)

    {

        $str = '';

        foreach ($data as $k => $v) {

            if ($k != 'sign') {

                $str .= $k . '=' . $v . '&';

            }

        }

        return strtoupper(md5($str . 'key=' . $key));

    }


    /**
     * 记录回调的日志文件
     */

    public function callbackLog($filename, $data = null)

    {

        if (!$data) {

            $str = '';

            foreach ($_REQUEST as $k => $v) {   //$_REQUEST :默认情况下包含了 $_GET，$_POST 和 $_COOKIE 的数组

                $str .= $k . '=' . $v . '\n';

            }

            file_put_contents($filename, $str . "\n", FILE_APPEND);  //FILE_APPEND :在文件末尾以追加的方式写入数据

        } else {

            $str = '';

            foreach ($data as $k => $v) {

                $str .= $k . '=' . $v . '\n';

            }

            file_put_contents($filename, $str . "\n", FILE_APPEND);

        }


    }


    /**
     * 判断时间
     * @param $start
     * @param $end
     * @return bool
     */
    private function checkIsBetweenTime($start, $end)
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

    //2019-08-28 张杨  根据系统订单号获取相应的Version信息
    protected function sysordernumberGetVersion($sysordernumber){

    }

    //2019-08-29 张杨  生成响应基本数据
    protected function createResponseArray($sysordernumber){
        $settle_find = M('settle')->where(['ordernumber'=>array('eq',$sysordernumber)])->find();
        $settle_money = M('settlemoney')->where(['ordernumber'=>array('eq',$sysordernumber)])->find();
        $find_json = M('userparameter')->where(['sys_order_num'=>array('eq',$sysordernumber)])->getField('parameter');
        $settle_version_numberstr = json_decode($find_json,true)['parameter']['version'];
            $signmethod = json_decode($find_json,true)['parameter']['signmethod'];
            if($settle_find && $settle_money){
            $response_array = [
                'response_status' => true,
                'memberid' => $settle_find['memberid'],
                'userid' => $settle_find['userid'],
                'sysordernumber' => $settle_find['ordernumber'],
                'userordernumber' => $settle_find['userordernumber'],
                'bankname' => $settle_find['bankname'],
                'bankzhiname' => $settle_find['bankzhiname']?$settle_find['bankzhiname']:'',
                'banknumber' => $settle_find['banknumber']?$settle_find['banknumber']:'',
                'bankcode' => $settle_find['bankcode']?$settle_find['bankcode']:'',
                'bankcardnumber' => $settle_find['bankcardnumber'],
                'bankusername' => $settle_find['bankusername'],
                'identitynumber' => $settle_find['identitynumber']?$settle_find['identitynumber']:'',
                'phonenumber' => $settle_find['phonenumber']?$settle_find['phonenumber']:'',
                'province' => $settle_find['province']?$settle_find['province']:'',
                'city' => $settle_find['city']?$settle_find['city']:'',
                'applytime' => $settle_find['applytime'],
                'signmethod' => $signmethod,
                'dealtime' => $settle_find['dealtime']?$settle_find['dealtime']:'',
                'settle_ordermoney' => $settle_money['ordermoney'],  //结算金额
                'ordermoney' => $settle_find['ordermoney'], //提交金额
                'sxfmoney' => $settle_money['moneytrade'], //手续费
                'dzmoney' => $settle_money['money'], //到账金额
                'remarks' => $settle_find['remarks'],
                'notifyurl' => $settle_find['notifyurl'],
                'status' => $settle_find['status'],
                'version' => $settle_version_numberstr, //版本号

            ];
        }else{
            $response_array = [
                'response_status' => false,
                'status' => -1,
                'msg' => '结算订单不存在',
            ];
        }
     //   dump($response_array);
        return $response_array;

    }

    //2019-08-29 张杨 回调具体的version 方法
    protected function responseFromData($parameter,$functionname='responseExit'){
       // dump($parameter);die();
      //  $find_json = M('userparameter')->where(['sys_order_num'=>array('eq',$parameter['sysordernumber'])])->getField('parameter');
       // $settle_version_numberstr = json_decode($find_json,true)['parameter']['version'];
        $version_actionname = M('settleversion')->where(['numberstr'=>array('eq',$parameter['version'])])->getField('actionname');
        $obj = A('Version/'.$version_actionname);
        call_user_func(array($obj, $functionname), $parameter);
    }

}