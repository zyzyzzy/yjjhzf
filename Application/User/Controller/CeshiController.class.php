<?php
/**
 * Created by PhpStorm.
 * User: 任梦龙
 * Date: 2019/2/12/012
 * Time: 下午 5:32
 * 测试插入数据
 */

namespace User\Controller;

use Think\Controller;

class CeshiController extends \Think\Controller
{
    public $payaccount = [];

    //测试插入数据
    public function ceshi()
    {
        echo date('Y-m-d H:i:s');
        set_time_limit(0);
        ini_set('max_execution_time', '0');//mysql执行时间
        $userIds = M('user')->getField('id', true);  //用户id
        $payapiclassIds = M('payapiclass')->getField('id', true);  //通道分类id
        $payapiIds = M('payapi')->getField('id', true);  //通道id
        $payapiaccountIds = M('payapiaccount')->getField('id', true);  //账号id
        $orderIds = M('order')->getField('id', true);  //订单id
        $money_change_type = C('MONEY_CHANGE_TYPE');
        $sql = "INSERT INTO `pay_moneychange` (`userid`,`oldmoney`,`changemoney`,`nowmoney`,`datetime`,`transid`,`payapiid`,`accountid`,`changetype`,`tcuserid`,`tcdengji`,`orderid`,`remarks`) VALUES";
        for ($i = 0; $i <= 9000; $i++) {
            $sql .= "(" . $userIds[array_rand($userIds)] . ", " . '15' . ", " . '5' . ", " . '15' . ", " . '"' . date('Y-m-d H:i:s') . '"' . ", " . $orderIds[array_rand($orderIds)] . ", " . $payapiIds[array_rand($payapiIds)] . ", " . $payapiaccountIds[array_rand($payapiaccountIds)] . ", " . "'man_increase' , " . $userIds[array_rand($userIds)] . ", 1 , " . $orderIds[array_rand($orderIds)] . ", '线上测试增加金额'" . "),";
        }
        $sql .= "(" . $userIds[array_rand($userIds)] . ", " . '15' . ", " . '5' . ", " . '15' . ", " . '"' . date('Y-m-d H:i:s') . '"' . ", " . $orderIds[array_rand($orderIds)] . ", " . $payapiIds[array_rand($payapiIds)] . ", " . $payapiaccountIds[array_rand($payapiaccountIds)] . ", " . "'man_increase' , " . $userIds[array_rand($userIds)] . ", 1 , " . $orderIds[array_rand($orderIds)] . ", '线上测试增加金额'" . ")";
//        echo $sql;die;
        $resule = M()->query($sql);

//        for($i=0;$i<10000;$i++){
//            $data = [
//                'userid' => 1,
//                'oldmoney' => 10,
//                'changemoney' => 5,
//                'nowmoney' => 15,
//                'datetime' => date('Y-m-d H:i:s'),
//                'transid' => 12,
//                'payapiid' => 3,
//                'accountid' => 6,
//                'changetype' => 'man_increase',
////                'tcuserid' => 1,
////                'tcdengji' => 1,
////                'orderid' => 1,
//                'remarks' => '线上测试增加金额',
//            ];
//            M('moneychange')->add($data);
//        }
        echo '<br/>';
        echo date('Y-m-d H:i:s');
    }

    public function index()
    {
        set_time_limit(0);
        ini_set('max_execution_time', '0');//mysql执行时间
        @mysql_pconnect("localhost", "root", "root") or die('connect failed');
        @mysql_select_db("amn") or die('select db failed');
//这一步很重要  取消mysql的自动提交
//        mysql_query('SET AUTOCOMMIT=0;');
//
//        mysql_query('set names utf8');
        $begin = time();
        $count = 1;
        //mysql_query("insert into pay_moneychange values(92953,1,'10.0000','5.0000','15.0000','2019-02-13 10:10:04',12,3,6,'man_increase','','','','线上测试增加金额')");
        //mysql_query("insert into pay_version values(2,'1.0.1','Defaultversion')");
        for ($i = 160; $i <= 1000000; $i++) {
//            mysql_query("insert into user2 values($i,'name')");
            //插入订单表
            //mysql_query("insert into pay_order values($i,'123456789','54815512337126798306976888727598',5,3,0,7,10001,1,'https://www.baidu.com','https://www.baidu.com','1000',1,'".date('Y-m-d H:i:s',time())."','','',0,'192.168.0.17',1,'')");
            mysql_query("insert into pay_order values($i,'123456789','54815512337126798306976888727598',5,3,0,7,10001,1)");
            //插入20W提交一次
            if ($i % 200000 == '0') {
                $count++;
//                mysql_query("insert into log values($i,$count)");
                mysql_query("commit");
            }
        }

        $end = time();
        echo "用时 " . ($end - $begin) . " 秒 <hr/>";
    }

