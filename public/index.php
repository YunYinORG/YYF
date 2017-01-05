<?php
/**
 * YYF - A simple, secure, and high performance PHP RESTful Framework.
 *
 * @see https://github.com/YunYinORG/YYF/
 *
 * @license Apache2.0
 * @copyright 2015-2017 NewFuture@yunyin.org
 */

define('APP_PATH', dirname(__DIR__));

//create yaf app:; 创建YAFAPP
$app = new Yaf_Application(APP_PATH.'/conf/app.ini');

// boorstrap,if needed; 根据需要自动加载启动项
$app->getConfig()->get('application.bootstrap') && $app->bootstrap();

// run the app;运行app
$app->run();
