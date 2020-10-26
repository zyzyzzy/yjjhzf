<?php

namespace Pay\Controller;

class IndexController extends PayController
{
    protected $returnJson = [];  /*返回输出的数组*/
    protected $obj;

    public function __construct()
    {
        parent::__construct();
        $this->returnJson["status"] = "error";
    }

    //2019-4-24 rml：优化
    public function index()
    {
//        echo '<pre>';print_r($_SERVER);die;
        /*
         * $this->obj = A("Pay/Alipay");
         * dump($this->obj);
         * exit($this->obj->PayName);
         */
        /*从版本控制器里引入通道*/
        // if (!(A("Version/Index")->index(IS_GET ? I("get.") : I("post."), $this->returnJson))) {
        //判断ip和域名的黑名单
        $res_black = $this->checkBlack();
        if (!$res_black) {
            exit(json_encode($this->returnJson, JSON_UNESCAPED_UNICODE));
        }
        //判断接口状态,只有接口开启才能走pay模块
        $res_api = $this->checkApiSwitch();
        if (!$res_api) {
            exit(json_encode($this->returnJson, JSON_UNESCAPED_UNICODE));
        }
        //判断交易接口版本
        if (!(A("Version/Index")->index(I("request."), $this->returnJson))) {
            //2019-08-19 张杨 如果报错，删除其它的数据
            unset($this->returnJson["parameter"]);
            unset($this->returnJson["decryptdata"]);
            exit(json_encode($this->returnJson, JSON_UNESCAPED_UNICODE));
        } elseif ($this->returnJson['status'] == 'success') {
            parent::indexpay($this->returnJson);
        }

    }

    /**
     * 2019-02-24 张杨新订单查询入口
     */
    public function orderquery()
    {
        if (!(A("Version/Index")->index(I("request."), $this->returnJson))) {
            exit(json_encode($this->returnJson, JSON_UNESCAPED_UNICODE));  //输出JSON格式的错误信息
        } elseif ($this->returnJson['status'] == 'success') {
            A("Pay/Pay")->Query($this->returnJson);//2019-02-28汪桂芳修改
        }
    }

    /**
     * 2019-02-28汪桂芳:检测接口开关是否开启,需要检测全部功能的开关和接口功能的开关
     */
    //2019-4-24 rml：优化
    final private function checkApiSwitch()
    {
        $switch = M('website')->order('id DESC')->field('all_valve,api_valve')->find();
        //0:开启 1:关闭
        if ($switch['all_valve'] != 0 || $switch['api_valve'] != 0) {
            $this->returnJson["msg"] = "支付接口关闭,请联系管理员处理";
            return false;
        } else {
            return true;
        }
    }

    /**
     * 2019-03-11汪桂芳:检测来源ip和域名是否在系统的黑名单中
     */
    //2019-4-25 rml：修改
    final private function checkBlack()
    {
        $user_ip = getIp();
        $res_ip = M('blackip')->where(['ip' => ['EQ', $user_ip]])->count();
        if ($res_ip) {
            addBlackRecord(0, 'user', 1, $user_ip, 2, json_encode($_REQUEST, JSON_UNESCAPED_UNICODE));
            $this->returnJson["msg"] = "来源IP已进入黑名单,无法进行交易";
            return false;
        }
        $user_domain = $_SERVER['HTTP_REFERER'];   //获取跳到该链接的上一个链接的地址。形式：http://www.juhezhifu.cc/UserPay/SelfCash/payConfirm.html
        $all_domain = M('blackdomain')->getField('domain', true);
        $res_domain = false;
        foreach ($all_domain as $v) {
            if (strpos($v, $user_domain) !== false) {
                $res_domain = true;
                break;  //如果找到域名，则跳出循环
            }
        }
        if ($res_domain) {
            addBlackRecord(0, 'user', 2, $user_domain, 2, json_encode($_REQUEST, JSON_UNESCAPED_UNICODE));
            $this->returnJson["msg"] = "来源域名已进入黑名单,无法进行交易";
            return false;
        }
        return true;
    }
}