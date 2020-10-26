<?php
/**
 * AdaPay 对账单下载
 * author: adapay.com https://docs.adapay.tech/api/04-trade.html
 * Date: 2019/09/17
 */

# 加载SDK需要的文件
include_once  dirname(__FILE__). "/../AdapaySdk/init.php";
# 加载商户的配置文件
include_once  dirname(__FILE__). "/config.php";

# 初始化对账单下载对象类
$bill = new \AdaPaySdk\Tools();

# 对账单下载
$bill->download(["bill_date"=> "20190905"]);

# 对账单下载结果进行处理
if ($bill->isError()){
    //失败处理
    var_dump($bill->result);
} else {
    //成功处理
    var_dump($bill->result);
}