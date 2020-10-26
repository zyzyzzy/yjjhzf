<?php



namespace Payaccount\Controller;

use Think\Controller;

class AccountController extends Controller
{
    protected $returnJson = [];  //返回输出的数组

    protected $userid = "";

    protected $error_msg = '';  // 错误信息

    protected $own_type;  // 2019-4-30 rml:添加一个账号所属者类型的属性 1=通道的账号 2=用户的账号 3=用户的自助账号



    public function __construct()
    {
        parent::__construct();

        $this->returnJson["status"] = "error";

        $this->own_type;
    }



    /*

     * $parameter_array:包含用户id与通道编码的一维数组

     * $ordermoney：提交金额

     */

    public function getAccount($parameter_array, $ordermoney, &$reutrnJson)
    {

        //2018-12-18 张杨 添加判断是否存在通道类别编码

        if (!is_array($parameter_array) || !array_key_exists("userid", $parameter_array) || !array_key_exists("PayName", $parameter_array) || !array_key_exists("payapiclass_bm", $parameter_array)) {
            $reutrnJson["msg"] = "获取通道账号时参数错误";

            return false;
        }

        if (!$this->isuserid($parameter_array["userid"])) {
            $reutrnJson["msg"] = "获取通道账号时用户不存在";

            return false;
        }

        $this->userid = $parameter_array["userid"];

        if (!($payapiid = $this->ispayname($parameter_array["PayName"]))) {
            $reutrnJson["msg"] = "获取通道账号时通道不存在" . json_encode($parameter_array);

            return false;
        }



        if (!($payapiclass_id = $this->isPayapiClass($parameter_array["payapiclass_bm"]))) {
            $reutrnJson["msg"] = "获取通道类别时通道类别不存在";

            return false;
        }



//        $payapi_account_find = [];   //返回的通道账号具体数据



        //2019-4-28 rml：判断用户是否开通自助账号功能

        //开通：如果用户指定了通道，判断该通道内是否有用户的自助账号

        //1. 如果没有，先判断是否指定了通道，如果指定了，则直接报错，如果没有，使用系统的账号

        //2. 如果有自助账号：判断是否有设置默认的自助账号

        //1. 有设置默认：判断默认自助账号的条件，符合获取，否则报错

        //2. 没设置默认：获取该通道下的所有自助账号，轮询判断账号条件，将符合条件的账号存储，再随机取一条记录

        //没开通：使用系统,流程走系统的

        $self_payapi = M('user')->where(['id' => $parameter_array["userid"]])->getField('self_payapi');

        if ($self_payapi == 1) {

            //获取这个用户下这个通道的所有自助账号

            $self_where = [

                'user_id' => $parameter_array["userid"],

                'user_payapiid' => $payapiid,

//                'status' => 1,

                'del' => 0,

            ];

            $self_account = M('payapiaccount')->where($self_where)->count();

            if ($self_account) {

                //判断用户在这个通道上有没有设置默认账号是否有设置默认账号,有则获取默认账号的id,没有则轮询此通道下的自助账号

                $default_where = [

                    'user_id' => $parameter_array["userid"],

                    'user_payapiid' => $payapiid,

                    'default_status' => 1,

//                    'status' => 1,

                    'del' => 0,

                ];

                $default_useraccount = M('payapiaccount')->where($default_where)->find();

                if ($default_useraccount) {

                    //最先判断账号状态，如果被禁用了，下面的判断就没有意义了

                    if (!($this->checkAccountStatus($default_useraccount['id']))) {
                        $reutrnJson["msg"] = $this->error_msg;

                        return false;
                    }

                    //判断单笔最小最大金额

                    if (!($this->checkMinMaxMoney($default_useraccount['id'], $ordermoney))) {
                        $reutrnJson["msg"] = $this->error_msg;

                        return false;
                    }

                    //经过种种判断,通过则赋值账号的信息

                    $this->own_type=3;

                    $payapi_account_find = $default_useraccount;
                } else {

                    //随机从该通道下的账号列表中取一条数据

                    $useraccount_list = M('payapiaccount')->where($self_where)->select();

                    //在这里判断每一个账号是否符合要求，将符合要求的存入一个数组

                    $accountlistarray = [];

                    foreach ($useraccount_list as $key => $val) {
                        if (!($this->checkAccountStatus($val['id']))) {
                            unset($useraccount_list[$key]);

                            continue;
                        }

                        if (!($this->checkMinMaxMoney($val['id'], $ordermoney))) {
                            unset($useraccount_list[$key]);

                            continue;
                        }

                        $accountlistarray[] = $val;
                    }

                    //判断数组是否为空,如果有则去随机取一条数据

                    if (!$accountlistarray) {

                        //2019-4-4 任梦龙：修改

                        $this->error_msg = '没有符合要求的用户自助账号';

                        $reutrnJson["msg"] = $this->error_msg;

                        return false;
                    }

                    $count = count($accountlistarray) - 1;

                    $arri = rand(0, $count);

                    $this->own_type=3;

                    $payapi_account_find = $accountlistarray[$arri];
                }
            } else {

                //2019-4-3 任梦龙：如果用户指定了通道,同时通道内没有账号时,直接报错

                $set_user_payapi = M('setuserpayapi')->where(['user_id' => $parameter_array["userid"], 'user_payapi' => $payapiid])->count();

                if ($set_user_payapi) {
                    $reutrnJson['msg'] = '该指定通道下没有自助账号';

                    return false;
                }

                $payapi_account_find = $this->findPayapiAccount($payapiid, $payapiclass_id, $ordermoney);
            }
        } else {

            //2018-12-27 张杨 判断是否给该用户的该通道单独设置使用的账号，如果没有调用通道里设置的默认.所有账号

            //2018-12-28 张杨 给getDefaultpayapiaccountid方法添加了一个参数 通道类别ID

            $payapi_account_find = $this->findPayapiAccount($payapiid, $payapiclass_id, $ordermoney);
        }

        //  $payapiaccountfind = $payapiaccountid > 0 ? $this->checkDefaultAccount(['payapiid' => $payapid, 'payapiaccountid' => $payapiaccountid], $ordermoney, "", "default") : $this->getRandomAccount($payapid, $ordermoney, '');

        if (is_bool($payapi_account_find) && $payapi_account_find == false) {
            $reutrnJson["msg"] = $this->error_msg;

            return false;
        }

        //返回的通道账号具体数据

        $payapi_account_find['own_type'] = $this->own_type;  //账号所属者

        $payapi_account_find['payapiid'] = $payapiid;

        $payapi_account_find['payapiclassid'] = $payapiclass_id; //2019-01-29 张杨 添加返回通道类别ID

        return $payapi_account_find;
    }



