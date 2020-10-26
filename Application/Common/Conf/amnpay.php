<?php
return array(
    'AMNPAY_VERSION' => '',//请求独立系统的版本号
    'AMNPAY_MERID' => '',//独立系统分配的商户号
    'AMNPAY_SECRETKEY' => '',  //请求独立系统时的私钥,用来加密
    'AMNPAY_PUBLIC_SECRETKEY' => '',  //独立系统请求解冻时用来解密的公钥
    'AUTO_UNFREEZE_URL' => '',  //自动解冻接口路径,即独立系统请求解冻的接口
    'ONE_TASK_UNFREEZE_URL' => '',  //创建单条定时解冻任务时请求的接口
    'MORE_TASK_UNFREEZE_URL' => '',  //创建多条定时解冻任务时请求的接口
    'DELETE_TASK_UNFREEZE_URL' => '',  //删除定时解冻任务时请求的接口
);