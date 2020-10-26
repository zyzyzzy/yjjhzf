#!/usr/bin/env php
<?php
/**
 * AdaPay 异步消息接收, 商户需要自己启动此脚本来监听支付消息
 * linux下 php subscribe.php start windows: php subscribe.php
 * author: adapay.com https://docs.adapay.tech/api/04-trade.html
 * Date: 2019/08/08 10:05
 */

# 加载SDK需要的文件
include_once  dirname(__FILE__). '/../AdapayMerchantSdk/init.php';
# 加载商户的配置文件
include_once  dirname(__FILE__). '/config.php';

# 消息回调函数
$callback = function ($message, $topic){
    # 消息例子
    //{"created_time":"1573192653","data":"{\"alipay_stat\":\"S\",\"object\":\"resident_modify\",\"request_id\":\"req_cfg_20191108055731352026\",\"status\":\"succeeded\",\"type\":\"resident.modify.succeeded\",\"prod_mode\":\"true\"}",
    //"prod_mode":"true","sign":"返沪的数据签名值","id":"0038756954395152384","type":"resident.modify.failed"}
};

# 实例化消息订阅类
$worker = new \AdaPay\AdaSubscribe();
$apiKey = "api_live_3128984e-7c0f-4799-8c45-90e673870cb5";
$client_id = "";// 机器唯一标识
# 启动消息监听服务进程
$worker->workerStart($worker, function ($content, $topic) use ($callback){
    call_user_func($callback, $content, $topic); //设置接收消息回调处理
}, $apiKey, $client_id);