    /**

     * 封装获取系统的账号：提取公共代码

     * @param $payapiid

     * @param $payapiclass_id

     * @param $ordermoney

     * @return array|bool

     */

    public function findPayapiAccount($payapiid, $payapiclass_id, $ordermoney)
    {
        $payapi_account_id = $this->getDefaultpayapiaccountid($this->userid, $payapiid, $payapiclass_id);

        switch ($payapi_account_id) {

            case 0:  //用户未开通通道分类

                $reutrnJson['msg'] = $this->error_msg;

                return false;

                break;

            case -1:   //轮循通道下的所有账号

                $this->own_type=1;

                return $this->getRandomAccount($payapiid, $ordermoney, '');

                break;

            case -2:   //2018-12-29 张杨  轮循用户下所有的通道帐号

                $this->own_type=2;

                return $this->getRandomAccount($payapiid, $ordermoney, '', $this->userid);

                break;

            default:  //应用通道下设置的默认账号

                $this->own_type=1;

                return $this->checkDefaultAccount(['payapiid' => $payapiid, 'payapiaccountid' => $payapi_account_id], $ordermoney, "", "default");

        }
    }



    /**

     * 获取默认通道账号

     * @param $userid

     * @param $payapiid

     * @param $payapiclass_id

     * @return mixed

     */

