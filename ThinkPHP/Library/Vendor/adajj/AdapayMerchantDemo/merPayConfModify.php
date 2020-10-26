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
    "sub_api_key"=> "api_live_826e0027-68b4-4468-ada8-aa35babdc86e",
    "alipay_request_params"=> json_encode([
        "mer_short_name"=> "尚云科技",
        "mer_phone"=> "18919876721",
        "fee_type"=> "02",
        "card_no"=> "6227000734730752177",
        "card_name"=> "浦发银行",
        "category"=> "2015050700000000",
        "cls_id"=> "5812",
        "mer_name"=> "上海尚云科技服务有限公司",
        "mer_addr"=> "上海市静安区",
        "contact_name"=> "朱先生",
        "contact_phone"=> "15866689123",
        "contact_mobile"=> "15866689123",
        "contact_email"=> "817888900@qq.com",
        "legal_id_no"=> "310555196710215555",
        "mer_license"=> "110108001111111",
        "province_code"=> "310000",
        "city_code"=> "310100",
        "district_code"=> "310115",
    ], JSON_UNESCAPED_UNICODE)
];

# 发起商户进件修改
$merConfig->modify($mer_config_params);

# 对进件结果进行处理
if ($merConfig->isError()){
    //失败处理
    var_dump($merConfig->result);
} else {
    //成功处理
    var_dump($merConfig->result);
}