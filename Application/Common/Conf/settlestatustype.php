<?php

return array(

    //订单状态

    'SETTLESTATUS' => [

        0 => '未处理',

        1 => '处理中',

        2 => '已打款',

        3 => '已退款',

        4 => '打款失败',

    ],



    //退款状态

    'REFUNDSETTLESTATUS' => [

        0 => '正常',

        1 => '退款中',

        2 => '已退款',

        3=> '退款失败',
    ],



    //单笔结算提交地址

    'ONE_SETTLE_URL' => 'https://www.juhezhifu.cc/Daifu/Api/index',

    //批量结算提交地址

    'MANY_SETTLE_URL' => 'https://www.juhezhifu.cc/Daifu/Apis/index',

);