    private function getDefaultpayapiaccountid($userid, $payapiid, $payapiclass_id)
    {

        // 2018-12-28 判断是否开通了此通道分类

        $user_payapiclass_id = M('userpayapiclass')->where('payapiclassid=' . $payapiclass_id . ' and userid=' . $userid . ' and payapiid=' . $payapiid)->getField('id');

//        file_put_contents('ccccc.txt','payapiclassid='.$payapiclass_id.' and userid='.$userid.' and payapiid='.$payapiid);

        if (!$user_payapiclass_id) {
            $this->error_msg = '用户未开通通道分类';

            return 0;
        }

        $user_payapi_account_count = M('userpayapiaccount')->where('userpayapiclassid=' . $user_payapiclass_id)->count();

        if ($user_payapi_account_count == 0) { //如果没有单独的给该用户设置通道账号，返回通道设置的默认帐号

            $default_payapi_account_id = M('userpayapiclass')->where('payapiclassid=' . $payapiclass_id . ' and userid = 0 and payapiid = ' . $payapiid)->getField('defaultpayapiaccountid');

            if ($default_payapi_account_id) {  //如果设置了通道默认账号，返回该账号ID

                return $default_payapi_account_id;
            } else {   //如果没有设置通道默认账号，返回通道下的所有账号参与轮循

                return -1;  //返回-1 告诉程序执行轮循通道下所有账号
            }
        } else {
            return -2;  //2018-12-29 张杨 返回-2 告诉程序执行轮循给该用户设置的通道账号
        }

        //首先判断有没有给用户单独设置通道账号，如果没有获取通道设置的默认帐号

        // return M("userpayapiclass")->where("userid=" . $userid . " and payapiid = " . $payapiid)->getField("defaultpayapiaccountid");
    }



    /**

     * 判断默认通道账号是否符合要求，符合的话返回具体的通道账号信息，否则再去随机挑选一个符合的账号

     * @param array $accountlistarray

     * @param string $ordermoney：订单金额

     * @param string $wherestr

     * @param string $default 如果是默认的账号，不用重复遍历，直接返回错误

     * @return mixed

     */

    private function checkDefaultAccount($accountlistarray, $ordermoney, $wherestr = "", $default = "")
    {

        //最先判断账号状态，如果被禁用了，下面的判断就没有意义了

        if (!($this->checkAccountStatus($accountlistarray['payapiaccountid']))) {
            return false;
        }

        //判断该账号是否有轮询规则，判断账号限额，返回具体的账号信息

        if ($this->checkDefaultCondition($accountlistarray, $ordermoney)) {
            return $this->getPayapiaccount($accountlistarray["payapiaccountid"]);
        }

        //如果指定默认的则直接返回

        if ($default == "default") {
            return false;
        }

        //如果以上都不是，则会去轮询一个账号

        return $this->getRandomAccount($accountlistarray["payapiid"], $ordermoney, $accountlistarray["payapiaccountid"]);
    }



    /**

     * 符合判断默认通道账号的条件

     */

    private function checkDefaultCondition($accountlistarray, $ordermoney)
    {

        //利用第三方变量来表示结果，这样避免一长串的逻辑判断条件出现

        $quota = $minmax_money = 0;



        if ($this->checkAccountQuota($accountlistarray, $ordermoney)) {
            $quota = 1;
        }

        if ($this->checkMinMaxMoney($accountlistarray['payapiaccountid'], $ordermoney)) {
            $minmax_money = 1;
        }

        if ($quota && $minmax_money) {
            return true;
        }

        return false;
    }



    /**

     * 随机获取一个可用账号

     */

    private function getRandomAccount($payapid, $ordermoney, $wherestr = '', $user_id = 0)
    {
        $res = $this->getAccountList($payapid, $ordermoney, $wherestr, $user_id);  //获取可用账号列表

//        dump($res);die;

        if ($res) {
            return $this->randomAccount($res); //随机获取
        } else {
            return false;
        }
    }



    /**

     * 获取给通道分配的所有账号ID，挑选所有符合条件的账号

     * @param $payapiid

     * @param $wherestr

     * @return mixed

     */

