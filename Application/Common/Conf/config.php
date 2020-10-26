<?php

return array(
    'LOAD_EXT_CONFIG' => 'database,menu,adminpwd,formatdata,moneychangetype,email,orderstatustype,adminaddress,settlestatustype,orderopentype,amnpay,orderpay,selfcashback,ZFB,WX,SD,loginset', // 加载扩展配置文件
    
    'DEFAULT_MODULE' => 'Home', // 默认模块 默认为安装模块   2019-4-1 任梦龙：将宣传首页传入
    
    'URL_MODEL' => 2, // URL访问模式,可选参数0、1、2、3,代表以下四种模式：
   
    'URL_PATHINFO_DEPR' => '/', // PATHINFO模式下，各参数之间的分割符号
    
    'MODULE_DENY_LIST' => array('Common', 'Runtime', 'Payaccount', 'Version'), //禁止直接访问的模块
    
    'TMPL_TEMPLATE_SUFFIX' => '.html', // 默认模板文件后缀
   
    'URL_HTML_SUFFIX' => 'html',
    
    'APP_FILE_CASE' => true, // 是否检查文件的大小写 对Windows平台有效
   
    'TMPL_L_DELIM' => '<{', // 模板引擎普通标签开始标记
    
    'TMPL_R_DELIM' => '}>',
    
    'SHOW_PAGE_TRACE' => false,  //关闭trace
    
    'TESTLOG' => 'testlog',  //生成临时测试日志文件的文件夹
    
    'KEY_STR_PATH' => 'Public/Uploads/payapiaccountkey/',  //账号密钥上传文件存储位置
    
    'USER_KEY_STR_PATH' => 'Public/Uploads/userkeypath/',  //用户密钥上传文件存储位置
    
    'QRCODE_PATH' => 'Public/Uploads/qrcodeimgs/',  //二维码模板图片存储位置
    
    'ADVTEMPLATE_PATH' => 'Public/Uploads/advtemplate/',  //广告模板图片存储位置
    
    'DATATEMPLATE_PATH' => 'Public/Uploads/datatemplate/',  //统计模板图片存储位置
    
    'VERIFYINFO_PATH' => 'Public/Uploads/verifyinfo/',  //用户认证图片存储位置
    
    'CASHIER_PATH' => 'Public/Uploads/cashier/',  //收银台logo存储位置
    
    'SYSTEMBANK_PATH' => 'Public/Uploads/systembank/',  //系统银行图片存储路径
    
    'PAYAPICLASS_PATH' => 'Public/Uploads/payapiclass/',  //通道分类图片存储路径
    
    'USERQRCODE_PATH' => 'Public/UserPay/images/userqrcode/',  //用户自助收银二维码图片存储位置
    
    'MANYSETTLE_PATH' => 'Public/Uploads/manysettle/',  //用户批量结算文件存储位置
    
    'CHILD_MENU_PATH' => 'Public/childmenujson/',  //用户的子账号的菜单文件存储位置

    //添加用户的菜单文件存储位置,系统后台的菜单存储位置,网站名称

    'USER_MENU_PATH' => 'Public/usermenujson/',

    'ADMIN_MENU_PATH' => 'Public/menujson/',

    'WEBSITE_NAME' => '易吉支付系统',

    'USER_COMMISSION' => 0.0010,//代理商默认的提成利润

    'SESSION_OPTIONS' => array(

        'use_cookies' => 1,         //是否在客户端用 cookie 来存放会话 ID，1是开启

        'use_trans_sid' => true,    //跨页传递

        'expire' => 10800,  

    ),

    'LOGIN_COUNT' => 3,   //设置默认的登录限制次数

    'SET_TIME' => 300,   //设置默认的等待时间  300/60=5分钟

    'LOGIN_TEMPLATE_PATH' => 'Public/Uploads/logintemplates/',  //添加登录页面模板图片存储位置

    'DEFAULT_FILTER' => 'trim,htmlspecialchars'    // 修改系统默认的变量过滤机制:添加trim去两端空格

);