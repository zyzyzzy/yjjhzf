<?php

// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2014 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------



// 应用入口文件


//防止SQL注入代码，
/////*****防止SQL注入代码 begin*****/////
/*
function sqlInj($value) {
    //过滤参数
    $arr = explode('|', 'UPDATEXML|UPDATE|WHERE|EXEC|INSERT|SELECT|DELETE|CHR|MID|MASTER|TRUNCATE|DECLARE|BIND|DROP|NOT BETWEEN|NOTBETWEEN|BETWEEN|NOTIN|CONTACT|EXTRACTVALUE|LOAD_FILE|INFORMATION_SCHEMA|outfile|into|union');
    if (is_string($value)) {
        foreach ($arr as $a) {
            //判断参数值中是否含有SQL关键字，如果有则跳出
            if (stripos($value, $a) !== false){
                exit('参数错误，含有敏感字符');
            } 
        }
    } elseif (is_array($value)) {
        //如果参数值是数组则递归遍历判断
        foreach ($value as $v) {
            sqlInj($v);
        }
    }
}

//过滤请求参数
foreach ($_REQUEST as $key => $value) {
    sqlInj($value);
}
*/
/////*****防止SQL注入代码 end*****/////


// 检测PHP环境
if(version_compare(PHP_VERSION,'5.3.0','<'))  die('require PHP > 5.3.0 !');
date_default_timezone_set('Asia/Shanghai');

// 开启调试模式 建议开发阶段开启 部署阶段注释或者设为false
define('APP_DEBUG',True);

// 定义应用目录
define('APP_PATH','./Application/');
require './Vendor/autoload.php';

// 引入ThinkPHP入口文件
require './ThinkPHP/ThinkPHP.php';

// 亲^_^ 后面不需要任何代码了 就是如此简单