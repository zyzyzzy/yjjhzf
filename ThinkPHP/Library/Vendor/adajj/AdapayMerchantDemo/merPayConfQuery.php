<?php

# 加载SDK需要的文件
include_once  dirname(__FILE__). '/../AdapayMerchantSdk/init.php';
# 加载商户的配置文件
include_once  dirname(__FILE__). '/config.php';

# 初始化支付配置类
$merConfig = new \AdaPayMerchant\MerchantConf();

# 发起支付配置查询
$merConfig->query(["request_id"=> "req_20190912013859492871"]);

# 对进件结果进行处理
if ($merConfig->isError()){
    //失败处理
    var_dump($merConfig->result);
} else {
    //成功处理
    var_dump($merConfig->result);
}