<?php

return array(

    'REQUEST_FORMAT_DATA' => [    //标准格式化请求参数

        'userid' => '',  //用户ID

        'amount' => '',  //交易金额

        'orderid' => '',  //用户订单号

        //'callbackurl' => '',  //同步跳转回调地址

        'notifyurl' => '',   //异步回调地址

        'orderdatetime' => '',    //交易订单时间

        //银行编码非必需

        //'bankcode' => '',   //银行编码

        'tongdao' => '',  //通道编码

        //扩展字段非必需

        //'extend' => '',   //扩展字段

        'other' => array(), //其它的字段

    ],

    'REQUEST_FORMAT_DATA_QUERY' => [    //标准格式化请求参数

        'userid' => '',  //用户ID

        'amount' => '',  //交易金额

        'orderid' => '',  //用户订单号

        //'callbackurl' => '',  //同步跳转回调地址

        'notifyurl' => '',   //异步回调地址

        'orderdatetime' => '',    //交易订单时间

        //银行编码非必需

        //'bankcode' => '',   //银行编码

        'tongdao' => '',  //通道编码

        //扩展字段非必需

        //'extend' => '',   //扩展字段

        'other' => array(), //其它的字段

    ],

    'TONGDAO' => 'tongdao',  //通道字段名

    'SYSTEST' => 'systest',  //开启测试的参数值

    'SECRETKEY_ARRAY' => ['md5str','user_keypath','sys_publickeypath','sys_privatekeypath'], //用户密钥字段



);