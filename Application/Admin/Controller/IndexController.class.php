<?php



//2019-3-21 任梦龙：修改

namespace Admin\Controller;

use Admin\Model\OrdercommissionModel;

use Admin\Model\OrderModel;

use Admin\Model\PayapiclassModel;

use Admin\Model\SettleModel;

use Admin\Model\StatisticModel;

use Admin\Model\GooglecodeModel;

use Admin\Model\SuccessrateModel;

use User\Model\UserModel;

use Admin\Model\AdminuserModel;

use Admin\Model\AdminauthgroupaccessModel;

class IndexController extends CommonController
{
    public function __construct()
    {
        parent::__construct();
    }



    public function index()
    {

        //2019-4-17 rml :如果有菜单文件，读取，否则判断是否有json数据，否则读取菜单数据，如果没有数据，则为空

        $admin_path = $this->getAdminMenuPath(session('admin_info.id'));

        $admin_menu = AdminuserModel::getAdminiInfo(['id' => session('admin_info.id')]);

        if (file_exists($admin_path)) {
            $file_json = file_get_contents($admin_path);

            $menujson = json_decode($file_json, true);
        } elseif ($admin_menu['menu_json']) {
            file_put_contents($admin_path, $admin_menu['menu_json']);

            $menujson = json_decode($admin_menu['menu_json'], true);
        } else {
            $menujson = AdminauthgroupaccessModel::getUserRules(session('admin_info.id'));

            if ($menujson) {
                file_put_contents($admin_path, json_encode($menujson));

                AdminuserModel::editPassword(session('admin_info.id'), ['menu_json' => json_encode($menujson), 'menu_path' => $admin_path]);
            } else {
                $menujson = '';
            }
        }


        $this->assign("menujson", $menujson);


        $google_code = $this->isExistGoogle();

        $this->assign('google_code', $google_code);

        $this->display();
    }