    private function getAccountList($payapiid, $ordermoney, $wherestr, $user_id = 0)  //2018-12-29 张杨  添加了一个用户ID的参数
    {
        $str = ($wherestr != "" ? $wherestr : "");

        $where = [

            "payapiid" => $payapiid,

            "payapiaccountid" => ['not in', $str],

            "userid" => $user_id

        ];

        $table_name = $user_id == 0 ? 'tongdaozhanghao' : 'userpayapiaccountlist';

        $all_accounts = M($table_name)->where($where)->field(['payapiaccountid', 'money', 'payapiid'])->select();  //得到该通道下所有账号

        //判断该通道是否有账号，没有直接返回错误信息

        //2019-4-28 rml：先去判断状态,如果异常，则直接跳过，继续下一个循环,这样避免嵌套if语句

        if ($all_accounts) {

            //依据账号状态+轮循规则+限额来挑选符合的账号

            $account_list = [];

            foreach ($all_accounts as $k => $v) {

                //还是先判断账号状态

                if (!$this->checkAccountStatus($v['payapiaccountid'])) {
                    unset($all_accounts[$k]);

                    continue;
                }

                if (!$this->checkAllCondition($v, $ordermoney)) {
                    unset($all_accounts[$k]);

                    continue;
                }

                $account_list[] = $v;
            }

            //判断是否有可用的账号

            if ($account_list) {
                return $account_list;
            } else {

                //2019-8-20 rml:注释：因为如果错误，在对应的方法中属性已经赋值了

//                $this->error_msg = '该通道下没有可用的账号';

                return false;
            }
        } else {
            $this->error_msg = '该通道下没有账号';

            return false;
        }
    }



    /**

     * 判断账号的状态

     */

    //2019-4-28 rml：修改为M方法

    private function checkAccountStatus($payapiaccountid)
    {
        if (!(M("payapiaccount")->where("id=" . $payapiaccountid)->getField("status") == 1)) {
            $this->error_msg = '该账号已被禁用';

            return false;
        }

        return true;
    }



    /**

     * 检测订单金额是否在账号单笔最小金额与单笔最大金额之间

     */

    //2019-8-20 rml:修改提示语

    private function checkMinMaxMoney($payapiaccountid, $ordermoney)
    {
        $account_info = M('payapiaccount')->where('id = ' . $payapiaccountid)->field('min_money,max_money')->find();

        if ($ordermoney < $account_info['min_money']) {
            $this->error_msg = '最低单笔支付' . $account_info['min_money'];

            return false;
        }

        if ($ordermoney > $account_info['max_money']) {
            $this->error_msg = '最高单笔支付' . $account_info['max_money'];

            return false;
        }

        return true;
    }



    /**

     * 判断账号的轮循

     * 地区判断还没做

     */

    private function checkAccountLoops($payapiaccountid, $ordermoney)
    {
        $account = M('payapiaccountloops')->where('payapiaccountid=' . $payapiaccountid)->find();

        //判断该账号是否开启轮循，如果开启则判断是否符合规则，没开启则直接可用

        if ($account['status'] == 1) {
            $time = strtotime('now');

            if (

                $time > strtotime($account['datetime_ks'])

                && $time < strtotime($account['datetime_js'])

                && floatval($ordermoney) >= floatval($account['money_ks'])

                && floatval($ordermoney) <= floatval($account['money_js'])

            ) {
                return true;
            } else {
                return false;
            }
        } else {
            return true;
        }
    }



    /**判断账号的限额

     * @param $accountlistarray

     * @param $ordermoney

     * @return bool

     */

    private function checkAccountQuota($accountlistarray, $ordermoney)
    {

        //获取通道账号当天的交易金额

        /*$TodayMoneyAccount = $this->TodayMoneyAccount($accountlistarray["payapiid"],$accountlistarray["payapiaccountid"]);

        $TodayMoneyAccount = $TodayMoneyAccount ? $TodayMoneyAccount : 0 ;



        $accountTodayQuota =$this->getPayapiAccountQuota($accountlistarray["payapiid"],$accountlistarray["payapiaccountid"],$ordermoney); //获取限额顺序：用户-->通道--->账号-->默认最大值

        if($accountTodayQuota){

            //判断

            if( ($TodayMoneyAccount + $ordermoney) >= $accountTodayQuota){

                return false;

            }

            return true;

        }else{

            return false;

        }*/



        return $this->getPayapiAccountQuota($accountlistarray["payapiid"], $accountlistarray["payapiaccountid"], $ordermoney);
    }





