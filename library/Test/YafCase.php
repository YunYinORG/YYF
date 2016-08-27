<?php
namespace Test;

use \Yaf_Application as Application;
use \PHPUnit_Framework_TestCase as TestCase;

/**
 * 框架库测试基类 YafCase
 * Function list:
 * - app()；获取当前运行app实例
 */

abstract class YafCase extends TestCase
{

    /*是否自动加载bootstrap*/
    protected static $bootstrap=true;
    /*是否自动启动yaf app*/
    protected static $auto_init=true;

    protected $app=null;

    public function __construct()
    {
        parent::__construct();
        if (!extension_loaded('yaf')) {
            $this->markTestSkipped('YAF扩展未加载[not YAF extension]');
        }
        if (static::$auto_init) {
            if (!$this->app=static::app()) {
                $this->markTestSkipped('APP 启动失败！');
            }
        }
    }

    /**
    * 获取当前APP
    * @return Application
    */
    public static function app()
    {
        if ($app=Application::app()) {
            return $app;
        } else {
            //加载APP
            defined('APP_PATH') || define('APP_PATH', realpath(__DIR__ . '/../../'));
            $conf=APP_PATH . '/conf/app.ini';
            $app=new Application(APP_PATH . '/conf/app.ini');

            //加载启动项 app Bootstrap
            if (static::$bootstrap&&$app->getConfig('application.bootstrap')) {
                $app->bootstrap();
            }
            return $app;
        }
    }
}