    public function welcome()
    {
        $y = date('Y');

        $m = date('m');

        $d = date('d');

        //总利润

        $sum_profit = StatisticModel::getSumProfit();

        //用户总数

        $sum_user = UserModel::getSumUser();

        //充值总笔数

        $sum_count = SuccessrateModel::getSumCount();

        //充值总金额

        $sum_amount = StatisticModel::getSumAmount();

        //总提款金额

        $sum_tkamount = StatisticModel::getSumAmount(2);

        //交易总笔数

        $sum_SuccessCount = SuccessrateModel::getSumSuccessCount();

        //总成功率

        $sum_rate = sprintf('%.2f', $sum_SuccessCount / $sum_count) * 100;





        //今日充值总金额

        $today_amount = OrderModel::getTodaySumMoney();

        //今日充值总笔数

        $today_count = OrderModel::getTodayCounts();

        //今日充值手续费

        $today_moneytrade = OrderModel::getTodaySumMoneytrade();





        //今日提款总金额

        $today_settle_money = SettleModel::getTodaySumMoney();

        //今日提款总手续费

        $today_tc_moneytrade = SettleModel::getTodaySumMoneytrade();

        //今日提款总笔数

        $today_settle_count = SettleModel::getTodayCounts();



        //今日提成总金额

        $today_tc_money = OrdercommissionModel::getTodaySumMoney();

        //今日提成总笔数

        $today_tc_count = OrdercommissionModel::getTodayCounts();





        //当月充值总金额

        $months_amount = OrderModel::getMonthsSumMoney();

        //当月总充值总手续费

        $months_moneytrade = OrderModel::getMonthsSumMoneytrade();

        //当月充值总笔数

        $months_count = OrderModel::getMonthsCounts();





        //当月结算总金额

        $months_settle_money = SettleModel::getMonthsSumMoney();

        //当月结算总手续费

        $months_settle_moneytrade = SettleModel::getMonthsSumMoneytrade();

        //当月结算总笔数

        $months_settle_count = SettleModel::getMonthsCounts();





        //当月提成总金额

        $months_tc_money = OrdercommissionModel::getMonthsSumMoney();

        //当月提成总笔数

        $months_tc_count = OrdercommissionModel::getMonthsCounts();





        //本年充值总金额

        $year_amount = OrderModel::getYearSumMoney();

        //本年充值总手续费

        $year_moneytrade = OrderModel::getYearSumMoneytrade();

        //本年充值总笔数

        $year_count = OrderModel::getYearCounts();





        //本年结算总金额

        $year_settle_money = SettleModel::getYearSumMoney();

        //本年结算总手续费

        $year_settle_moneytrade = SettleModel::getYearSumMoneytrade();

        //本年结算总笔数

        $year_settle_count = SettleModel::getYearCounts();

        //本年提成总金额

        $year_tc_money = OrdercommissionModel::getYearSumMoney();

        //本年提成总笔数

        $year_tc_count = OrdercommissionModel::getYearCounts();



        //今日充值利润

        $today_sumprofit = StatisticModel::getTodaySumProfit();

        //今日结算利润

        $today_settle_sumprofit = StatisticModel::getTodaySumProfit('', '', '', 2);

        //本月充值利润

        $months_sumprofit = StatisticModel::getMonthsSumProfit();

        //本月结算利润

        $months_settle_sumprofit = StatisticModel::getMonthsSumProfit('', '', 2);

        //本年充值利润

        $year_sumprofit = StatisticModel::getYearSumProfit();

        //本年结算利润

        $year_settle_sumprofit = StatisticModel::getYearSumProfit('', 2);



        $data = [

            "admin_info" => session('admin_info'),

            "sum_profit" => intval($sum_profit),

            "sum_user" => intval($sum_user),

            "sum_amount" => intval($sum_amount),

            "sum_tkamount" => intval($sum_tkamount),

            "sum_count" => $sum_count,

            "sum_SuccessCount" => $sum_SuccessCount,

            "sum_rate" => $sum_rate,

            "y" => $y,

            "m" => $m,

            "d" => $d,

            "today_amount" => intval($today_amount),

            "today_count" => $today_count,

            "today_tc_moneytrade" => intval($today_tc_moneytrade),

            "today_settle_money" => intval($today_settle_money),

            'today_moneytrade' => intval($today_moneytrade),

            "today_settle_count" => $today_settle_count,

            "today_tc_money" => intval($today_tc_money),

            "today_tc_count" => $today_tc_count,

            "months_amount" => intval($months_amount),

            "months_settle_moneytrade" => intval($months_settle_moneytrade),

            'months_moneytrade' => intval($months_moneytrade),

            "months_count" => $months_count,

            "months_settle_money" => intval($months_settle_money),

            "months_settle_count" => $months_settle_count,

            "months_tc_money" => intval($months_tc_money),

            "months_tc_count" => $months_tc_count,

            "year_amount" => intval($year_amount),

            "year_moneytrade" => intval($year_moneytrade),

            "year_count" => $year_count,

            "year_settle_money" => intval($year_settle_money),

            "year_settle_moneytrade" => intval($year_settle_moneytrade),

            "year_settle_count" => $year_settle_count,

            "year_tc_money" => intval($year_tc_money),

            "year_tc_count" => $year_tc_count,

            "today_sumprofit" => intval($today_sumprofit),

            "today_settle_sumprofit" => intval($today_settle_sumprofit),

            "months_sumprofit" => intval($months_sumprofit),

            "months_settle_sumprofit" => intval($months_settle_sumprofit),

            "year_sumprofit" => intval($year_sumprofit),

            "year_settle_sumprofit" => intval($year_settle_sumprofit),

        ];



        $this->assign($data);

        $this->display();
    }



