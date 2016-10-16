<?php
namespace Test;

use \Yaf_Application as Application;
use \Yaf_Loader as Loader;
use \Debug\Assertion as Assertion;
use \PHPUnit_Framework_TestCase as TestCase;

defined('APP_PATH') || define('APP_PATH', realpath(__DIR__ . '/../../'));

Loader::import(__DIR__.'/functions.php');

/**
 * 框架库测试基类 YafCase
 * Function list:
 * - app()；获取当前运行app实例
 *
 * @preserveGlobalState disabled
 * @backupGlobals disabled
 * @backupStaticAttributes disabled
 */
abstract class YafCase extends TestCase
{

    /*是否自动加载bootstrap*/
    protected static $bootstrap = true;
    /*是否自动启动yaf app*/
    protected static $auto_init = true;

    protected $app = null;

    public function __construct($name = null, array $data = array(), $dataName = '')
    {
        call_user_func_array('parent::__construct', func_get_args());
        if (!extension_loaded('yaf')) {
            $this->markTestSkipped('YAF扩展未加载[not YAF extension]');
        }
        if (static::$auto_init) {
            if (!$this->app = static::app()) {
                $this->markTestSkipped('APP 启动失败！');
            }
        }
    }

    /**
     * 获取当前APP
     *
     * @return Application
     */
    public static function app()
    {
        if (!$app = Application::app()) {
            //加载APP
            $conf = APP_PATH . '/conf/app.ini';
            $app = new Application(APP_PATH . '/conf/app.ini');
            $conf = $app->getConfig();
            //加载启动项 app Bootstrap
            if (static::$bootstrap && $conf->get('application.bootstrap')) {
                $app->bootstrap();
            }

            if (version_compare(PHP_VERSION, '5.4.8', '<') && $app->environ() == 'dev') {
                //低版本(php5.3)断言
                Assertion::init($conf->get('assert')->toArray());
            }
        }
        return $app;
    }

    /**
     * 检查文件权限，
     *
     * @param $base 基础值，文件0666 目录0777
     * @param $umask
     * @requires OS Linux
     */
    public function assertFileMode($path, $base = 0666, $umask = null)
    {
        $umask = $umask ?: static::app()->getConfig()->umask;
        if (null === $umask) {
            $mode = 0700 & $base;
        } else {
            $mode = intval($umask, 8) & $base ^ $base;
        }
        clearstatcache();
        $this->assertSame(fileperms($path) & $mode, $mode, $path.'文件权限与预设不符(file permission not the same)');
    }

    public function __sleep()
    {
        $keys = array();
        return $keys;
    }

    public function __wakeup()
    {
        if (static::$auto_init) {
            if (!$this->app = static::app()) {
                $this->markTestSkipped('APP 启动失败！');
            }
        }
    }
}
