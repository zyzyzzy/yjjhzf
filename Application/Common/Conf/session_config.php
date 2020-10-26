<?php

return [

    /* SESSION设置 begin*/

    //初始化 session 配置数组 支持type name id path expire domain 等参数

    'SESSION_OPTIONS'       =>  array(

        'type'=>'Redis',

        'prefix'=>'session_amnpay_',

        'path'=>'tcp://127.0.0.1:6379',

        'expire'=>7200,

    ),



    'SESSION_AUTO_START'    =>  true,    // 是否自动开启Session

    'SESSION_TYPE'          =>  'Redis', // session hander类型 默认无需设置 除非扩展了session hander驱动

    'SESSION_PREFIX'        =>  'session_amnpay_', // session 前缀

    'VAR_SESSION_ID'        =>  'session_id', //sessionID的提交变量

    'SESSION_REDIS_EXPIRE'  =>  7200,        //session有效期(单位:秒) 0表示永久缓存


    'SESSION_REDIS_HOST'    =>  '127.0.0.1', //redis服务器ip

    'SESSION_REDIS_PORT'    =>  '6379',       //端口

    'SESSION_REDIS_AUTH'    =>  '', //认证密码


];