    public function searchData()
    {
        $start = I('start') . " 00:00:00";

        //$start ='2018-12-11 09:04:56';

        $end = I('end') . " 23:59:59";

        //$end = '2018-12-21 14:45:17';



        $sql1 = "select sum(ordermoney) as 'sum_ordermoney',sum(moneytrade) as 'sum_moneytrade',sum(moneycost) as 'sum_moneycost',count(*) as 'count' from `__ORDERINFO__` where (datetime<='" . $end . "') and (datetime>='" . $start . "')";

        $sql2 = "select sum(ordermoney) as 'sum_ordermoney',sum(moneytrade) as 'sum_moneytrade',sum(moneycost) as 'sum_moneycost',count(*) as 'count' from `__SETTLEINFO__` where (applytime<='" . $end . "') and (applytime>='" . $start . "')";

        $orderinfo = M()->query($sql1);

        $settleinfo = M()->query($sql2);





        $this->ajaxReturn([

            'status' => 'success',

            'msg' => '数据获取成功!',

            'data' => [

                "orderinfo" => strToInt($orderinfo[0]),

                "settleinfo" => strToInt($settleinfo[0]),

            ]

        ], "json", JSON_UNESCAPED_UNICODE);
    }



    //获取最近当天的统计数据

    public function getTodayStatisticData()
    {
        $recharge_data = StatisticModel::getTodayData();

        $settlement_data = StatisticModel::getTodayData(2);

        $sum_recharge = StatisticModel::getTodaySum('', '', '');

        $sum_settlement = StatisticModel::getTodaySum('', '', '', 2);



        $h = ['00', '01', '02', '03', '04', '05', '06', '07', '08', '09', 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23];

        $recharge = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];

