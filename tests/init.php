<?php
define('APP_PATH', dirname(dirname(__FILE__)));
define('TRACER_OFF', true);

$app=new Yaf_Application(APP_PATH . '/conf/app.ini');
if ($app->getConfig()->application->bootstrap) {
    $app->bootstrap();//加载启动项
}