    public function test()
    {
        echo date('Y-m-d H:i:s');
        set_time_limit(0);
        ini_set('max_execution_time', '0');//mysql执行时间
        //连接数据库
//        $host = '127.0.0.1';
        $host = '192.168.0.119';
        $user = 'root';
        $pwd = 'root';
        $db_name = 'www_amnpay_com';
//        $db_name = 'amnpay';
        $conn = mysqli_connect($host, $user, $pwd, $db_name);
        if (!$conn) {
            echo mysqli_connect_error();
            exit;
        }
        $userIds = M('user')->getField('id', true);  //用户id
        $payapiclassIds = M('payapiclass')->getField('id', true);  //通道分类id
        $payapiIds = M('payapi')->getField('id', true);  //通道id
        $payapiaccountIds = M('payapiaccount')->getField('id', true);  //账号id
        $orderIds = M('order')->getField('id', true);  //订单id
//        $money_change_type = C('MONEY_CHANGE_TYPE');

        $sql = "INSERT INTO `pay_moneychange` (`userid`,`oldmoney`,`changemoney`,`nowmoney`,`datetime`,`transid`,`payapiid`,`accountid`,`changetype`,`tcuserid`,`tcdengji`,`orderid`,`remarks`) VALUES";
        for ($i = 0; $i <= 10000; $i++) {
            $money_change_type = rand(0, 7);
            $sql .= "(" . $userIds[array_rand($userIds)] . ", " . '15' . ", " . '5' . ", " . '15' . ", " . '"' . date('Y-m-d H:i:s') . '"' . ", " . $orderIds[array_rand($orderIds)] . ", " . $payapiIds[array_rand($payapiIds)] . ", " . $payapiaccountIds[array_rand($payapiaccountIds)] . ", " . $money_change_type . ", " . $userIds[array_rand($userIds)] . ", 1 , " . $orderIds[array_rand($orderIds)] . ", '线上测试增加金额'" . "),";

        }
        $sql .= "(" . $userIds[array_rand($userIds)] . ", " . '15' . ", " . '5' . ", " . '15' . ", " . '"' . date('Y-m-d H:i:s') . '"' . ", " . $orderIds[array_rand($orderIds)] . ", " . $payapiIds[array_rand($payapiIds)] . ", " . $payapiaccountIds[array_rand($payapiaccountIds)] . ", " . $money_change_type . ", " . $userIds[array_rand($userIds)] . ", 1 , " . $orderIds[array_rand($orderIds)] . ", '线上测试增加金额'" . ")";
        $resule = $conn->query($sql);

        echo '<br/>';
        echo date('Y-m-d H:i:s');
    }

