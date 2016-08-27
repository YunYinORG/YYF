<?php
//DEFINE the project path;项目目录
define('APP_PATH', dirname(__DIR__));
//create yaf app:; 创建YAFAPP
$app = new Yaf_Application(APP_PATH . '/conf/app.ini');
// boorstrap,if needed; 根据需要自动加载启动项
$app->getConfig()->get('application.bootstrap') && $app->bootstrap();
// run the app;运行app
$app->run();
