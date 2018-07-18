<?php

// 检测PHP环境
if(version_compare(PHP_VERSION,'5.3.0','<'))  die('运行环境中PHP版本不得低于5.3.0');

// 开启调试模式 建议开发阶段开启 部署阶段注释或者设为 False / True
define('APP_DEBUG',True);
// 定义应用目录
define('APP_PATH','./Application/');
//  绑定模块
define('BIND_MODULE','Admin');
// 应用运行时目录
define('RUNTIME_PATH','./Runtime/');
// 引入ThinkPHP入口文件
require './vendor/autoload.php';
require './ThinkPHP/ThinkPHP.php';