<?php

# 加载SDK需要的文件
include_once  dirname(__FILE__). '/../AdapayMerchantSdk/init.php';
# 加载商户的配置文件
include_once  dirname(__FILE__). '/config.php';

# 初始化进件类
$merchant = new \AdaPayMerchant\MerchantUser();
$merchant_params = [
    "request_id"=> 'req_mer_'.date("YmdHis").rand(100000, 999999),
    "usr_phone"=> "1300001".rand(1000,9999),
    "cont_name"=> "风清扬",
    "cont_phone"=> "13100001234",
    "customer_email"=> "cuif".rand(1000, 9999).'@163.com',
    "mer_name"=> "河南通达电缆股份有限公司",
    "mer_short_name"=> "通达电缆",
    "license_code"=> "91410300X148288455",
    "reg_addr"=> "测试地址zaac",
    "cust_addr"=> "测试地址zaac",
    "cust_tel"=> "13333783701",
    "mer_valid_date"=> "20201010",
    "legal_name"=> "史万福",
    "legal_type"=> "0",
    "legal_idno"=> "321121198606115128",
    "legal_mp"=> "13333333300",
    "legal_id_expires"=> "20300101",
    "card_id_mask"=> "6222021703001692228",
    "bank_code"=> "01020000",
    "card_name"=> "农行银行",
    "bank_acct_type"=> "1",
    "prov_code"=> "1100",
    "area_code"=> "0011",
    "legal_start_cert_id_expires"=> "20300101",
    "mer_start_valid_date"=> "20300101",
    "rsa_public_key"=> "016515646131sdasd1as32d13as2d13asd13",
];

# 发起进件
$merchant->create($merchant_params);

# 对进件结果进行处理
if ($merchant->isError()){
    //失败处理
    var_export($merchant->result);
} else {
    //成功处理
    var_export($merchant->result);
}