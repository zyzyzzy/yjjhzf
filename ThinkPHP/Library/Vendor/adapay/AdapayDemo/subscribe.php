#!/usr/bin/env php
<?php
/**
 * AdaPay 异步消息接收, 商户需要自己启动此脚本来监听支付消息
 * linux下 php subscribe.php start
 * windows: php subscribe.php
 * author: adapay.com https://docs.adapay.tech/api/04-trade.html
 * Date: 2019/08/08 10:05
 */

# 加载SDK需要的文件
include_once  dirname(__FILE__). "/../AdapaySdk/init.php";
# 加载商户的配置文件
include_once  dirname(__FILE__). "/config.php";

# 消息回调函数
$callback = function ($message, $topic){
     //$topic 订阅的 topic
    //商户需要对消息结果处理
    //$message 格式：{"appId": "商户appId" ,"created": "创建时间年月日时分秒", "data": "{\"amount"\：\"0.01\"}", //"type":"payment.succeeded"}
};

# 实例化消息订阅类
$worker = new \AdaPay\AdaSubscribe();
$apiKey = "api_live_9c14f264-e390-41df-984d-df15a6952031";
$client_id = "2019101300000001";
# 启动消息监听服务进程
$worker->workerStart($worker, function ($content, $topic) use ($callback){
    call_user_func($callback, $content, $topic); //设置接收消息回调处理
}, $apiKey, $client_id);