    /**
     * 2019-2-14 任梦龙：插入交易数据：有问题
     */
    public function jiaoyi()
    {
        echo date('Y-m-d H:i:s');
        echo '<br/>';
        set_time_limit(0);
        ini_set('max_execution_time', '0');//mysql执行时间
        //连接数据库
//        $host = '127.0.0.1';
        $host = '192.168.0.119';
        $user = 'root';
        $pwd = 'root';
        $db_name = 'www_amnpay_com';
        $conn = mysqli_connect($host, $user, $pwd, $db_name);
        if (!$conn) {
            echo mysqli_connect_error();
            exit;
        }
        //拼接sql语句
        for ($i = 0; $i < 1; $i++) {
            $sysordernumber = $this->Createsysordernumber(32);  //系统订单号
            //如果订单号重复了则返回
            if (!$sysordernumber) {
                continue;
            }
            $order_money = rand(9999, 99999); //订单金额
            $par['amount'] = $order_money;
            $userordernumber = rand(10000, 99990); //用户订单号
            //字段数据
            $order = D("order");
            $data = [];
            $data["userordernumber"] = $userordernumber; //用户订单号
            $data["sysordernumber"] = $sysordernumber;  //系统订单号
            $data["shangjiaid"] = 4;  //商家ID
            $data["payapiid"] = 9; //通道ID
            $data["payapiaccountid"] = 6; //通道账号ID
            $data["memberid"] = 10001;  //商户号
            $data["userid"] = 1;  //用户ID
            $data["callbackurl"] = 'http://www.amnpay.com';  //同步回调地址
            $data["notifyurl"] = 'https://www.baidu.com'; //异步回调地址
            $data["ordermoney"] = $order_money; //订单金额
            $data["datetime"] = date('Y-m-d H:i:s'); //创建时间
            $data["version"] = '1.0.0';   //对应的提交接口版本号
            $data["payapiclassid"] = 26; //通道类别ID
            $data["userip"] = '127.0.0.1'; //用户IP

            $par['userid'] = 1;
            $this->payaccount["id"] = 8;

            //生成订单
//            $order->add($data);

            $sql_order = "INSERT INTO `pay_order` (`userordernumber`,`sysordernumber`,`shangjiaid`,`payapiid`,`payapiclassid`,`payapiaccountid`,`memberid`,`userid`,`ordermoney`,`datetime`) VALUES ";
            $sql_order .= "(" . "'{$userordernumber}'" . ", " . "'$sysordernumber'" . ", 4, 9, 26, 6, 10001, 1, " . $order_money . ", " . "'" . date('Y-m-d H:i:s') . "'" . ")";
            $conn->query($sql_order);  //返回布尔值

            //生成订单日志
//            $this->orderLogAdd(1, $sysordernumber, '未支付');
            $sql_orderlog = "INSERT INTO `pay_orderlog` (`sys_order_num`,`user_id`,`content_log`,`at_time`) VALUES ";
            $sql_orderlog .= "(" . "'$sysordernumber'" . ", 1, '未支付', " . "'" . date('Y-m-d H:i:s') . "'" . ")";
            $conn->query($sql_orderlog);  //返回布尔值

            //由到账方案id得到对应的冻结方案id
            $money_class_id = M('payapiaccount')->where('id = ' . $this->payaccount["id"])->getField('moneytypeclassid');
            $money_type_list = M('moneytype')->where('moneytypeclassid = ' . $money_class_id)->select();

            //获取成本费率和交易费率
            $feilv = $this->getFeilv($this->payaccount["id"], $par['userid']);
            $trade_money = $feilv['trade_feilv'] * $par['amount'];  //交易手续费

            //将金额信息写入ordermoney表，得到新增id
            $ordermoney_id = $this->setOrderMoney($conn, $feilv, $sysordernumber, $par['amount'], $money_class_id);
            $ordermoney_info = M('ordermoney')->order('id DESC')->limit(1)->find();
            $ordermoney_id = $ordermoney_info['id'];

            //写入冻结金额明细表
            foreach ($money_type_list as $key => $val) {
                $freeze_dzbl = M('moneytype')->where('id = ' . $val['id'])->getField('dzbl');
                $freeze_mooney = ($par['amount'] - $trade_money) * $freeze_dzbl;
                $expect_time = strtotime($val['dzsj_time']) + (intval($val['dzsj_day']) * 24 * 60 * 60);  //计算预计时间
                $expect_time = date('Y-m-d H:i:s', $expect_time);
                $data_freeze = [
                    'ordermoney_id' => $ordermoney_id,  //订单金额表id
                    'moneytype_id' => $val['id'],  //金额方案表id
                    'user_id' => $par['userid'],  //用户id
                    'freeze_money' => $freeze_mooney,  //冻结的金额
                    'expect_time' => $expect_time,  //预计到达时间
                    'date_time' => date('Y-m-d H:i:s'),  //记录添加时间
                ];
                $sql_orderfreezemoney = "INSERT INTO `pay_orderfreezemoney` (`ordermoney_id`,`moneytype_id`,`user_id`,`freeze_money`,`date_time`,`unfreeze`,`freeze_type`,`unfreeze_type`,`order_status`) VALUES ";
                $sql_orderfreezemoney .= "(" . $ordermoney_id . ", " . $val['id'] . ", " . $par['userid'] . ", " . $freeze_mooney . ", " . "'" . date('Y-m-d H:i:s') . "'" . ", 0, 0, 0, 0)";
                $conn->query($sql_orderfreezemoney);  //返回布尔值

//                M('orderfreezemoney')->add($data_freeze);
            }

            //将用户提交的参数存入数据库，信息以json格式存储
            $this->setUserPar($conn, $par['userid'], $sysordernumber, $data);
        }

        echo date('Y-m-d H:i:s');

    }

