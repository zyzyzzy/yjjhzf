<?php



namespace Pay\Controller;

use Admin\Model\OrdercommissionModel;

use Admin\Model\StatisticModel;

use Admin\Model\SuccessrateModel;

use Pay\Model\AdvjumpdataModel;

use Pay\Model\AdvtemplateModel;

use Pay\Model\PayapijumpModel;

use Pay\Model\PayapiModel;

use Think\Controller;

class PayController extends Controller
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



    public function __construct()
    {
        parent::__construct();

        header("Content-type: text/html; charset=utf-8");

        $this->returnJson["status"] = "error";
    }



    final protected function indexpay($parameter)
    {
        if ($parameter['formatdata']['query'] == 'query') {  //执行查询操作

            $this->Query($parameter["formatdata"]);
        } else {
            if (

                $this->CheckUserPayStatus($parameter["formatdata"]["userid"])   //2019-03-06汪桂芳:判断用户的交易状态

                && $this->CheckPayapiClass($parameter["formatdata"][C("TONGDAO")], $parameter["formatdata"]["userid"])   //实例化具体的通道

                && $this->CheckObj()   //判断通道实例是否合法

                && $this->CheckDomain($parameter["formatdata"]["userid"])   //判断是否限制域名

                && $this->CheckIp($parameter["formatdata"]["userid"])   //判断是否限制ip 2019-04-09汪桂芳添加

                && $this->GetAccount($parameter["formatdata"]["userid"], $parameter['formatdata']['amount'], $parameter["formatdata"]["tongdao"])  //获取通道的账号,2018-12-28 张杨 添加了一个参数通道类别编码

                && $this->CreateOrder($parameter) //产生订单记录

                && $this->CreateParameter($parameter)  //生成调用通道所需要的参数

            ) {
                call_user_func(array($this->obj, "Pay"), $this->payparameter);
            } else {

                //2019-08-28  张杨   报错时删除其它的数据

                unset($this->returnJson["parameter"]);

                unset($this->returnJson["decryptdata"]);

                unset($this->returnJson["formatdata"]);

                //2019-01-08汪桂芳修改,加了一个参数方便查看报错信息

                exit(json_encode($this->returnJson, JSON_UNESCAPED_UNICODE));  //输出JSON格式的错误信息
            }
        }





        //判断是否给用户开通了此通道，并且判断通道的状态是否可用

        //是否判断提交域名（IP），如果需要判断，域名（IP）是否合法

        //获取通道的账号

        //判断通道的账号是否可用

        //依据通道账号的设置来判断交易金额，单笔最小交易金额，单笔最大交易金额，每日交易总限额

        //生成系统订单号

        //产生订单记录  （加日志记录）

        //生成调用通道所需要的参数

        //调用对应的通道发起提交
    }





    /**

     * 2019-02-24 张杨添加的交易查询接口

     * @param $parameter

     */

    public function Query($parameter)
    {
        $userid = $parameter["userid"];

        $orderid = $parameter["orderid"];

        $order = M("order");

        $find = $order->where("userordernumber='" . $orderid . "'")->find();

        $return = [];

        if (!$find || $find['status'] == 0) {
            $return["status"] = "01";

            $return["msg"] = "未支付";

            $return["userid"] = $parameter["userid"];

            $return["amount"] = $find["ordermoney"];

            $return["true_amount"] = $find["true_ordermoney"];

            $return["orderid"] = $find["userordernumber"];

            $return["sysorderid"] = $find["sysordernumber"];

            $return["submittime"] = $find["datetime"];

            $return["successtime"] = $find["successtime"];

            $return["notifyurl"] = $parameter["notifyurl"];

            $return["signmethod"] = $parameter["signmethod"];

            $return["version"] = $parameter["version"];
        }

        if ($find['status'] == 1 or $find['status'] == 2) {
            $return["status"] = "00";

            $return["msg"] = "支付成功";

            $return["userid"] = $parameter["userid"];

            $return["amount"] = $find["ordermoney"];

            $return["true_amount"] = $find["true_ordermoney"];

            $return["orderid"] = $find["userordernumber"];

            $return["sysorderid"] = $find["sysordernumber"];

            $return["submittime"] = $find["datetime"];

            $return["successtime"] = $find["successtime"];

            $return["memberid"] = $find["memberid"];

            $return["notifyurl"] = $parameter["notifyurl"];

            $return["signmethod"] = $parameter["signmethod"];

            $return["version"] = $parameter["version"];
        }

        $version = $parameter["version"];

        $versionActionname = M("version")->where("numberstr = '" . $version . "'")->getField("actionname");

        $obj = A("Version/" . $versionActionname);

        ob_start();   //打开缓冲区

        call_user_func(array($obj, "queryNotifyurl"), $return);

        ob_end_clean();

        exit(call_user_func(array($obj, "queryExit"), $return));
    }



    //前台查询订单状态

    public function queryOrder()
    {
        $orderid = I("orderid");

        $status = M("order")->where(["sysordernumber"=> $orderid])->getField("status");

        $res = $status > 0 ? "ok" : "no";

        $this->ajaxReturn(['status' => $res], 'json');
    }



    /**

     * 检测用户状态,需要检测用户的总状态和交易状态

     */

    //2019-4-24 rml:优化

    //2019-4-28 rml:优化

    final private function CheckUserPayStatus($userid)
    {
        $user_status = M('user')->where(['id' => $userid, 'del' => 0])->field('status,order')->find();

        if ($user_status['status'] != 2) {
            $this->returnJson["msg"] = "用户状态异常,请检查";

            return false;
        }

        if ($user_status['order'] != 0) {
            $this->returnJson["msg"] = "用户交易状态关闭";

            return false;
        }

        return true;
    }





    /**

     * 检测通道分类是否存在

     * $classbm:通道分类编码

     * $userid：用户id

     * 用户以后只需要填写通道分类编码，具体的通道账号则由后台来分配，这样也保证了一定的安全性

     */

    //2019-4-24 rml：优化

    //2019-4-28 rml：优化

    final private function CheckPayapiClass($classbm, $userid)
    {

        //判断通道分类

        $payapiclass = M("payapiclass")->where(['classbm' => $classbm, 'del' => 0])->field('id,status')->find();

        if (!$payapiclass) {
            $this->returnJson["msg"] = "通道分类--$classbm--不存在";

            return false;
        }

        if ($payapiclass['status'] != 1) {
            $this->returnJson["msg"] = "通道分类--$classbm--状态异常!";

            return false;
        }

        //判断通道

        $payapi_id = M("userpayapiclass")->where(['userid' => $userid, 'payapiclassid' => $payapiclass['id']])->getField('payapiid');



        if (!$payapi_id) {
            $this->returnJson["msg"] = "用户在通道分类--$classbm--下无可用通道";

            return false;
        }

        $payapi = M('payapi')->where(['id' => $payapi_id])->field('en_payname,status,del')->find();

        if ($payapi['del'] != 0) {
            $this->returnJson["msg"] = "交易通道不存在!";

            return false;
        }

        if (!$payapi['en_payname']) {
            $this->returnJson["msg"] = "交易通道错误!";

            return false;
        }

        if ($payapi['status'] != 1) {
            $this->returnJson["msg"] = "交易通道状态异常!";

            return false;
        }

        //实例化具体的通道控制器

        $this->obj = A("Pay/" . $payapi['en_payname']);

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
                $this->returnJson["msg"] = "域名非法!";

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



    final private function GetAccount($userid, $ordermoney, $payapiclass_bm)
    {
        $parameter_array = [

            'userid' => $userid,

            'PayName' => $this->obj->PayName,

            'payapiclass_bm' => $payapiclass_bm // 2018-12-28

        ];

        // file_put_contents('aaaa.txt', json_encode($parameter_array));

        $returnJson = $this->returnJson;

        $accountObj = A("Payaccount/Account");

        $this->payaccount = $accountObj->getAccount($parameter_array, $ordermoney, $returnJson);

        $this->returnJson = $returnJson;

        return $this->payaccount;
    }





    /**

     * 生成订单

     * $parameter：标准数据

     * 问题：获取IP的方法记得老系统里有，但是得找找

     */



    final private function CreateOrder($parameter)
    {

//        print_r($this->payaccount);die;

        //获取端口号

        //    $port=$_SERVER["SERVER_PORT"];

        //生成系统订单号

        $sysordernumber = Createsysordernumber($this->obj->sysordernumberleng);

        // 如果系统订单号生成失败 返回报错

        if (is_bool($sysordernumber) && $sysordernumber == false) {
            $this->returnJson["msg"] = "系统订单号生产失败";

            return false;
        }

        //2019-4-28 rml:添加判断：判断订单号是否重复

        if (M("order")->lock(true)->where(['sysordernumber' => $sysordernumber])->count() > 0) {
            $this->returnJson["msg"] = "系统订单号重复,请刷新页面重试";

            return false;
        }



        //产生订单记录

        $par = $parameter['formatdata'];

        $data = [];

        $data["userordernumber"] = $par['orderid']; //用户订单号

        $data["sysordernumber"] = $sysordernumber;  //系统订单号

        $data["shangjiaid"] = $this->payaccount["payapishangjiaid"];  //商家ID

        $data["payapiid"] = $this->payaccount["payapiid"]; //通道ID

        $data["payapiaccountid"] = $this->payaccount["id"]; //通道账号ID

        $data["memberid"] = huoqumemberid($par['userid']);  //商户号

        $data["userid"] = $par['userid'];  //用户ID

        $data["callbackurl"] = $par['callbackurl'];  //同步回调地址

        $data["notifyurl"] = $par['notifyurl']; //异步回调地址

        $data["ordermoney"] = $par['amount']; //提交订单金额

        $data["datetime"] = date('Y-m-d H:i:s'); //创建时间

        $data["version"] = $par['version'];   //对应的提交接口版本号

        $data["payapiclassid"] = $this->payaccount["payapiclassid"]; //通道类别ID

        $data["userip"] = getIp(); //用户IP

        if ($_POST['source_type']) {
            $data["source_type"] = $_POST['source_type']; //来源类型 2019-04-09汪桂芳添加
        }



        //2019-4-29 rml:$this->payaccount:获取到的通道账号数组，利用数组信息即可，而不必再去根据id查询账号信息

        //判断充值零头是否开启,只要开启就加零头

        $data["true_ordermoney"] = $par['amount']; //true_ordermoney：实际订单金额

//        $account_oddment = M('payapiaccount')->where('id=' . $this->payaccount["id"])->field('oddment,min_oddment,max_oddment')->find();

        if ($this->payaccount['oddment'] == 1) {
            $add_oddment = rand($this->payaccount['min_oddment'], $this->payaccount['max_oddment']);

            $data["true_ordermoney"] = $par['amount'] + (floatval($add_oddment) * 0.01);
        }



        //判断交易类型 2019-03-04汪桂芳添加

        //test_status：用户的测试状态；$user_type=3：测试用户

//        if ($par['userid']) {

//            $user_type = M('user')->where('id=' . $par['userid'])->getField('usertype');

//            $user_test_type = M('user')->where('id=' . $par['userid'])->getField('test_status');

//            if ($user_test_type == 0 || $user_type == 3) {

//                $data['type'] = 1;

//            }

//        }

//        //$account_type=3：账号为测试状态

//        if ($this->payaccount["id"]) {

//            $account_type = M('payapiaccount')->where('id=' . $this->payaccount["id"])->getField('type');

//            if ($account_type == 3) {

//                $data['type'] = 1;

//            }

//        }



        //2019-4-29 rml：如果账号为测试类型，用户为测试用户或者测试状态，那么订单为测试  type=0:普通 1=测试

        $user_info = M('user')->where('id=' . $par['userid'])->field('usertype,test_status')->find();

//        $account_type = M('payapiaccount')->where('id=' . $this->payaccount["id"])->getField('type');

        if ($user_info['usertype'] == 3 || $user_info['test_status'] == 0 || $this->payaccount['type'] == 1) {
            $data['type'] = 1;
        }



        //2019-4-29 rml：订单数据的添加没有用到模型,所以用D方法根本就没用

        $order = M("order");

        //先前是在这里返回通道所需要的参数，现在分离出去单独写

//        if (!$order->create($data)) {

//            $this->returnJson["msg"] = $order->getError();

//            //失败时写入日志

//            $this->orderLogAdd($par['userid'], $par['orderid'], $sysordernumber, $order->getError());

//            return false;

//        } else {

        M()->startTrans();    //开启事务

        if (!$order->add($data)) {
            M()->rollback();  //回滚

            $this->orderLogAdd($par['userid'], $par['orderid'], $sysordernumber, '生成订单失败');  //如果添加失败，写入日志

            $this->returnJson["msg"] = "生成订单失败";

            return false;
        }



        //2019-01-28 张杨  记录扩展字段，回调时会原样返回

        if (is_array($par['other'])) {
            if (!M("orderother")->add(['sysordernumber' => $sysordernumber, 'jsonstr' => json_encode($par['other'])])) {
                M()->rollback();  //回滚

                $this->orderLogAdd($par['userid'], $par['orderid'], $sysordernumber, '生成订单时写入扩展字段失败');  //如果添加失败，写入日志

                $this->returnJson["msg"] = "生成订单时写入扩展字段失败";

                return false;
            }
        }



        //2019-4-29 rml:获取到账方案。目前为止，用户的自助账号没有到账方案

        //own_type:1=通道下的账号 2=用户下的账号 3=用户的自助账号

//        if ($this->payaccount["own_type"] != 3) {



        $feilv = $this->getFeilv($par['userid']);   //获取成本费率,交易费率,单笔最低手续费

        $trade_money = $feilv['trade_feilv'] * $data["true_ordermoney"];   //交易手续费 = 交易费率 * 实际金额



        //获取真正的交易手续费:用户计算出的交易手续费与最低手续费做对比

        if ($trade_money <= $feilv['trade_min_money']) {
            $trade_money = $feilv['trade_min_money'];
        }



        //判断交易手续费是否大于订单金额

        if ($trade_money >= $par['amount']) {
            M()->rollback();  //回滚

            $this->orderLogAdd($par['userid'], $par['orderid'], $sysordernumber, '单笔最低手续费[' . $feilv['trade_min_money'] . ']大于订单金额');  //如果添加失败，写入日志

            $this->returnJson["msg"] = '单笔最低手续费[' . $feilv['trade_min_money'] . ']大于订单金额';

            return false;
        }



        //只有自助账号没有到账方案的设置功能

        if ($this->payaccount["own_type"] != 3) {

            //获取到账方案

            //如果是用户下的账号：如果用户账号有设置到账方案，则查询用户的到账方案，如果不存在，则使用通道账号的

            if ($this->payaccount["own_type"] == 2) {
                $user_moneyclass = M('usermoneyclass')->where(['payapiaccountid' => $this->payaccount["id"], 'userid' => $par['userid']])->getField('moneytypeclass_id');

                if ($user_moneyclass) {
                    $money_class_id = $user_moneyclass;
                } else {
                    $money_class_id = $this->payaccount["moneytypeclassid"];
                }
            }

            if ($this->payaccount["own_type"] == 1) {
                $money_class_id = $this->payaccount["moneytypeclassid"];
            }

            //判断到账方案是否删除:有则获取冻结方案。没有则为空

            //判断是否存在冻结方案  1=存在 2=不存在

            $money_class_del = M('moneytypeclass')->where('id=' . $money_class_id)->getField('del');

            if ($money_class_del == 0) {
                $money_type_list = M('moneytype')->where(['moneytypeclassid' => $money_class_id, 'del' => 0])->field('id,moneytypename,dzsj_day,jiejiar,dzsj_time,dzbl')->select();

                $exist_money_type = 1;

                if (!$money_type_list) {
                    $exist_money_type = 2;

                    $money_type_list = [];
                }
            } else {
                $exist_money_type = 2;

                $money_type_list = [];
            }
        } else {
            $money_class_id = 0;

            $exist_money_type = 2;

            $money_type_list = [];
        }





        //将金额信息写入pay_ordermoney表

        //还需要判断账号所属者类型

        $ordermoney_id = $this->setOrderMoney($feilv, $sysordernumber, $par['amount'], $exist_money_type, $money_class_id, $data["true_ordermoney"], $trade_money); //添加实际金额参数 2019-02-28汪桂芳修改

        if (!$ordermoney_id) {
            M()->rollback();  //回滚

            $this->orderLogAdd($par['userid'], $par['orderid'], $sysordernumber, '生成订单时订单金额信息写入失败');

            $this->returnJson["msg"] = '生成订单时订单金额信息写入失败';

            return false;
        }



        //只有存在冻结方案时：写入冻结金额明细表 pay_orderfreezemoney

        if ($exist_money_type == 1) {
            $data_arr = [];

            $acture_money = $data["true_ordermoney"] - $trade_money;

            foreach ($money_type_list as $key => $val) {

//                    $freeze_dzbl = M('moneytype')->where('id = ' . $val['id'])->getField('dzbl');

                $freeze_mooney = $acture_money * $val['dzbl'];

                $expect_time = strtotime($val['dzsj_time']) + (intval($val['dzsj_day']) * 24 * 60 * 60);  //计算预计时间

//                    $expect_time = date('Y-m-d H:i:s', $expect_time);

                $data_freeze = [

                    'ordermoney_id' => $ordermoney_id,  //订单金额表id

                    'moneytype_id' => $val['id'],  //冻结金额方案表id

                    'user_id' => $par['userid'],  //用户id

                    'freeze_money' => $freeze_mooney,  //冻结的金额

                    'expect_time' => date('Y-m-d H:i:s', $expect_time),  //预计到达时间

                    'date_time' => date('Y-m-d H:i:s'),  //记录添加时间

                    'sysordernumber' => $sysordernumber,  //系统订单号 2019-02-15汪桂芳添加

//                        'freezeordernumber'=>$sysordernumber  //系统订单号 2019-02-15汪桂芳添加

                ];

                $data_arr[] = $data_freeze;
            }

            if (!M('orderfreezemoney')->addAll($data_arr)) {
                M()->rollback();  //回滚

                $this->orderLogAdd($par['userid'], $par['orderid'], $sysordernumber, '生成订单时冻结金额写入失败');

                $this->returnJson["msg"] = "生成订单时冻结金额写入失败";

                return false;
            }
        }



//        }



        //将用户提交的参数存入数据库，信息以json格式存储

        $userparameter = $this->setUserPar($par['userid'], $sysordernumber, $parameter);

        if (!$userparameter) {
            M()->rollback();  //回滚

            $this->orderLogAdd($par['userid'], $par['orderid'], $sysordernumber, '生成订单时用户提交信息写入失败');  //如果添加失败，写入日志

            $this->returnJson["msg"] = "生成订单时用户提交信息写入失败";

            return false;
        }



        //如果successrate统计表有统计数据  count+1   否则 添加一条新数据

        $successrate = SuccessrateModel::getInfoByUser($par['userid'], $this->payaccount["payapiid"], $this->payaccount["payapiclassid"]);

        if ($successrate) {
            $res_successrate = SuccessrateModel::countInc($successrate['id']);
        } else {
            $res_successrate = SuccessrateModel::addSuccessrate($par['userid'], $this->payaccount["payapiid"], $this->payaccount["payapiclassid"]);
        }

        if (!$res_successrate) {
            M()->rollback();  //回滚

            $this->orderLogAdd($par['userid'], $par['orderid'], $sysordernumber, '生成订单时统计表信息写入失败');  //如果添加失败，写入日志

            $this->returnJson["msg"] = "生成订单时统计表信息写入失败";

            return false;
        }

        M()->commit();    //事务提交

        return true;

//    }
    }





    /*

    *

     * 获取成本费率，交易费率

     * @param $account_id

     * @param $user_id

     * @param $order_money

     */



    /**

     * 获取成本费率，交易费率

     * @param $user_id :用户id

     * @return array

     */

    final private function getFeilv($user_id)
    {

        //获取账户成本费率

//        $cost_feilv = M('payapiaccount')->lock(true)->where('id=' . $account_id)->getField('cbfeilv');  //获取成本费率

        //获取交易费率和单笔最低交易手续费

        $feilv = $this->getUserOrderFeilv($user_id);

        $data = [

            'cost_feilv' => floatval($this->payaccount['cbfeilv']), //成本费率

            'trade_feilv' => $feilv['trade_feilv'],  //交易费率

            'trade_min_money' => $feilv['trade_min_money'],   //单笔最低手续费

        ];

        return $data;
    }





    /**

     * 获取交易费率和单笔最低手续费

     * @param $user_id ： 用户id

     * @param $payapiclass_id

     * @return mixed

     */

    //根据$this->payaccount["own_type"]判断账号所属者类型：1=通道下的账号 2=用户下的账号 3=用户的自助账号。目前为止，用户的自助账号没有设置交易费率

    //如果为用户的账号，判断该用户是否设置了交易费率和单笔最低手续费。有则用用户的，否则用通道分类的

    //如果为通道的账号，则直接用通道分类的数据

    final private function getUserOrderFeilv($user_id, $payapiclass_id = '')
    {
        if ($this->payaccount["own_type"] == 2) {
            $user_feilv = M('userpayapiclass')->lock(true)->where(['payapiclassid' => $this->payaccount['payapiclassid'], 'userid' => $user_id])->field('order_feilv,order_min_feilv')->find();

            if (floatval($user_feilv['order_feilv'])) {
                $trade_feilv = $user_feilv['order_feilv'];
            } else {
                $trade_feilv = M('payapiclass')->lock(true)->where(['id' => $this->payaccount['payapiclassid']])->getField('order_feilv');
            }

            if (floatval($user_feilv['order_min_feilv'])) {
                $trade_min_feilv = $user_feilv['order_min_feilv'];
            } else {
                $trade_min_feilv = M('payapiclass')->lock(true)->where(['id' => $this->payaccount['payapiclassid']])->getField('order_min_feilv');
            }
        }

        if ($this->payaccount["own_type"] == 1) {
            $payapi_class_feilv = M('payapiclass')->lock(true)->where(['id' => $this->payaccount['payapiclassid']])->field('order_feilv,order_min_feilv')->find();

            $trade_feilv = $payapi_class_feilv['order_feilv'];

            $trade_min_feilv = $payapi_class_feilv['order_min_feilv'];
        }

        $data = [

            'trade_feilv' => floatval($trade_feilv),

            'trade_min_money' => floatval($trade_min_feilv),

        ];

        //如果是自助账号：费率，最低手续费为0

        if ($this->payaccount["own_type"] == 3) {
            $data = [

                'trade_feilv' => 0,

                'trade_min_money' => 0,

            ];
        }

        return $data;
    }











    /**

     * 将金额信息写入ordermoney表:手续费等都用实际金额计算

     * @param $feilv :费率数组

     * @param $sysordernumber：系统订单号

     * @param $order_money ：订单金额

     * @param $exist_money_type ：是否存在冻结金额方案数组 1=存在 2=不存在

     * @param $money_type_list ：冻结金额方案数组

     * @param $true_order_money ：实际金额

     * @param $trade_money ：实际交易手续费

     * @return mixed

     */



    //冻结金额 = （实际金额 - 交易手续费）* 冻结比例

    //实际到账金额=（实际金额 - 交易手续费）* 立即到账比例 （立即到账比例 + 冻结比例 = 1）

    final private function setOrderMoney($feilv, $sysordernumber, $order_money, $exist_money_type, $money_class_id, $true_order_money, $trade_money)
    {
        $cost_money = $feilv['cost_feilv'] * $true_order_money;  //成本金额 = 成本费率 * 实际金额

        //获取减去交易手续费后的金额

        $actual_money = $true_order_money - $trade_money;

        //通过$exist_money_type:获取冻结比例  1=有冻结比例  2=没有

        if ($exist_money_type == 1) {
            $freeze_dzbl = M('moneytype')->lock(true)->where(['moneytypeclassid' => $money_class_id, 'del' => 0])->sum('dzbl');  //总的冻结比例

            $freeze_money = $actual_money * floatval($freeze_dzbl);  //总的冻结金额

            $money = $actual_money * (1 - floatval($freeze_dzbl));  //实际到账金额
        } else {
            $freeze_money = 0;

            $money = $actual_money;
        }



        //写入ordermoney表

        $order_data = [

            'sysordernumber' => $sysordernumber,  //系统订单号

            'ordermoney' => $order_money,  //提交订单金额

            'true_ordermoney' => $true_order_money,  //实际订单金额

            'traderate' => $feilv['trade_feilv'],  //交易费率

            'moneytrade' => $trade_money,  //交易手续费

            'costrate' => $feilv['cost_feilv'],  //成本费率

            'moneycost' => $cost_money,  //成本费

            'money' => $money,   //实际到账金额

            'freezemoney' => $freeze_money  //冻结金额

        ];

        return M('ordermoney')->add($order_data);
    }





    /**

     * 将用户提交的参数存入数据库，信息以json格式存储

     * @param $parameter：用户请求的原始数据

     * @param $user_id：用户id

     * @param $sysordernumber：系统订单号

     */



    //2019-2-14 任梦龙：修改字段设计，将order_id修改为user_id：用户id



    final private function setUserPar($user_id, $sysordernumber, $parameter)
    {
        $user_parameter = [

            'user_id' => $user_id,

            'sys_order_num' => $sysordernumber,

            'parameter' => json_encode($parameter)  //用户提交的参数

        ];

        return M('userparameter')->add($user_parameter);
    }





    /**

     * 修改金额

     * $trans_id：订单号

     * $pay_name：通道编码

     * $return_type：回调类型 0=异步 1=同步

     * $trans_money：交易金额

     * $return_echo：返回值

     * 根据老版本大概的思路：

     * 1.判断该笔订单是否存在，否则拦截回滚，记入日志

     * 2.为新订单时（status=0），进行修改金额操作，计算金额，利润，费率等问题--（重点）

     * 3.修改完后，进行回调操作：异步0  同步1，此时status=1

     * 4.首先获取用户数据，即回调版本控制器中方法将信息返回给用户

     * 5.同步：发起同步页面请求，页面跳转

     * 6.异步：发起异步请求，判断是否收到用户回馈的ok,如果收到，则改变status=2，没有则记入日志，中止程序

     *

     * 修改金额这里流程不是很清楚，明天问，搞清楚

     * 还有关于测试通道的问题，也得明天解决，明天大搞做完金额修改这块，星期五好测试

     */



    final protected function editMoney($trans_id, $pay_name, $return_type = 1, $trans_money = 0, $return_echo = '', $verify = 0, $supplement = 0)
    {
        ob_start();   //打开缓冲区

        $order = M('order');

        //查询是否存在此订单

        $order_info = $order->lock(true)->where("sysordernumber='" . $trans_id . "'")->find();

        if (!$order_info) {
            $this->orderlogadd('', '', '', '订单号：' . $trans_id . ' 不存在');

            return $this->orderVerifyReturn($verify, $supplement, '订单号：' . $trans_id . ' 不存在');
        }



        //判断通道编码



        $paypai = M('payapi')->lock(true)->where('id=' . $order_info['payapiid'] . " and en_payname='" . $pay_name . "'")->count();

        if ($paypai <= 0) {
            $this->orderlogadd($order_info['userid'], $order_info['userordernumber'], $order_info['sysordernumber'], '用户通道编码不存在');

            return $this->orderVerifyReturn($verify, $supplement, '用户通道编码不存在');
        }



        //判断系统金额与返回的金额是否一致

        if (floatval($trans_money) != floatval($order_info['true_ordermoney'])) {
            $this->orderlogadd($order_info['userid'], $order_info['userordernumber'], $order_info['sysordernumber'], '订单金额错误，系统订单金额' . $order_info['true_ordermoney'] . ',返回金额' . $trans_money);

            return $this->orderVerifyReturn($verify, $supplement, '订单金额错误，系统订单金额' . $order_info['true_ordermoney'] . ',返回金额' . $trans_money);
        }



        //修改金额

        if ($order_info['status'] == 0) {
            M()->startTrans();

            //2019-8-7 rml：解开查询操作

            if (method_exists(A("Pay/" . $pay_name), "queryPayOrder")) { //如果存在查询方法，查询订单状态

                $orderStatus = call_user_func(array(A("Pay/" . $pay_name), "queryPayOrder"), $trans_id);

                if ($orderStatus) {
                    $this->orderlogadd($order_info['userid'], $order_info['userordernumber'], $order_info['sysordernumber'], '订单号：' . $trans_id . ' 查询订单状态为支付成功');
                } else {
                    M()->rollback();  //回滚

                    $this->orderlogadd($order_info['userid'], $order_info['userordernumber'], $order_info['sysordernumber'], '订单号：' . $trans_id . ' 未支付');

                    return $this->orderVerifyReturn($verify, $supplement, '用户订单号为' . $order_info['userordernumber'] . '的订单未支付');
                }
            }



            $data = [

                'status' => 1,  //支付成功，还未响应

                'successtime' => date('Y-m-d H:i:s'),

            ];



            if (!$order->lock(true)->where("sysordernumber='" . $trans_id . "'")->setField($data)) {//修改状态

                M()->rollback();  //回滚

                $this->orderlogadd($order_info['userid'], $order_info['userordernumber'], $order_info['sysordernumber'], '订单状态修改失败');

                return $this->orderVerifyReturn($verify, $supplement, '订单状态修改失败');
            }



            //2019-04-08汪桂芳修改

            //修改用户余额和冻结金额

            $changemoney = M('ordermoney')->where("sysordernumber='" . $order_info['sysordernumber'] . "'")->getField('money'); //获取新增金额

            //2019-7-19 rml:判断是否有usermoney记录

            $find_usermoney = M('usermoney')->where([

                'userid' => ['eq', $order_info["userid"]]

            ])->find();

            if (!$find_usermoney) {
                M('usermoney')->add([

                    'userid' => $order_info["userid"]

                ]);
            }

            $oldmoney = M("usermoney")->lock(true)->where("userid=" . $order_info["userid"])->getField("money"); //用户余额

            $nowmoney = $changemoney + $oldmoney;  //新增后的金额

            $freeze_changemoney = M('ordermoney')->where("sysordernumber='" . $order_info['sysordernumber'] . "'")->getField('freezemoney'); //获取新增冻结金额

            $freeze_oldmoney = M("usermoney")->lock(true)->where("userid=" . $order_info["userid"])->getField("freezemoney"); //用户冻结余额

            $freeze_nowmoney = $freeze_changemoney + $freeze_oldmoney;  //新增后的冻结金额

            $usermoney_savedata = [

                'money' => $nowmoney,

                'freezemoney' => $freeze_nowmoney

            ];

            if (!M("usermoney")->lock(true)->where("userid=" . $order_info["userid"])->save($usermoney_savedata)) {
                M()->rollback();  //回滚

                $this->orderlogadd($order_info['userid'], $order_info['userordernumber'], $order_info['sysordernumber'], '用户支付完成收到回调后变更用户余额失败！');

                return $this->orderVerifyReturn($verify, $supplement, '用户支付完成收到回调后变更用户余额失败！');
            }



            //添加余额的资金变动记录

            $money_change = [

                'userid' => $order_info['userid'],   //用户id

                'oldmoney' => $oldmoney,   //原始金额

                'changemoney' => $changemoney,   //改变金额

                'nowmoney' => $nowmoney,   //改变后的金额

                'datetime' => date('Y-m-d H:i:s'),   //添加时间

                'transid' => $trans_id,   //订单号

                'payapiid' => $order_info['payapiid'],   //通道id

                'accountid' => $order_info['payapiaccountid'],   //账号id

                'changetype' => 4,   //金额变动类型

            ];



            if (!M('moneychange')->add($money_change)) {
                M()->rollback();  //回滚

                $this->orderlogadd($order_info['userid'], $order_info['userordernumber'], $order_info['sysordernumber'], '支付成功后记录资金变动失败！');

                return $this->orderVerifyReturn($verify, $supplement, '支付成功后记录资金变动失败！');
            }



            //2019-03-06  张杨 支付成功，把钱加上去以后就提交事务



            M()->commit();    //事务提交

            /**

             * 2019-02-28汪桂芳添加:提成处理程序

             * 2019-03-04汪桂芳修改:将提成全部提出成函数

             */

            $this->orderTiCheng($order_info);



            //支付成功 在统计表加记录 20190326  黄----------------------------------------------

            //$order_info

            $y = date('Y', strtotime($order_info['datetime']));

            $m = date('m', strtotime($order_info['datetime']));

            $d = date('d', strtotime($order_info['datetime']));

            $h = date('H', strtotime($order_info['datetime']));

            //提成总金额

            $tc_summoney = OrdercommissionModel::getSummoneyByOrder($order_info['sysordernumber']);

            //成本

            $ordermoney = M('ordermoney')->where(['sysordernumber' => ['eq', $order_info['sysordernumber']]])->find();

            //利润=总手续费-提成总金额-成本手续费

            $profit = $ordermoney['moneytrade'] - $tc_summoney - $ordermoney['moneycost'];



            $where = [

                'y' => ['eq', $y],

                'm' => ['eq', $m],

                'd' => ['eq', $d],

                'h' => ['eq', $h],

                'userid' => ['eq', $order_info['userid']],

                'payapiclassid' => ['eq', $order_info['payapiclassid']],

                'payapiid' => ['eq', $order_info['payapiid']],

                'type' => ['eq', 1],

            ];

            $successrate = SuccessrateModel::getInfo($where);

            //$result = SuccessrateModel::countInc($successrate['id']);

            SuccessrateModel::successcountInc($successrate['id']);

            $statistic = StatisticModel::getInfoByUser($where);

            if ($statistic) {
                StatisticModel::editInfo($where, [

                    'amount' => $statistic['amount'] + $order_info['ordermoney'],

                    'profit' => $statistic['profit'] + $profit,

                    'count' => $statistic['count'] + 1,

                ]);
            } else {
                M('statistic')->add([

                    'y' => $y,

                    'm' => $m,

                    'd' => $d,

                    'h' => $h,

                    'userid' => $order_info['userid'],

                    'payapiclassid' => $order_info['payapiclassid'],

                    'payapiid' => $order_info['payapiid'],

                    'type' => 1,

                    "amount" => $order_info['ordermoney'],

                    "profit" => $profit,

                    "count" => 1

                ]);
            }





            //支付成功 在统计表加记录 20190326  黄----------------------------------------------





            /**

             * 触发启动冻结金额解冻

             */

            //2019-03-02 张杨  支付成功后修改对应的冻结金额状态为可解冻

            $this->statusOrderfreeze($order_info['userordernumber'], $order_info["sysordernumber"], $order_info["userid"]);

            $thaw_outside = true;  //是否发送到外部去定时解冻

            if ($thaw_outside) {
                $this->thawOutside($order_info['userordernumber'], $order_info["sysordernumber"], $order_info["userid"]);
            }
        }



        /**

         * 2019-03-04汪桂芳:回调用户

         */

        if (!$verify) {
            $res = $this->orderReturnUser($order_info, $return_type, $return_echo, $verify, $supplement);

            if (is_bool($res) || is_array($res)) {
                return $res;
            } elseif (is_string($res)) {
                exit($res);
            }
        } else {

            //2019-8-7 rml：解开查询操作

            if (method_exists(A("Pay/" . $pay_name), "queryPayOrder")) { //如果存在查询方法，查询订单状态

                $orderStatus = call_user_func(array(A("Pay/" . $pay_name), "queryPayOrder"), $trans_id);

                if ($orderStatus) {
                    $res['status'] = 'ok';

                    $res['msg'] = '验证成功';

                    $this->orderlogadd($order_info['userid'], $order_info['userordernumber'], $order_info['sysordernumber'], '订单号：' . $trans_id . ' 查询订单状态为支付成功');
                } else {
                    $res['status'] = 'no';

                    $res['msg'] = '验证失败';

                    $this->orderlogadd($order_info['userid'], $order_info['userordernumber'], $order_info['sysordernumber'], '订单号：' . $trans_id . ' 未支付');
                }
            } else {
                $res['status'] = 'no';

                $res['msg'] = '验证失败';
            }

            return $res;
        }
    }



    /**

     * 2019-03-04汪桂芳:提成相关

     */

    final private function orderTiCheng($order_info)
    {

        //判断是否需要提成

        $max_commission_level = M('website')->where('id=1')->getField('commission_level');//查询最高提成等级

        $sup_list = $this->getCommissionList($order_info['userid'], $order_info['payapiclassid'], [], $max_commission_level, 0);

        if ($sup_list) {
            foreach ($sup_list as $k => $v) {

                //生成提成记录

                $commission_data = [

                    'sysordernumber' => $order_info['sysordernumber'],

                    'tc_userid' => $v['tc_userid'],

                    'tc_dengji' => $v['tc_dengji'],

                    'tc_feilv' => $v['commission_feilv'],

                    'tc_money' => $order_info['true_ordermoney'] * $v['commission_feilv'],

                    'tc_type' => 0,

                    'date_time' => date('Y-m-d H:i:s')

                ];



                if (!M('ordercommission')->add($commission_data)) {
                    $this->orderlogadd($order_info['userid'], $order_info['userordernumber'], $order_info['sysordernumber'], '支付成功后提成记录失败！');

                    return false;
                }



                //当提成费率大于0时才修改提成用户的金额

                if ($v['commission_feilv'] > 0) {

                    //修改usermoney表记录

                    $tc_usermoney = M('usermoney')->where('userid=' . $v['tc_userid'])->find();

                    if ($tc_usermoney) {
                        $tc_old_money = $tc_usermoney['money'];

                        //修改提成用户金额

                        if (!M('usermoney')->where('userid=' . $v['tc_userid'])->setField('money', $tc_old_money + $commission_data['tc_money'])) {
                            $this->orderlogadd($order_info['userid'], $order_info['userordernumber'], $order_info['sysordernumber'], '支付成功后提成用户金额修改失败！');

                            return false;
                        }
                    } else {
                        $tc_old_money = 0;

                        //添加提成用户金额

                        if (!M('usermoney')->add(['userid' => $v['tc_userid'], 'money' => $tc_old_money + $commission_data['tc_money']])) {
                            $this->orderlogadd($order_info['userid'], $order_info['userordernumber'], $order_info['sysordernumber'], '支付成功后提成用户金额修改失败！');

                            return false;
                        }
                    }



                    //添加资金变动记录

                    $tc_money_change = [

                        'userid' => $v['tc_userid'],

                        'oldmoney' => $tc_old_money,

                        'changemoney' => $commission_data['tc_money'],

                        'nowmoney' => $tc_old_money + $commission_data['tc_money'],

                        'datetime' => date('Y-m-d H:i:s'),

                        'transid' => $order_info['sysordernumber'],   //订单号

                        'payapiid' => $order_info['payapiid'],   //通道id

                        'accountid' => $order_info['payapiaccountid'],   //账号id

                        'changetype' => 7,

                        'remarks' => "交易提成"

                    ];



                    if (!M('moneychange')->add($tc_money_change)) {
                        $this->orderlogadd($order_info['userid'], $order_info['userordernumber'], $order_info['sysordernumber'], '支付成功后记录提成用户的资金变动失败！');

                        return false;
                    }
                }
            }
        }
    }





    /**

     * 2019-03-04汪桂芳:回调用户

     */

    final private function orderReturnUser($order_info, $return_type, $return_echo, $verify, $supplement)
    {
        $version = $order_info["version"];

        $versionActionname = M("version")->where("numberstr = '" . $version . "'")->getField("actionname");

        if (!$obj = A("Version/" . $versionActionname)) {
            $this->orderlogadd($order_info['userid'], $order_info['userordernumber'], $order_info['sysordernumber'], '获取回调入口失败！');

            return $this->orderVerifyReturn($verify, $supplement, '获取回调入口失败！');
        }



        //生成回调参数

        $returndata = $this->CreateReturnData($order_info, "00");

        if ($return_type == 1) { //同步回调

            $this->orderLogAdd($order_info['userid'], $order_info['userordernumber'], $order_info['sysordernumber'], ',成功向【' . $order_info["callbackurl"] . '】发起页面跳转回调');

            //2019-04-03汪桂芳添加:判断是否需要先跳到广告页面

            $adv = $this->checkPayapiUserAdv($order_info['payapiid'], $order_info['userid']);

            if ($adv) {

                //先跳转广告页面

                //将数据存储到数据库

                $advjumpdata_id = $this->addAdvJumpData($versionActionname, $returndata);

                $this->jumpAdv($advjumpdata_id, $adv);
            } else {

                //直接回调用户

                call_user_func(array($obj, "callbackurl"), $returndata);
            }
        } elseif ($return_type == 0) { //异步回调

            $this->orderLogAdd($order_info['userid'], $order_info['userordernumber'], $order_info['sysordernumber'], ',成功向【' . $order_info["notifyurl"] . '】发起异步回调');

            $return = call_user_func(array($obj, "notifyurl"), $returndata);

            if (!is_array($return)) {
                $this->orderLogAdd($order_info['userid'], $order_info['userordernumber'], $order_info['sysordernumber'], '收到异步回调响应的数据格式错误，[' . $return . ']');

                return $this->orderVerifyReturn($verify, $supplement, '收到异步回调响应的数据格式错误');
            }

            if ($return["status"]) {
                $this->orderLogAdd($order_info['userid'], $order_info['userordernumber'], $order_info['sysordernumber'], '成功收到异步回调响应，收到的响应数据为[' . $return["reutrncontent"] . ']');

                $order = M('order');

                $data = [

                    "status" => intval(2),  //支付成功，还未响应

                    "successtime_notifyurl" => date("Y-m-d H:i:s"),

                ];



                if ($supplement) {
                    $res['status'] = 'ok';

                    $res['msg'] = '成功收到异步回调响应，收到的响应数据为[' . $return["reutrncontent"] . ']';

                    if (!$order->lock()->where("sysordernumber='" . $order_info['sysordernumber'] . "'")->setField($data)) {//修改状态

                        $this->orderlogadd($order_info['userid'], $order_info['userordernumber'], $order_info['sysordernumber'], '异步回调成功订单状态修改失败');

                        $res['msg'] = '异步回调成功订单状态修改失败';
                    }

                    return $res;
                } else {
                    if (!$order->lock(true)->where("sysordernumber='" . $order_info['sysordernumber'] . "'")->setField($data)) {//修改状态

                        $this->orderlogadd($order_info['userid'], $order_info['userordernumber'], $order_info['sysordernumber'], '异步回调成功订单状态修改失败');

                        return false;
                    } else {
                        ob_end_clean();

                        return $return_echo;
                    }
                }
            } else {
                $this->orderLogAdd($order_info['userid'], $order_info['userordernumber'], $order_info['sysordernumber'], '收到异步回调响应，但状态未成功，收到的回调数据为[' . $return["reutrncontent"] . ']');

                return $this->orderVerifyReturn($verify, $supplement, '收到异步回调响应，但状态未成功');
            }
        }
    }



    //判断是否需要跳转到广告页面

    protected function checkPayapiUserAdv($payapi_id, $user_id)
    {
        $payapi_jump = PayapiModel::getPayapiInfo($payapi_id);

        if ($payapi_jump['jump_type'] == 1) {

            //有广告

            //判断用户是否排除

            $payapi_user = PayapijumpModel::getPayapiUser($payapi_id, $user_id);

            if ($payapi_user) {

                //排除了此用户

                return false;
            } else {

                //先跳转广告页面

                //判断通道是否选择广告模板

                $adv_templateid = $payapi_jump['adv_templateid'];

                if ($adv_templateid) {
                    $adv = AdvtemplateModel::getAdvInfo($adv_templateid);
                }

                if (!$adv_templateid || !$adv) {

                    //查询默认模板

                    $adv_templateid = AdvtemplateModel::getDefaultAdv();

                    if (!$adv_templateid) {

                        //跳过广告

                        return false;
                    }

                    $adv = AdvtemplateModel::getAdvInfo($adv_templateid);
                }

                return $adv;
            }
        } else {

            //无广告

            return false;
        }
    }



    //存储同步跳转时需要传递的数据

    protected function addAdvJumpData($versionActionname, $returndata)
    {
        $code = AdvjumpdataModel::getCode();

        $data = [

            'code' => $code,

            'versionactionname' => $versionActionname,

            'returndata_json' => json_encode($returndata),

        ];

        return AdvjumpdataModel::addAdvInfo($data);
    }



    //广告页面

    public function jumpAdv($advjump_id, $adv)
    {
        $code = AdvjumpdataModel::getCodeById($advjump_id);

        $this->assign('code', $code);

        $jump_url = U('advJump');

        $this->assign('jump_url', $jump_url);

        $mobile = $this->isMobile();

        if ($mobile) {
            $this->display('AdvTemplate/' . $adv['wap_template_name']);
            ;
        } else {
            $this->display('AdvTemplate/' . $adv['pc_template_name']);
            ;
        }
    }



    //广告页面完成后的同步跳转回调

    public function advJump()
    {
        $code = I('get.code');

        $data = AdvjumpdataModel::getInfoByCode($code);

        $returndata = json_decode($data['returndata_json'], true);

        call_user_func(array(A("Version/" . $data['versionactionname']), "callbackurl"), $returndata);
    }



    //判断是否为手机端访问

    public function isMobile()
    {

        // 如果有HTTP_X_WAP_PROFILE则一定是移动设备

        if (isset($_SERVER['HTTP_X_WAP_PROFILE'])) {
            return true;
        }



        //此条摘自TPM智能切换模板引擎，适合TPM开发

        if (isset($_SERVER['HTTP_CLIENT']) && 'PhoneClient' == $_SERVER['HTTP_CLIENT']) {
            return true;
        }

        //如果via信息含有wap则一定是移动设备,部分服务商会屏蔽该信息

        if (isset($_SERVER['HTTP_VIA'])) {

            //找不到为flase,否则为true

            return stristr($_SERVER['HTTP_VIA'], 'wap') ? true : false;
        }

        //判断手机发送的客户端标志,兼容性有待提高

        if (isset($_SERVER['HTTP_USER_AGENT'])) {
            $clientkeywords = array(

                'nokia', 'sony', 'ericsson', 'mot', 'samsung', 'htc', 'sgh', 'lg', 'sharp', 'sie-', 'philips', 'panasonic', 'alcatel', 'lenovo', 'iphone', 'ipod', 'blackberry', 'meizu', 'android', 'netfront', 'symbian', 'ucweb', 'windowsce', 'palm', 'operamini', 'operamobi', 'openwave', 'nexusone', 'cldc', 'midp', 'wap', 'mobile'

            );

            //从HTTP_USER_AGENT中查找手机浏览器的关键字

            if (preg_match("/(" . implode('|', $clientkeywords) . ")/i", strtolower($_SERVER['HTTP_USER_AGENT']))) {
                return true;
            }
        }

        //协议法，因为有可能不准确，放到最后判断

        if (isset($_SERVER['HTTP_ACCEPT'])) {

            // 如果只支持wml并且不支持html那一定是移动设备

            // 如果支持wml和html但是wml在html之前则是移动设备

            if ((strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') !== false) && (strpos($_SERVER['HTTP_ACCEPT'], 'text/html') === false || (strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') < strpos($_SERVER['HTTP_ACCEPT'], 'text/html')))) {
                return true;
            }
        }

        return false;
    }



    /**

     * 2019-03-02 张杨 交易订单支付成功后，修改金额冻结记录里的相应冻结金额状态为可解冻

     */



    final private function statusOrderfreeze($userordernumber, $sysordernumber, $userid)
    {
        $all_orderfreeze = M('orderfreezemoney')->where("sysordernumber='" . $sysordernumber . "'")->select();



        //修改order_status和freezeordernumber



        foreach ($all_orderfreeze as $k) {
            $save_orderfreeze = [];



            $save_orderfreeze['order_status'] = 1;//订单已支付



            $save_orderfreeze['task'] = 1;//订单需要发送定时任务



            $save_orderfreeze['freezeordernumber'] = Createfreezeordernumber($this->freezeordernumberleng);//2019-03-08汪桂芳修改



            // 如果冻结订单号生成失败 返回报错



            if (is_bool($save_orderfreeze['freezeordernumber']) && $save_orderfreeze['freezeordernumber'] == false) {
                $this->orderlogadd($userid, $userordernumber, $sysordernumber, '支付成功后id为【' . $k["id"] . '】冻结订单号生产失败');



                continue;
            }



            if (!M('orderfreezemoney')->where("id=" . $k["id"])->save($save_orderfreeze)) {
                $this->orderlogadd($userid, $userordernumber, $sysordernumber, '支付成功后id为【' . $k["id"] . '】冻结订单号修改冻结金额记录失败！');



                continue;
            }
        }
    }



    public function test()
    {
        $this->thawOutside('1000120190308100244', 'fy8AVeADRDf2nM80EDEehdvFt7bWF9eo', 1);
    }



    /**

     * 2019-03-02 张杨，支付成功后把解冻金额记录发送到外部去定时触发解冻

     */



    public function thawOutside($userordernumber, $sysordernumber, $userid)
    {

        //发送到独立系统的定时任务



        //拼接请求参数

        $send_data = [

            'version' => C('AMNPAY_VERSION'),  //版本号

            'merid' => C('AMNPAY_MERID'),  //独立系统分配的商户编号,从配置文件读出

            'notifyurl' => 'http://www.baidu.com',  //异步回调地址,不能为空,暂时没有,后期可能会加

            'createtime' => date('Y-m-d H:i:s'),  //系统请求接口创建任务的时间

        ];



        //查询交易订单生成的所有冻结订单

        $new_all_orderfreeze = M('orderfreezemoney')->where("sysordernumber='" . $sysordernumber . "'")->select();

//        print_r($new_all_orderfreeze);die;

        if (!$new_all_orderfreeze) {
            return false;
        }



        $content = [];

        foreach ($new_all_orderfreeze as $k => $v) {
            $content[] = [

                'merorderno' => $v['freezeordernumber'],  //冻结订单号

                'request_url' => C('AUTO_UNFREEZE_URL'),  //请求路径,从配置文件中读出

                'request_time' => $v['expect_time'],  //接口请求系统开始解冻的时间

            ];
        }



        $send_data['content'] = json_encode($content);



        //签名



        $userKey = C('AMNPAY_SECRETKEY'); //与独立系统对接的密钥,从配置文件读取

        $signStr = getTaskSignStr($send_data); //签名字符串

        openssl_sign($signStr, $sign, $userKey);  //rsa加密

        $send_data['sign'] = base64_encode($sign);

        //curl请求定时任务接口



        $post_url = C('MORE_TASK_UNFREEZE_URL');//多条定时任务请求接口

        $task_return = curlPost($post_url, $send_data);

//        $this->setHtml2($post_url,$send_data,true);

        $task_return = json_decode($task_return, true);

        //判断定时任务是否发送成功

        if ($task_return['status'] != '0000') {
            $this->orderlogadd($userid, $userordernumber, $sysordernumber, '支付成功后冻结订单请求定时任务失败,原因是:' . $task_return['msg']);

            return false;
        }



        //修改数据表

        if (!M('orderfreezemoney')->where("sysordernumber='" . $sysordernumber . "'")->setField('send', 1)) {
            $this->orderlogadd($userid, $userordernumber, $sysordernumber, '支付成功后冻结订单请求定时任务失败！');

            return false;
        }
    }





    //2019-02-28汪桂芳添加:提成用户列表



    final private function getCommissionList($user_id, $payapiclass_id, $sup_list = [], $max_commission_level, $tc_dengji = 0)
    {
        if ($tc_dengji < $max_commission_level) {
            $superior_userid = M('user')->where('id=' . $user_id)->getField('superiorid');



            if ($superior_userid) {
                $user_feilv = $this->getUserOrderFeilvByUserid($user_id, $payapiclass_id);//查询用户费率



                //查询需要提成的用户,计算提成等级,提成费率等



                //查询上级用户的费率



                $superior_feilv = $this->getUserOrderFeilvByUserid($superior_userid, $payapiclass_id);



                $tc_dengji++;



                $sup_list[] = [



                    'tc_userid' => $superior_userid,



                    'feilv' => $user_feilv,



                    'superior_feilv' => $superior_feilv,



                    'commission_feilv' => $user_feilv - $superior_feilv,



                    'tc_dengji' => $tc_dengji,



                ];



                return $this->getCommissionList($superior_userid, $payapiclass_id, $sup_list, $max_commission_level, $tc_dengji);
            }
        }



        return $sup_list;
    }





    //从支付回调中调用此方法时，不能获取到own_type这个属性，所以需要重写，但是账号的所属者还是需要判断

    //先去查找用户通道分类中的费率，没有的话去查找通道中的费率

    final private function getUserOrderFeilvByUserid($userid = 1, $payapiclass_id = 4)
    {
        $user_payapiclass = M('userpayapiclass')->where([

            'payapiclassid' => ['eq', $payapiclass_id],

            'userid' => ['eq', $userid],

        ])->find();



        $payapiclass = M('payapiclass')->where([

            'id' => $payapiclass_id

        ])->find();

        if ($user_payapiclass) {
            $trade_feilv = $user_payapiclass['order_feilv'];
        } elseif ($payapiclass) {
            $trade_feilv = $payapiclass['order_feilv'];
        } else {
            $trade_feilv = '0';
        }

        return $trade_feilv;
    }





    /**

     * 2019-01-29 张杨  生成回调的参数

     * @param $order_info

     * @param string $status

     * @return array

     */



    final private function CreateReturnData($order_info, $status = "00", $error_content = '')
    {
        $other = M('orderother')->where("sysordernumber='" . $order_info['sysordernumber'] . "'")->getField('jsonstr');



        $otherarray = $other ? json_decode($other, true) : [];



        $amount_trade = M('ordermoney')->where("sysordernumber='" . $order_info['sysordernumber'] . "'")->getField('moneytrade');



        $tongdao = M("payapiclass")->where("id=" . $order_info['payapiclassid'])->getField('classbm');



        //生成回调的数据



        $returnData = [



            'userid' => $order_info['userid'],  //用户id



            'amount' => sprintf("%.2f", $order_info['ordermoney']), //交易金额



            'true_amount' => sprintf("%.2f", $order_info['true_ordermoney']),  //实际支付金额



            'amount_trade' => sprintf("%.2f", $amount_trade), //交易手续费



            'orderid' => $order_info['userordernumber'], //订单号



            'sysorderid' => $order_info['sysordernumber'], //系统订单号



            'submittime' => $order_info['datetime'],  //提交交易时间



            'successtime' => $order_info['successtime'],  //交易成功时间



            'tongdao' => $tongdao, //交易通道



            'callbackurl' => $order_info["callbackurl"],  //同步跳转回调地址



            'notifyurl' => $order_info["notifyurl"],   //异步回调地址



            'version' => $order_info['version'],



            'status' => $status,  //状态



            'error_content' => $error_content, //错误说明



            'other' => $otherarray, //原样返回的数据



        ];



        $data = [



            'sysordernumber' => $order_info['sysordernumber'],



            'jsonstr' => json_encode($returnData),



        ];



        M('orderreturncontent')->add($data);



        return $returnData;
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

     * 将订单状态写进pay_orderstatus表中

     * $sysordernumber：系统订单号

     * $status：当前状态

     * 暂时没用

     */



    final private function setOrderStatus($sysordernumber, $status)
    {
        $order_status = M('orderstatus');



        //如果状态=0，表示是在生成订单，则直接插入



        //如果不为0，表示在修改金额操作，则往后追加，用英文逗号连接



        if ($status == 0) {
            $data = [



                'sysordernumber' => $sysordernumber,



                'status' => $status . ',',



                'datetime' => date('Y-m-d H:i:s'),



            ];



            $order_status->add($data);
        } else {



            //往后追加状态值



            $stats_now = $order_status->where("sysordernumber='" . $sysordernumber . "'")->getField('status');



            $stats_now = rtrim($stats_now, ',');



            $status_arr = explode(',', $stats_now);



            $status_new = array_push($status_arr, $status);



            $status_str = implode(',', $status_new);



            $data = [



                'status' => $status_str,



                'datetime' => date('Y-m-d H:i:s'),  //更新修改时间



            ];



            $order_status->where("sysordernumber='" . $sysordernumber . "'")->save($data);
        }
    }





    /**

     * 生成调用通道所需要的参数 ，还需将通道二维码页面模板数据传过去

     * @return bool

     */

    final public function GetParameter($sysordernumber)
    {
        $tj_parameter = M('userparameter')->where(["sys_order_num'"=> $sysordernumber])->getField("tj_parameter");

        return json_decode($tj_parameter, true);
    }



    final private function CreateParameter($parameter)
    {
        $par = $parameter['formatdata'];

        $order_info = $this->getOrderInfo($par['orderid'], $par['userid']);


        $find = M('payapiaccount')->where('id=' . $this->payaccount["id"])->field("memberid,account")->find();


        $account_key_str = M("payapiaccountkeystr")->where("payapi_account_id=" . $this->payaccount["id"])->find();



        $account_key = [



            'publickeystr_path' => $account_key_str["publickeystr_path"],



            'privatekeystr_path' => $account_key_str["privatekeystr_path"],



            'upstreamkeystr_path' => $account_key_str["upstream_keystr_path"],



            'publickeystr' => $account_key_str["publickeystr"],



            'privatekeystr' => $account_key_str["privatekeystr"],



            'upstreamkeystr' => $account_key_str["upstream_keystr"],



            'memberid' => $find["memberid"],



            'account' => $find["account"],



            'md5keystr' => $account_key_str["md5keystr"]



        ];



        $this->payparameter = [



            'userordernumber' => $par['orderid'], //用户订单号



            'shangjiaid' => $this->payaccount["payapishangjiaid"], //商家ID



            'payapiid' => $this->payaccount["payapiid"], //通道ID



            'payapiaccountid' => $this->payaccount["id"], //通道账号ID



            'userid' => $par['userid'], //用户ID



            //'ordermoney' => $par['amount'],  //订单金额



            'ordermoney' => sprintf("%.2f", $order_info['true_ordermoney']),



            //'callbackurl' => $par['callbackurl'],  //同步回调地址



            //'notifyurl' => $par['notifyurl'], //异步回调地址



            'callbackurl' => $this->getReturnUrl($this->obj->callbackurl ? $this->obj->callbackurl : "callbackurl"),  //同步回调地址



            'notifyurl' => $this->getReturnUrl($this->obj->callbackurl ? $this->obj->notifyurl : "notifyurl"), //异步回调地址



            'account_key' => $account_key, //账号密钥



            'sysordernumber' => $order_info['sysordernumber'],  //系统订单号



            'memberid' => $order_info['memberid'],   //商户订单号



            //   'user_parameter' => $parameter['parameter'],  //返回用户信息



        ];

        M('userparameter')->where("sys_order_num='" . $order_info['sysordernumber'] . "'")->setField("tj_parameter", json_encode($this->payparameter));

        return true;
    }





    //2019-4-2 任梦龙：虽然pay_website只有一条数据，但是防止意外，所以不用id=1这种直接方式，而是按照id倒序查询

    final private function getReturnUrl($retunname)
    {
        $find = M("website")->order('id DESC')->field("back_domain,pay_http")->find();



        return ($find["pay_http"] == 2 ? "https" : "http") . "://" . $find["back_domain"] . U("Pay/" . $this->obj->PayName . "/" . $retunname);
    }





    /**

     * 获取单条订单信息

     * $orderid：用户订单号

     * $userid：用户id

     */



    final private function getOrderInfo($orderid, $userid)
    {
        $where = [



            'userordernumber' => $orderid,



            'userid' => $userid,



        ];



        return M('order')->where($where)->find();
    }





    final private function StatusType()
    {
    }





    /**

     * 在修改金额时回调相应的版本回调方法，回馈给用户

     */



    final private function versionCallBack($param)
    {
    }







    /**

     * 获取ip

     * 问题：没有UTFWry.dat文件

     */



//    public function getIp()



//    {



//        $IpLocation = new \Org\Net\IpLocation('UTFWry.dat'); // 实例化类 参数表示IP地址库文件



//



//        $location = $IpLocation->getlocation(); // 获取某个IP地址所在的位置



//



//        return $location['ip'];



//    }





    /**

     * 生成html

     */



    public function setHtml($tjurl, $arraystr, $test = false, $method = "post")
    {
        if ($test) {
            $str = '<form id="Form1" name="Form1" method="' . $method . '" action="' . $tjurl . '">';



            foreach ($arraystr as $key => $val) {
                $str = $str . $key . '：' . $val . '<br /><input type="hidden" name="' . $key . '" value="' . $val . '">';
            }



            $str = $str . '<input type="submit" value="submit">';



            $str = $str . '</form>';
        } else {
            $str = '<form id="Form1" name="Form1" method="' . $method . '" action="' . $tjurl . '">';



            foreach ($arraystr as $key => $val) {
                $str = $str . '<input type="hidden" name="' . $key . '" value="' . $val . '">';
            }



            $str = $str . '</form>';



            $str = $str . '<script>';



            $str = $str . 'document.Form1.submit();';



            $str = $str . '</script>';
        }





        exit($str);
    }



    public function setHtml2($tjurl, $arraystr, $test = false, $method = "post")
    {
        if ($test) {
            $str = '<form id="Form1" name="Form1" method="' . $method . '" action="' . $tjurl . '">';



            foreach ($arraystr as $key => $val) {
                $str = $str . $key . '：' . $val . "<br /><input type='hidden' name='" . $key . "' value='" . $val . "'>";
            }



            $str = $str . '<input type="submit" value="submit">';



            $str = $str . '</form>';
        } else {
            $str = '<form id="Form1" name="Form1" method="' . $method . '" action="' . $tjurl . '">';



            foreach ($arraystr as $key => $val) {
                $str = $str . '<input type="hidden" name="' . $key . '" value="' . $val . '">';
            }



            $str = $str . '</form>';



            $str = $str . '<script>';



            $str = $str . 'document.Form1.submit();';



            $str = $str . '</script>';
        }





        exit($str);
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

     * 获取用户密钥

     * $oderid：订单号

     */



    public function getOrderKey($oderid)
    {
        $secretkey = M('secretkey');



        $key = $secretkey->join('pay_order ON pay_secretkey.userid = pay_order.userid')->where("sysordernumber='" . $oderid . "'")->getField('md5str');



        return $key;
    }





    /**

     * 2019-03-02 张杨，依据系统订单号获取这笔订单对应的通道账号密钥信息

     * @param $orderid

     */



    final protected function getPayapiAccountKey($orderid)
    {
        if (!$orderid) {
            return false;
        }


        $payapiaccountid = M("order")->where(["sysordernumber"=>$orderid])->getField("payapiaccountid");

        $find = M('payapiaccount')->where(['id'=>$payapiaccountid])->field("memberid,account")->find();

        $account_key_path = M('payapiaccountkey')->where(["payapiaccount_id"=>$payapiaccountid])->find();

        $account_key_path = M('payapiaccountkey')->where(["payapiaccount_id" =>$payapiaccountid])->find();

        $account_key_str = M("payapiaccountkeystr")->where(["payapi_account_id"=> $payapiaccountid])->find();

        $account_key = [

            'publickeystr_path' => $account_key_path["publickeystr_path"],

            'privatekeystr_path' => $account_key_path["privatekeystr_path"],

            'upstreamkeystr_path' => $account_key_path["upstream_keystr_path"],

            'publickeystr' => $account_key_str["publickeystr"],

            'privatekeystr' => $account_key_str["privatekeystr"],

            'upstreamkeystr' => $account_key_str["upstream_keystr"],

            'memberid' => $find["memberid"],

            'account' => $find["account"],

            'md5keystr' => $account_key_str["md5keystr"]

        ];



        return $account_key;
    }





    /**

     * 2019-03-03 张杨  返回支付宝的配置信息

     * @param $orderid

     * @return array

     */



    final protected function getAlipayConfig($orderid)
    {
        $account_key = $this->getPayapiAccountKey($orderid);



        $config = array(



            //应用ID,您的APPID。



            'app_id' => $account_key["memberid"],



            //商户私钥



            'merchant_private_key' => $account_key["privatekeystr"],



            //编码格式



            'charset' => "UTF-8",



            //签名方式



            'sign_type' => "RSA2",



            //支付宝网关



            'gatewayUrl' => "https://openapi.alipay.com/gateway.do",



            //支付宝公钥,查看地址：https://openhome.alipay.com/platform/keyManage.htm 对应APPID下的支付宝公钥。



            'alipay_public_key' => $account_key["upstreamkeystr"],



        );



        return $config;
    }





    /**

     * 调用生成同步异步地址，这个还是死数据，明天还得问哈

     * $pay_name:具体通道名称

     * $url_back：回调名称

     */



    public function setUrl($pay_name, $url_back)
    {



//        return "http://www.amnpay.com/pay/".$pay_name.'/'.$url_back;



        return U('Pay/' . $pay_name . '/' . $url_back);
    }







    /**

     * 2019-03-04  张杨，生成二维码

     * @param $url

     * @param $orderid

     * @param $money

     * @param $codeurl

     * @param bool $logocheck

     */

    //2019-03-06汪桂芳修改

    public function Qrcode($url, $sysordernumber, $userordernumber, $payname, $amount)
    {

        //获取用户id

        $user_id = M('order')->where(['sysordernumber'=> $sysordernumber ])->getField('userid');

        //获取通道分类

        $class_id = M('payapi')->where(['en_payname' =>$payname ])->getField('payapiclassid');

        //获取用户扫码设置

        $user_qrcode = $this->getUserQrcodeInfo($user_id, $class_id, 'qrcode');

        if ($user_qrcode != 1) {

            //应用扫码模板

            import("Vendor.phpqrcode.phpqrcode", '', ".php");

            $imgname = C('QRCODE_PAY') . $sysordernumber . ".png";//已经生成的原始二维码图

            \QRcode::png($url, $imgname, "L", 20);

            $this->assign("imgurl", $imgname);

            $this->assign("userordernumber", $userordernumber);

            $this->assign("amount", $amount);



            //获取用户选择的扫码模板

            $qrcodeid = $this->getUserQrcodeInfo($user_id, $class_id, 'qrcode_template_id');

            if ($qrcodeid <= 0) {

                //用户未选择就获取交易通道模板文件名

                $qrcodeid = M('payapi')->where(['en_payname' =>$payname ])->getField('qrcodeid');
            }

            if ($qrcodeid) {

                //存在的话判断该模板id是否合法:即模板id和通道分类id是否对应
                if (M('qrcodetemplate')->where(['id' => $qrcodeid ,'payapiclass_id' =>$class_id])->find()) {
                    $file_name = M('qrcodetemplate')->where('id=' . $qrcodeid)->getField('template_name');
                } else {

                    //出现这种情况可能的原因是:通道所选的扫码模板后来重新改了一个分类,通道没有去重新选模板

                    exit('此通道没有可用的扫码模板');
                }
            } else {

                //用户和通道都未选择的话获取该通道分类默认应用的模板

                $file_name = M('qrcodetemplate')->where(['payapiclass_id' =>$class_id, ' default'=>1])->getField('template_name');


                if (!$file_name) {

                    //通道分类没有默认的模板就随机选取一条

                    $temp = M('qrcodetemplate')->where(['payapiclass_id' => $class_id])->find();


                    $file_name = $temp['template_name'];
                }
            }

            if (!$file_name) {
                exit('此通道没有可用的扫码模板');
            }



            //订单查询路径

            $queryorderurl = U('queryOrder');

            $this->assign("queryorderurl", $queryorderurl);

            $this->assign("sysordernumber", $sysordernumber);

            $this->display('QrcodeTemplate/' . $file_name);
        } else {

            //直接返回二维码地址,需补全接下来的操作
        }
    }



    //添加:获取用户的信息

    public function getUserQrcodeInfo($user_id, $class_id, $field)
    {
        $where = [

            'userid' => $user_id,

            'payapiclassid' => $class_id,

        ];

        return M('userpayapiclass')->where($where)->getField($field);
    }



    /**

     * 2019-03-05汪桂芳添加

     * 获取生成二维码的地址

     */

    public function qrcodeUrl($sysordernumber, $result, $payName)
    {
        $short = $this->getShortInfo($sysordernumber);

        if (!$short) {

            //生成短域名

            $short_url = $this->getShortUrl();

            $data = [

                'payname' => $payName,

                'short_url' => $short_url,

                'long_url' => $result,

                'sysordernumber' => $sysordernumber,

            ];

            $this->addShortInfo($data);
        } else {
            $short_url = $short['short_url'];
        }

        //读取支付域名

        $website = M('website')->where('id=1')->find();

        if ($website['pay_http'] == 1) {
            $pay_domain = 'http://' . $website['pay_domain'];
        } else {
            $pay_domain = 'https://' . $website['pay_domain'];
        }

        $url = $pay_domain . U('Pay/Pay/qrcodeJump', 'short=' . $short_url);

        return $url;
    }



    //添加短域名记录

    public function addShortInfo($data)
    {
        return M('ordershorturl')->add($data);
    }



    //获取是否有短域名

    public function getShortInfo($sysordernumber)
    {
        return M('ordershorturl')->where(['sysordernumber' =>$sysordernumber ])->find();
    }



    //生成短域名

    public function getShortUrl()
    {
        $url = randpw(C('SHORT_LENGTH'), 'ALL');

        $temp = M('ordershorturl')->where(['short_url' =>$url])->count();

        if ($temp > 0) {
            return $this->getShortUrl();
        }

        return $url;
    }





    /**

     * 扫码跳转处理程序

     */

    public function qrcodeJump()
    {
        $short = I('get.short');

        $long = $this->getLongUrl($short);

        header('location:' . $long);
    }



    //获取是否有短域名

    public function getLongUrl($short)
    {
        return M('ordershorturl')->where(['shor_url'=>$short])->getField('long_url');
    }





    /**

     * 2019-03-05汪桂芳添加

     * 订单验证和补单调用的方法

     */



    public function orderVerify($data)
    {
        $order_info = M('order')->where(["sysordernumber'" =>$data['sysordernumber'] ])->find();

        //查询通道编码

        $pay_name = M('payapi')->where(['id'=> $order_info['payapiid']])->getField('en_payname');

        $key = C('ORDER_VERIFY_MD5');

        $sign_arr = [

            'sysordernumber' => $order_info['sysordernumber'],

            'pay_name' => $pay_name,

            'true_ordermoney' => $order_info['true_ordermoney'],

            'verify' => $data['verify'], //验证

            'supplement' => $data['supplement']  //补单

        ];

        $sign = $this->getSign($sign_arr, $key);

        if ($sign == $data['sign']) {
            $res = $this->editMoney($order_info['sysordernumber'], $pay_name, 0, $order_info['true_ordermoney'], '', $data['verify'], $data['supplement']);
        } else {
            $res['status'] = 'no';

            $res['msg'] = '签名验证失败';
        }

        return $res;
    }





    //封装返回方法

    protected function orderVerifyReturn($verify, $supplement, $msg)
    {
        if ($verify || $supplement) {
            $res['status'] = 'ok';

            $res['msg'] = $msg;

            return $res;
        } else {
            return false;
        }
    }
}
