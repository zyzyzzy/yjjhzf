<?php
namespace Daifu\Controller;

use Think\Controller;

class IndexController extends DaiFuController
{
    protected $returnJson = [];  /*返回输出的数组*/
    protected $obj;

    public function __construct()
    {
        parent::__construct();
        $this->returnJson["status"] = "error";
    }

    public function index()
    {
        //2019-03-11汪桂芳添加:判断ip和域名的黑名单
        $res1 = $this->checkBlack();
        //2019-08-15 张杨
        if (!$res1) {
            exit(json_encode($this->returnJson, JSON_UNESCAPED_UNICODE));
        }
        //2019-02-28汪桂芳添加:判断接口状态,只有接口开启才能走pay模块
        $res = $this->checkApiSwitch();
        //2019-08-15 张杨
        if (!$res) {
            exit(json_encode($this->returnJson, JSON_UNESCAPED_UNICODE));
        }


        if (!(A("Version/IndexDaifu")->index(I("request."), $this->returnJson))) {
            //2019-01-07汪桂芳修改，方便查看报错信息
            exit(json_encode($this->returnJson,JSON_UNESCAPED_UNICODE));  //输出JSON格式的错误信息
        } elseif ($this->returnJson['status'] == 'success') {
            // A("Pay/Pay")->index($this->returnJson);//2019-02-28汪桂芳修改
            parent::Settle($this->returnJson);
        }

    }

    /**
     * 2019-02-28汪桂芳:检测接口开关是否开启,需要检测全部功能的开关和接口功能的开关
     */
    final private function checkApiSwitch()
    {
        $switch = M('website')->where('id=1')->field('api_valve')->find();
        //0:开启 1:关闭
        if ($switch['all_valve']==1 || $switch['settle_valve']==1) {
            $this->returnJson["msg"] = "结算接口关闭,请联系管理员处理";
            return false;
        } else {
            return true;
        }
    }

    /**
     * 2019-03-11汪桂芳:检测来源ip和域名是否在系统的黑名单中
     */
    final private function checkBlack()
    {
        $user_ip = getIp();
        $user_domain = $_SERVER['HTTP_REFERER'];
        $res1 = M('blackip')->where('ip="'.$user_ip.'"')->select();
        if($res1){
            addBlackRecord('','user',1,$user_ip,2,json_encode($_REQUEST));
        }
        $all_domain = M('blackdomain')->select();
        $res2 = false;
        foreach ($all_domain as $k=>$v){
            if(strpos($user_domain,$v['domain'])){
                $res2 = true;
            }
        }
        if($res2){
            addBlackRecord('','user',2,$user_domain,2,json_encode($_REQUEST));
        }
        if($res1 || $res2){
            $this->returnJson["msg"] = "来源域名或ip已进入黑名单,无法进行结算";
            return false;
        }else{
            return true;
        }
    }
}