    /**
     * 2019-2-14 任梦龙：测试交易日志数据
     */
    public function orderLog()
    {
        echo date('Y-m-d H:i:s');
        set_time_limit(0);
        ini_set('max_execution_time', '0');//mysql执行时间
        $userIds = M('user')->getField('id', true);  //用户id
        $userIds1 = $userIds[array_rand($userIds)];

        $sysordernumber2 = rand(10000000, 60000000);
        $sql_orderlog = "INSERT INTO `pay_orderlog` (`sys_order_num`,`user_id`,`content_log`,`at_time`) VALUES";
        for ($i = 0; $i <= 9000; $i++) {
            $sysordernumber1 = rand(60000001, 99999999);
            $sql_orderlog .= "(" . $sysordernumber1 . ", " . $userIds[array_rand($userIds)] . ", " . "'未支付'" . ", " . "'" . date('Y-m-d H:i:s') . "'" . "),";
        }
        $userIds2 = $userIds[array_rand($userIds)];
        $sql_orderlog .= "(" . $sysordernumber2 . ", " . $userIds[array_rand($userIds)] . ", " . "'未支付'" . ", " . "'" . date('Y-m-d H:i:s') . "'" . ")";
        $resule = M()->query($sql_orderlog);
    }

    function Createsysordernumber($leng = 32, $n = 1)
    {
        $sysordernumber = randpw($leng, 'ALL');  //2019-01-27 大写字母和小写字母和数字
        //2019-01-27如果存在相同的系统订单号 重新生成一次，每冷重新生成系统订单号时间隔2秒，如果连续五次生成都重复就报错
        $num = M("order")->lock(true)->where("sysordernumber='" . $sysordernumber . "'")->count();
        if ($num >= 1) {
            if ($n < 5) {
                sleep(1);
                $n = $n + 1;
                return Createsysordernumber($leng, $n);
            } else {
                return false;
            }
        } else {
            return $sysordernumber;
        }

    }

    function orderLogAdd($user_id, $sys_order_num, $error_content, $return_msg = '')
    {
        $order_log = M('orderlog');
        $data = [
            'user_id' => $user_id,
            'sys_order_num' => $sys_order_num,
            'content_log' => $error_content,
            'at_time' => date('Y-m-d H:i:s'),
            'return_msg' => $return_msg,
        ];
        $order_log->add($data);

    }

    function getFeilv($account_id, $user_id, $order_money)
    {
        $cost_feilv = M('payapiaccount')->lock(true)->where('id=' . $account_id)->getField('cbfeilv');  //获取成本费率
        //获取用户交易费率和账号交易费率，如果用户没有设置，则用账号的
        $user_feilv = M('feilv')->lock(true)->where('payapiaccountid=' . $account_id . ' and userid=' . $user_id)->getField('feilv');
        $account_feilv = M('payapiaccount')->lock(true)->where('id=' . $account_id)->getField('feilv');
        $trade_feilv = $user_feilv ? $user_feilv : $account_feilv;  //交易费率
        $data = [
            'cost_feilv' => $cost_feilv,
            'trade_feilv' => $trade_feilv
        ];
        return $data;
    }