    /**

     * 获取限额

     * @param $payapiid：通道id

     * @param $accountid：账号id

     * @param $ordermoney：订单金额

     * @return int|mixed

     */

    //2019-4-28 rml：优化逻辑

    private function getPayapiAccountQuota($payapiid, $accountid, $ordermoney)
    {

        //获取用户限额

        //如果是用户自己的通道账号，那么在初始生成记录的时候一定是money=0.00,所以不必再判断isset($money)，只需要判断用户限额是否超过交易金额即可

        $user_quota = M("tongdaozhanghao")->where("payapiid=" . $payapiid . " and payapiaccountid=" . $accountid . " and userid=" . $this->userid)->getField("money");

        $user_quota = floatval($user_quota);

        //如果用户限额有设置，判断交易金额是否超过用户限额,超过报错,否则表示通过

        if ($user_quota > 0) {

            //先查出该用户在此账号上的今日交易总额

            $user_sum_money = M("todaypayapiaccountmoney")->where("payapiid=" . $payapiid . " and payapiaccountid=" . $accountid . " and userid=" . $this->userid)->sum("money");

            $user_summoney = $user_sum_money ? $user_sum_money : 0;

            if (($ordermoney + $user_summoney) > $user_quota) {
                $this->error_msg = '交易金额超过用户限额--' . $user_quota;

                return false;
            }

            return true;
        }



        //如果用户限额未设置，则判断通道限额

        //获取通道限额

        $payapi_quota = M("tongdaozhanghao")->where("payapiid=" . $payapiid . " and payapiaccountid=" . $accountid . " and userid=0")->getField("money");

        $payapi_quota = floatval($payapi_quota);

        if ($payapi_quota > 0) {

            //查出该通道在此账号上的今日交易总额

            $pay_sum_money = M("todaypayapiaccountmoney")->where("payapiid=" . $payapiid . " and payapiaccountid=" . $accountid)->sum("money");

            if (($ordermoney + $pay_sum_money) > $payapi_quota) {
                $this->error_msg = '交易金额超过通道限额--' . $pay_sum_money;

                return false;
            }

            return true;
        }



        //如果通道限额未设置,则判断账号限额,如果账号限额未设置或者未0，则报错

        //获取账号限额

        $account_quota = M("payapiaccount")->where("id=" . $accountid)->getField("money");

        $account_quota = floatval($account_quota);

        if ($account_quota > 0) {

            //先查出此账号上的今日交易总额

            $account_sum_money = M("todaypayapiaccountmoney")->where("payapiaccountid=" . $accountid)->sum("money");

            if (($ordermoney + $account_sum_money) > $account_quota) {
                $this->error_msg = '交易金额超过账号限额--' . $account_quota;

                return false;
            }

            return true;
        }

        return false;





        //先查出该用户在此账号上的今日交易总额   ------原始代码

//        $user_sum_money = M("todaypayapiaccountmoney")->where("payapiid=" . $payapiid . " and payapiaccountid=" . $accountid . " and userid=" . $this->userid)->sum("money");

//        if (isset($money) && $money > 0 && ($ordermoney + $user_sum_money) > $money) {  //2019-01-08汪桂芳修改,去掉了等于0的判断(等于0时代表用户没有限额)

//            //交易金额超过限额

//            $this->error_msg = '交易金额超过用户限额';

//            return false;

//        }

//        if (isset($money) && $money > 0 && ($ordermoney + $user_sum_money) <= $money) { //2019-01-08汪桂芳修改,去掉了等于0的判断

//            return true;

//        } else {

//            //如果用户限额不存在，获取通道限额，并判断交易金额是否超过通道限额

//            //先查出该通道在此账号上的今日交易总额

//            $pay_sum_money = M("todaypayapiaccountmoney")->where("payapiid=" . $payapiid . " and payapiaccountid=" . $accountid)->sum("money");

//            //获取通道限额

//            $money = M("tongdaozhanghao")->where("payapiid=" . $payapiid . " and payapiaccountid=" . $accountid . " and userid=0")->getField("money");

//            if (isset($money) && $money > 0 && ($ordermoney + $pay_sum_money) > $money) { //2019-01-08汪桂芳修改,去掉了等于0的判断(等于0时代表通道没有限额)

//                //交易金额超过限额

//                $this->error_msg = '交易金额超过通道限额';

//                return false;

//            }

//            if (isset($money) && $money > 0 && ($ordermoney + $pay_sum_money) <= $money) { //2019-01-08汪桂芳修改,去掉了等于0的判断

//                return true;

//            } else {

//                //如果通道限额不存在，获取账号限额，并判断交易金额是否超过账号限额

//                //先查出此账号上的今日交易总额

//                $account_sum_money = M("todaypayapiaccountmoney")->where("payapiaccountid=" . $accountid)->sum("money");

//                //获取账号限额

//                $money = M("payapiaccount")->where("id=" . $accountid)->getField("money");

//                if ($money > 0 && ($ordermoney + $account_sum_money) > $money) { //2019-01-08汪桂芳修改,去掉了等于0的判断(等于0时代表账号没有限额)

//                    //交易金额超过限额

//                    $this->error_msg = '交易金额超过账号限额';

//                    return false;

//                } else {

//                    return true;

//                }

//            }

//

//        }
    }



