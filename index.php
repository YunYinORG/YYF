<?php
define('APP_PATH', dirname(__FILE__)); //项目目录
$app = new Yaf_Application(APP_PATH . '/conf/app.ini');
$app->bootstrap()->run();
//如果不用调试模块可以直接$app->run();
?>