    function setOrderMoney($conn, $feilv, $sysordernumber, $order_money, $money_class_id)
    {
        $cost_money = $feilv['cost_feilv'] * $order_money;  //成本金额
        $trade_money = $feilv['trade_feilv'] * $order_money;  //交易手续费
        //冻结金额 = （订单金额 - 交易手续费）* 冻结比例
        //实际到账金额=（订单金额 - 交易手续费）* 立即到账比例 （立即到账比例 + 冻结比例 = 1）
        //通过到账方案id查询出冻结金额方案
        $freeze_dzbl = M('moneytype')->lock(true)->where('moneytypeclassid = ' . $money_class_id)->sum('dzbl');  //冻结比例
        $freeze_money = ($order_money - $trade_money) * $freeze_dzbl;  //总的冻结金额
        $actual_money = ($order_money - $trade_money) * (1 - $freeze_dzbl);  //实际到账金额
        //写入ordermoney表
        $order_data = [
            'sysordernumber' => $sysordernumber,  //系统订单号
            'ordermoney' => $order_money,  //订单金额
            'traderate' => $feilv['trade_feilv'],  //交易费率
            'moneytrade' => $trade_money,  //交易手续费
            'costrate' => $feilv['cost_feilv'],  //成本费率
            'moneycost' => $cost_money,  //成本费
            'money' => $actual_money,   //实际到账金额
            'freezemoney' => $freeze_money  //冻结金额
        ];
        $sql_ordermoney = "INSERT INTO `pay_ordermoney` (`sysordernumber`,`ordermoney`,`traderate`,`moneytrade`,`costrate`,`moneycost`,`money`,`freezemoney`) VALUES ";
        $sql_ordermoney .= "(" . "'$sysordernumber'" . ", " . $order_money . ", " . $feilv['trade_feilv'] . ", " . $trade_money . ", " . $feilv['cost_feilv'] . ", " . $cost_money . ", " . $actual_money . ", " . $freeze_money . ")";
        $conn->query($sql_ordermoney);  //返回布尔值
//        return M('ordermoney')->add($order_data);
    }

    function setUserPar($conn, $user_id, $sysordernumber, $parameter)
    {
        $user_parameter = [
            'user_id' => $user_id,
            'sys_order_num' => $sysordernumber,
            'parameter' => json_encode($parameter)  //用户提交的参数
        ];
        $parameters = json_encode($parameter);
        $sql_userparameter = "INSERT INTO `pay_userparameter` (`user_id`,`sys_order_num`,`parameter`) VALUES ";
        $sql_userparameter .= "(1, " . "'$sysordernumber'" . ", " . "'$parameters'" . ")";
        $conn->query($sql_userparameter);  //返回布尔值

//        M('userparameter')->add($user_parameter);
    }