        $settlement = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0];

        foreach ($recharge_data as $reck => $recv) {
            $recharge[intval($recv['h'])] = $recv['sum_amount'];
        }

        foreach ($settlement_data as $setk => $setv) {
            $settlement[intval($setv['h'])] = $setv['sum_amount'];
        }



        $this->ajaxReturn([

            'status' => "success",

            "data" => [

                "h" => $h,

                "recharge" => $recharge,

                "settlement" => $settlement,

                "sum_recharge" => intval($sum_recharge),

                "sum_settlement" => intval($sum_settlement),

            ],

            "msg" => "",

        ], "json");
    }



    //获取最近七天的统计数据

    public function getLastsevenDayData()
    {
        $date = getLatestDate();



        $data = [];



        foreach ($date as $key => $value) {
            $data['date'][] = $value['m'] . "/" . $value['d'];

            $data['recharge'][] = intval(StatisticModel::getTodaySum($value['y'], $value['m'], $value['d']));

            $data['settlement'][] = intval(StatisticModel::getTodaySum($value['y'], $value['m'], $value['d'], 2));
        }

        $data['sum_recharge'] = array_sum($data['recharge']);

        $data['sum_settlement'] = array_sum($data['settlement']);



        $this->ajaxReturn([

            'status' => "success",

            "data" => $data,

            "msg" => "",

        ], "json");
    }



    //获取最近当天不同通道分类的统计数据

    public function getTodayStatiticDataByPayapiclass()
    {
        $recharge_data = StatisticModel::getTodayStatiticDataByPayapiclass();

        $settlement_data = StatisticModel::getTodayStatiticDataByPayapiclass(2);

        $recharge_ids = array_keys($recharge_data);

        $settlement_ids = array_keys($settlement_data);

        $payapiclass_ids = array_unique(array_merge($recharge_ids, $settlement_ids));

        $data = [];

        foreach ($payapiclass_ids as $id) {
            $data['payapiclass'][] = PayapiclassModel::getPayapiClassname($id);

            if (in_array($id, $recharge_ids)) {
                $data['recharge'][] = $recharge_data[$id];
            } else {
                $data['recharge'][] = 0;
            }

            if (in_array($id, $settlement_ids)) {
                $data['settlement'][] = $settlement_data[$id];
            } else {
                $data['settlement'][] = 0;
            }
        }

        $data['sum_recharge'] = array_sum($data['recharge']);

        $data['sum_settlement'] = array_sum($data['settlement']);

        $this->ajaxReturn([

            'status' => "success",

            "data" => $data,

            "msg" => "",

        ], "json");
    }



    //获取最近七天不同通道分类的统计数据

    public function getLatelyByPayapiclass()
    {
        $payapiclass = PayapiclassModel::getClasses();

        $date = getLatestDate();

        $data = [];

        foreach ($payapiclass as $item) {
            foreach ($date as $key => $value) {
                if (!in_array($value['m'] . "/" . $value['d'], $data['date'])) {
                    $data['date'][] = $value['m'] . "/" . $value['d'];
                }

                $recharge[] = intval(StatisticModel::getTodayByPayapiclass($value['y'], $value['m'], $value['d'], $item['id']));
            }

            $data['payapiclass'][] = [

                "name" => $item['classname'],

                "recharge" => $recharge,

            ];

            unset($recharge);
        }

        $this->ajaxReturn([

            'status' => "success",

            "data" => $data,

            "msg" => "",

        ], "json");
    }



    //用户按时段统计成功率

    public function getUserTodaySuccessrate()
    {
        $successrate = SuccessrateModel::getUserTodaySuccessrate(1);

        $h = ['00', '01', '02', '03', '04', '05', '06', '07', '08', '09', '10', '11', '12', '13', '14', '15', '16', '17', '18', '19', '20', '21', '22', '23'];

        $data = [];

        foreach ($h as $item) {
            $data['h'][] = $item;

            if (in_array($item, array_keys($successrate))) {
                $data['rate'][] = sprintf('%.4f', $successrate[$item]['successcount'] / $successrate[$item]['count']) * 100;
            } else {
                $data['rate'][] = 0;
            }
        }

        $this->ajaxReturn([

            'status' => "success",

            "data" => $data,

            "msg" => "",

        ], "json");
    }



    //获取用户最近七天的成功率

    public function getUserLatelySuccessrate()
    {
        $date = getLatestDate();

        $data = [];

        $userid = 13;

        foreach ($date as $key => $value) {
            $data['date'][] = $value['m'] . "/" . $value['d'];

            $data['rate'][] = SuccessrateModel::getUserSuccessrateByDay($userid, $value['d'], $value['m'], $value['y']);
        }

        $this->ajaxReturn([

            'status' => "success",

            "data" => $data,

            "msg" => "",

        ], "json");
    }



    //获取最近7天所有用户成功率

    public function getLatelySuccessrate()
    {
        $date = getLatestDate();

        $data = [];

        foreach ($date as $key => $value) {
            $data['date'][] = $value['m'] . "/" . $value['d'];

            $data['rate'][] = SuccessrateModel::getSuccessrateByDay($value['d'], $value['m'], $value['y']);
        }

        $this->ajaxReturn([

            'status' => "success",

            "data" => $data,

            "msg" => "",

        ], "json");
    }



    //今日不同时段的利润

    public function getTodayProfit()
    {
        $profit_data = StatisticModel::getTodayProfitByh();

        $arr = array_column($profit_data, null, 'h');

        $h = array_keys($arr);

        $sum_profit = [];

        foreach ($h as $item) {
            $sum_profit[] = $arr[$item]['sum_profit'];
        }

        $this->ajaxReturn([

            'status' => "success",

            "data" => [

                'h' => $h,

                'sum_profit' => $sum_profit

            ],

            "msg" => "",

        ], "json");
    }



    //最近7天的利润

    public function getLatelyProfit()
    {
        $date = getLatestDate();

        $data = [];



        foreach ($date as $key => $value) {
            $data['date'][] = $value['m'] . "/" . $value['d'];

            $data['sum_profit'][] = intval(StatisticModel::getTodaySumProfit($value['y'], $value['m'], $value['d']));
        }

        $this->ajaxReturn([

            'status' => "success",

            "data" => $data,

            "msg" => "",

        ], "json");
    }



    //根据不同的通道分类获取充值的利润

    public function getProfitByPayapiclass()
    {
        $sum_profit = StatisticModel::getProfitByPayapiclass();

        $arr = array_column($sum_profit, null, 'payapiclassid');

        $payapiclass_ids = array_keys($arr);

        $data = [];

        foreach ($payapiclass_ids as $id) {
            $data['payapiclass'][] = PayapiclassModel::getPayapiClassname($id);

            $data['sum_profit'][] = $arr[$id]['sum_profit'];
        }

        $this->ajaxReturn([

            'status' => "success",

            "data" => $data,

            "msg" => "",

        ], "json");
    }



    //获取充值利润最大的前5个用户的统计数据

    public function getMaxProfitByUser()
    {
        $sum_profit = StatisticModel::getMaxProfitByUser();

        $arr = array_column($sum_profit, null, 'userid');

        $userids = array_keys($arr);

        $data = [];

        foreach ($userids as $id) {
            $data['username'][] = UserModel::getUsername($id);

            $data['sum_profit'][] = $arr[$id]['sum_profit'];
        }

        $this->ajaxReturn([

            'status' => "success",

            "data" => $data,

            "msg" => "",

        ], "json");
    }



    public function getMaxMoneyByUser()
    {
        $sum_profit = StatisticModel::getMaxMoneyByUser();

        $arr = array_column($sum_profit, null, 'userid');

        $userids = array_keys($arr);

        $data = [];

        foreach ($userids as $id) {
            $data['username'][] = UserModel::getUsername($id);

            $data['amount'][] = $arr[$id]['sum_amount'];
        }

        $this->ajaxReturn([

            'status' => "success",

            "data" => $data,

            "msg" => "",

        ], "json");
    }


    /**

     * 整个判断遮罩层的验证码

     */

    public function verifyCodeType()
    {
        $code_type = I('post.code_type', 0, 'intval');   //验证类型

        $verfiy_code = I('post.verfiy_code', '', 'trim');  //验证码

        $this->checkLayerCode($verfiy_code, $code_type);
    }



    /**

     * 验证登陆密码和管理密码

     */

    public function verifyPwd()
    {
        $login_pwd = I('post.login_pwd', '', 'trim');  //登录密码

        $manage_pwd = I('post.manage_pwd', '', 'trim');  //管理密码

        if (!$login_pwd) {
            $this->ajaxReturn(['status' => 'no', 'msg' => '请先输入登录密码']);
        }

        if (!$manage_pwd) {
            $this->ajaxReturn(['status' => 'no', 'msg' => '请输入管理密码']);
        }

        $password = AdminuserModel::getPassword(session('admin_info.id'));

        if (md5($login_pwd) != $password) {
            $this->ajaxReturn(['status' => 'no', 'msg' => '登录密码错误']);
        }

        $ori_manage_pwd = AdminuserModel::getManagePwd(session('admin_info.id'));

        if (md5($manage_pwd) != $ori_manage_pwd) {
            $this->ajaxReturn(['status' => 'no', 'msg' => '管理密码错误']);
        }

        $secret = GooglecodeModel::getSecret('admin', session('admin_info.id'));

        $src = create_googlecode_qr('管理员' . session('admin_info.user_name'), $secret);

        $this->ajaxReturn(['status' => 'ok', 'msg' => '验证成功', 'src' => $src]);
    }



    /**

     * 在首页开通验证码时，验证用户输入的验证码

     */

    //优化代码

    public function verifyCode()
    {
        $code = I('post.code', '', 'trim');

        $msg = '开启自己的谷歌验证功能:';

        $res = $this->veryGoogleCode('admin', session('admin_info.id'), $code);

        if ($res) {
            GooglecodeModel::saveStatus('admin', session('admin_info.id'));  //修改状态

            $this->addAdminOperate($msg . '开通成功');   //开通时添加操作记录

            $this->ajaxReturn(['status' => 'ok', 'msg' => '验证成功']);
        }

        $this->addAdminOperate($msg . '验证码错误');

        $this->ajaxReturn(['status' => 'no', 'msg' => '验证码错误,请重新输入']);
    }

}
