<?php
/**
 * AdaPay 支付订单查询
 * author: adapay.com https://docs.adapay.tech/api/04-trade.html
 * Date: 2019/08/03 13:05
 */

# 加载SDK需要的文件
include_once  dirname(__FILE__). "/../AdapaySdk/init.php";
# 加载商户的配置文件
include_once  dirname(__FILE__). "/config.php";

# 初始化支付类
$payment = new \AdaPaySdk\Payment();
#发起支付订单查询
$payment->orderQuery(['payment_id'=> '002112019101615181410030442335571763200']);

# 对关单结果进行处理
if ($payment->isError()){
    //失败处理
    var_dump($payment->result);
} else {
    //成功处理
# 加载SDK需要的文件
    var_dump($payment->result);
}