    public function jiaoyi_two()
    {
//        echo date('Y-m-d H:i:s');
//        echo '<br/>';
        set_time_limit(0);
        ini_set('max_execution_time', '0');//mysql执行时间
        $sql_order = "INSERT INTO `pay_order` (`userordernumber`,`sysordernumber`,`shangjiaid`,`payapiid`,`payapiclassid`,`payapiaccountid`,`memberid`,`userid`,`ordermoney`,`datetime`) VALUES";
        $sql_orderlog = "INSERT INTO `pay_orderlog` (`sys_order_num`,`user_id`,`content_log`,`at_time`) VALUES";
        for ($i = 0; $i < 1; $i++) {
            $sysordernumber1 = $this->Createsysordernumber(32);  //系统订单号
            $order_money = rand(10, 9999); //订单金额
            $par['amount'] = $order_money;
            //字段数据
            $order = D("order");
            $data = [];
            $data["userordernumber"] = rand(10000, 99990); //用户订单号
            $data["sysordernumber"] = $sysordernumber1;  //系统订单号
            $data["shangjiaid"] = 4;  //商家ID
            $data["payapiid"] = 9; //通道ID
            $data["payapiaccountid"] = 6; //通道账号ID
            $data["memberid"] = 10001;  //商户号
            $data["userid"] = 1;  //用户ID
            $data["callbackurl"] = 'http://www.amnpay.com';  //同步回调地址
            $data["notifyurl"] = 'https://www.baidu.com'; //异步回调地址
            $data["ordermoney"] = $order_money; //订单金额
            $data["datetime"] = date('Y-m-d H:i:s'); //创建时间
            $data["version"] = '1.0.0';   //对应的提交接口版本号
            $data["payapiclassid"] = 26; //通道类别ID
            $data["userip"] = '127.0.0.1'; //用户IP

            $par['userid'] = 1;
            $this->payaccount["id"] = 8;
            //生成订单
//            $order->add($data);
            $sql_order .= "(" . rand(10000, 99990) . ", " . "'{$sysordernumber1}'" . ", 4, 9, 26, 6, 10001, 1" . ", " . $order_money . ", " . '"' . date('Y-m-d H:i:s') . '"' . "),";

            //生成订单日志
//            $this->orderLogAdd(1, $sysordernumber, '未支付');
            $sql_orderlog .= "(" . "'{$sysordernumber1}'" . ", 1, '未支付', " . '"' . date('Y-m-d H:i:s') . '"' . "),";

            //由到账方案id得到对应的冻结方案id
            $money_class_id = M('payapiaccount')->where('id = ' . $this->payaccount["id"])->getField('moneytypeclassid');
            $money_type_list = M('moneytype')->where('moneytypeclassid = ' . $money_class_id)->select();

            //获取成本费率和交易费率
            $feilv = $this->getFeilv($this->payaccount["id"], $par['userid']);
            $trade_money = $feilv['trade_feilv'] * $par['amount'];  //交易手续费

            //将金额信息写入ordermoney表
//            $ordermoney_id = $this->setOrderMoney($feilv, $sysordernumber, $par['amount'], $money_class_id);

            //写入冻结金额明细表
//            foreach ($money_type_list as $key => $val) {
//                $freeze_dzbl = M('moneytype')->where('id = ' . $val['id'])->getField('dzbl');
//                $freeze_mooney = ($par['amount'] - $trade_money) * $freeze_dzbl;
//                $expect_time = strtotime($val['dzsj_time']) + (intval($val['dzsj_day']) * 24 * 60 * 60);  //计算预计时间
//                $expect_time = date('Y-m-d H:i:s', $expect_time);
//                $data_freeze = [
//                    'ordermoney_id' => $ordermoney_id,  //订单金额表id
//                    'moneytype_id' => $val['id'],  //金额方案表id
//                    'user_id' => $par['userid'],  //用户id
//                    'freeze_money' => $freeze_mooney,  //冻结的金额
//                    'expect_time' => $expect_time,  //预计到达时间
//                    'date_time' => date('Y-m-d H:i:s'),  //记录添加时间
//                ];
//                M('orderfreezemoney')->add($data_freeze);
//            }
            //将用户提交的参数存入数据库，信息以json格式存储
            // $this->setUserPar($par['userid'],$sysordernumber,$parameter['parameter']);
            //2019-2-14 任梦龙：修改字段设计，将order_id修改为user_id：用户id
//            $this->setUserPar($par['userid'], $sysordernumber, $data);
        }
        $sysordernumber2 = $this->Createsysordernumber(32);  //系统订单号
        $sql_order .= "(" . rand(10000, 99990) . ", " . "'{$sysordernumber2}'" . ", 4, 9, 26, 6, 10001, 1" . ", " . $order_money . ", " . '"' . date('Y-m-d H:i:s') . '"' . ")";
        $sql_orderlog .= "(" . "'{$sysordernumber2}'" . ", 1, '未支付', " . '"' . date('Y-m-d H:i:s') . '"' . ")";
//        echo $sql_orderlog;die;
        @M()->query($sql_order);  //生成订单
        @M()->query($sql_orderlog);  //生成订单日志


        echo date('Y-m-d H:i:s');

    }

    public function wode()
    {
        echo 22;
        die;
    }

