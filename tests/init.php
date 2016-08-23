<?php
ini_set('yaf.use_spl_autoload', 1);
ini_set('display_errors', 1);//显示错误

define('APP_PATH', dirname(dirname(__FILE__)));
define('TRACER_OFF', true);

$app=new Yaf_Application(APP_PATH . '/conf/app.ini');
if ($app->getConfig()->application->bootstrap) {
    $app->bootstrap();//加载启动项
}
