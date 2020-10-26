<?php
exit("dddddd");
// 绑定访问Admin模块
define('BIND_MODULE','Workerman');
// 绑定访问Index控制器
define('BIND_CONTROLLER','Workerman');
// 绑定访问test操作
define('BIND_ACTION','index');

define('APP_DEBUG',true);
// 定义应用目录
define('APP_PATH','/Application/');
// 引入ThinkPHP入口文件
require '/ThinkPHP/ThinkPHP.php';