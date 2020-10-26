<?php

# 加载SDK需要的文件
include_once  dirname(__FILE__). '/../AdapayMerchantSdk/init.php';
# 加载商户的配置文件
include_once  dirname(__FILE__). '/config.php';

# 初始化进件类
$merchant = new \AdaPayMerchant\MerchantUser();
# 进件成功后的request_id
# 发起进件
$merchant->query(["request_id"=> "req_mer_20190912013859492871"]);

# 对进件结果进行处理
if ($merchant->isError()){
    //失败处理
    var_dump($merchant->result);
} else {
    //成功处理
    var_dump($merchant->result);
}