    public function jiaoyi3()
    {
        echo date('Y-m-d H:i:s');
        echo '<br/>';
        set_time_limit(0);
        ini_set('max_execution_time', '0');//mysql执行时间
        //连接数据库
        $host = '127.0.0.1';
        $user = 'root';
        $pwd = 'root';
        $db_name = 'amn';
        $host = '127.0.0.1';
        $conn = mysqli_connect($host, $user, $pwd, $db_name);
        if (!$conn) {
            echo mysqli_connect_error();
            exit;
        }
        //拼接sql语句
        for ($i = 0; $i < 100; $i++) {
            $sysordernumber = $this->Createsysordernumber(32);  //系统订单号
            //如果订单号重复了则返回
            if (!$sysordernumber) {
                continue;
            }
            $order_money = rand(10, 9999); //订单金额
            $par['amount'] = $order_money;
            //字段数据
            $order = D("order");
            $data = [];
            $data["userordernumber"] = rand(10000, 99990); //用户订单号
            $data["sysordernumber"] = $sysordernumber;  //系统订单号
            $data["shangjiaid"] = 4;  //商家ID
            $data["payapiid"] = 9; //通道ID
            $data["payapiaccountid"] = 6; //通道账号ID
            $data["memberid"] = 10001;  //商户号
            $data["userid"] = 1;  //用户ID
            $data["callbackurl"] = 'http://www.amnpay.com';  //同步回调地址
            $data["notifyurl"] = 'https://www.baidu.com'; //异步回调地址
            $data["ordermoney"] = $order_money; //订单金额
            $data["datetime"] = date('Y-m-d H:i:s'); //创建时间
            $data["version"] = '1.0.0';   //对应的提交接口版本号
            $data["payapiclassid"] = 26; //通道类别ID
            $data["userip"] = '127.0.0.1'; //用户IP

            $par['userid'] = 1;
            $this->payaccount["id"] = 8;

            //生成订单
//            $order->add($data);
            $userordernumber = rand(10000, 99990); //用户订单号
            $sql_order = "INSERT INTO `pay_order` (`userordernumber`,`sysordernumber`,`shangjiaid`,`payapiid`,`payapiclassid`,`payapiaccountid`,`memberid`,`userid`,`ordermoney`,`datetime`) VALUES ";
            $sql_order .= "(" . "'{$userordernumber}'" . ", " . "'$sysordernumber'" . ", 4, 9, 26, 6, 10001, 1, " . $order_money . ", " . "'" . date('Y-m-d H:i:s') . "'" . ")";
            $conn->query($sql_order);  //返回布尔值

            //生成订单日志
//            $this->orderLogAdd(1, $sysordernumber, '未支付');
            $sql_orderlog = "INSERT INTO `pay_orderlog` (`sys_order_num`,`user_id`,`content_log`,`at_time`) VALUES ";
            $sql_orderlog .= "(" . "'$sysordernumber'" . ", 1, '未支付', " . "'" . date('Y-m-d H:i:s') . "'" . ")";
            $conn->query($sql_orderlog);  //返回布尔值

            //由到账方案id得到对应的冻结方案id
            $money_class_id = M('payapiaccount')->where('id = ' . $this->payaccount["id"])->getField('moneytypeclassid');
            $money_type_list = M('moneytype')->where('moneytypeclassid = ' . $money_class_id)->select();

            //获取成本费率和交易费率
            $feilv = $this->getFeilv($this->payaccount["id"], $par['userid']);
            $trade_money = $feilv['trade_feilv'] * $par['amount'];  //交易手续费

            //将金额信息写入ordermoney表，得到新增id
            $ordermoney_id = $this->setOrderMoney($conn, $feilv, $sysordernumber, $par['amount'], $money_class_id);
            $ordermoney_info = M('ordermoney')->order('id DESC')->limit(1)->find();
            $ordermoney_id = $ordermoney_info['id'];

            //写入冻结金额明细表
            foreach ($money_type_list as $key => $val) {
                $freeze_dzbl = M('moneytype')->where('id = ' . $val['id'])->getField('dzbl');
                $freeze_mooney = ($par['amount'] - $trade_money) * $freeze_dzbl;
                $expect_time = strtotime($val['dzsj_time']) + (intval($val['dzsj_day']) * 24 * 60 * 60);  //计算预计时间
                $expect_time = date('Y-m-d H:i:s', $expect_time);
                $data_freeze = [
                    'ordermoney_id' => $ordermoney_id,  //订单金额表id
                    'moneytype_id' => $val['id'],  //金额方案表id
                    'user_id' => $par['userid'],  //用户id
                    'freeze_money' => $freeze_mooney,  //冻结的金额
                    'expect_time' => $expect_time,  //预计到达时间
                    'date_time' => date('Y-m-d H:i:s'),  //记录添加时间
                ];
                $sql_orderfreezemoney = "INSERT INTO `pay_orderfreezemoney` (`ordermoney_id`,`moneytype_id`,`user_id`,`freeze_money`,`date_time`,`unfreeze`,`freeze_type`,`unfreeze_type`,`order_status`) VALUES ";
                $sql_orderfreezemoney .= "(" . $ordermoney_id . ", " . $val['id'] . ", " . $par['userid'] . ", " . $freeze_mooney . ", " . "'" . date('Y-m-d H:i:s') . "'" . ", 0, 0, 0, 0)";
                $conn->query($sql_orderfreezemoney);  //返回布尔值

//                M('orderfreezemoney')->add($data_freeze);
            }

            //将用户提交的参数存入数据库，信息以json格式存储
            $this->setUserPar($conn, $par['userid'], $sysordernumber, $data);
        }

        echo date('Y-m-d H:i:s');

    }
}