    /**

     * 随机选择下，账号轮循+限额

     */

    //2019-4-28 rml：如果有一个错误，则直接返回错误信息

    private function checkAllCondition($accountlistarray, $ordermoney)
    {
        if (!$this->checkAccountLoops($accountlistarray['payapiaccountid'], $ordermoney)) {
            return false;
        }

        if (!$this->checkAccountQuota($accountlistarray, $ordermoney)) {
            return false;
        }



        //2019-8-20 rml:添加账号单笔金额的判断条件

        if (!$this->checkMinMaxMoney($accountlistarray['payapiaccountid'], $ordermoney)) {
            return false;
        }

        return true;
    }



    /**

     * 随机获取一个通道账号

     * @param $accountlist ：账号列表

     * @return mixed

     */

    //2019-4-28 rml：修改逻辑，完善

    private function randomAccount($accountlist)
    {
        if (!$accountlist) {
            return false;
        }

        //发现$accountlistarray与$accountlist结构与数据一致，所以直接从$accountlist中随机去一条记录即可

//        $arri = 0;

//        $accountlistarray = [];

//        foreach ($accountlist as $key => $val) {

//            $accountlistarray[] = [

//                'payapiid' => $val["payapiid"],

//                'payapiaccountid' => $val["payapiaccountid"],

//                'money' => $val["money"]

//            ];

//            $arri++;

//        }

//        $i = rand(0, $arri - 1);

        $i = rand(0, count($accountlist) - 1);

        return $this->GetPayapiaccount($accountlist[$i]['payapiaccountid']);

//        return  $this->CheckAccount($accountlistarray[$i],$ordermoney,$wherestr);//已经挑选出来符合条件的账号，不需要再判断一次了
    }





    /**

     * 获取通道账号信息

     * @param $payapiaccountid

     * @return mixed

     */

    //2019-4-28 rml：D方法没有起作用,所以直接获取一条记录

    private function getPayapiaccount($payapiaccountid)
    {
        return M("payapiaccount")->where("id=" . $payapiaccountid)->find();
    }



    /**

     * 判断userid是否存在

     * @param $userid

     * @return bool

     */

    private function isuserid($userid)
    {
        if (M("user")->where("id=" . $userid)->count() > 0) {
            return true;
        }

        return false;
    }



    /**

     * 判断通道名是否存在

     * @param $payname

     * @return int|mixed

     */

    private function ispayname($payname)
    {
        $payapiid = M("payapi")->where("en_payname = '" . $payname . "'")->getField("id");

        if ($payapiid) {
            return $payapiid;
        } else {
            return 0;
        }
    }



    /**

     * 2018-12-28 张杨

     * 判断通道类别是否存在，如果存在返回对应的通道类别ID

     * @param $payapiclass_bm  通道编码

     * @return int|mixed

     */

    private function isPayapiClass($payapiclass_bm)
    {
        $payapiclass_id = M("payapiclass")->where("classbm='" . $payapiclass_bm . "'")->getField("id");

        if ($payapiclass_id) {
            return $payapiclass_id;
        } else {
            return 0;
        }
    }
}
