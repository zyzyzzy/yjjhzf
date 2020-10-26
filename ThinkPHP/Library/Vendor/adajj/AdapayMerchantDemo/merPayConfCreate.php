<?php
# 加载SDK需要的文件
include_once  dirname(__FILE__). '/../AdapayMerchantSdk/init.php';
# 加载商户的配置文件
include_once  dirname(__FILE__). '/config.php';

# 初始化支付配置类
$merConfig = new \AdaPayMerchant\MerchantConf();

$mer_config_params = [
    "request_id"=> "req_cfg_".date("YmdHis").rand(100000, 999999),
    # 推荐商户的api_key
    "sub_api_key"=> "",
    # 银行渠道号
    "bank_channel_no"=> "",
    # 费率
    "fee_type"=> "1",
    # 商户app_id
    "app_id"=> "app_0c2acc98-7437-4de6-ad4c-7c38a0c782e4",
    # 微信经营类目
    "wx_category"=> "服装类",
    # 支付宝经营类目
    "alipay_category"=> "服装",
    "cls_id"=> "01",
    # 服务商模式
    "model_type"=> "1",
    # 商户种类
    "mer_type"=> "1",
    # 省份code
    "province_code"=> "01",
    # 城市code
    "city_code"=> "11",
    # 县区code
    "district_code"=> "21",
    # 配置信息值
    "add_value_list"=> json_encode(['wx_lite'=> ['appid'=> '13213123123123']])
];

# 发起支付配置
$merConfig->create($mer_config_params);

# 对进件结果进行处理
if ($merConfig->isError()){
    //失败处理
    var_dump($merConfig->result);
} else {
    //成功处理
    var_dump($merConfig->result);
}