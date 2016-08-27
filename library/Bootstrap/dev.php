<?php
use \Storage\File as File;
use \Logger as Logger;
use \Service\Database as Database;

/**
 * 调试启动加载
 */
define('YYF_INIT_TIME', microtime(true)); //启动时间
define('YYF_INIT_MEM', memory_get_peak_usage()); //启动内存峰值

class Bootstrap extends Yaf_Bootstrap_Abstract
{
    /**
     * 断言设置
     * @method _initAssert
     * @author NewFuture
     */
    public function _initAssert()
    {
        $assert = Config::get('assert');
        if ($assert['active']) {
            if (version_compare(PHP_VERSION, '7.0.0', '>=')) { //for php7

                //判断环境
                if (-1 == ini_get('zend.assertions')) {
                    exit("调试环境，请开启php7的断言，以便更早发现问题！\n<br>(<u>在php.ini 中的设置 zend.assertions = 1 开启断言【推荐】</u>;或者在 conf/app.ini 中设置 assert.active = 0 关闭此警告【不推荐】。)\n<br>In development environment, please open assertion for php7 to debug ! \n<br> (set 'zend.assertions = 1' in [php.ini][recommended]; or set 'assert.active = 0' in [conf/app.ini] to ignore this [not recommender].)");
                }
                //PHP7配置
                ini_set('zend.assertions', 1);//开启断言
                ini_set('assert.exception', 0);//关闭异常
            } elseif (version_compare(PHP_VERSION, '5.4.8', '<')) {
                //低版本(php5.3)关闭断言
                assert_options(ASSERT_QUIET_EVAL, true);
                assert_options(ASSERT_WARNING, false);
                assert_options(ASSERT_BAIL, false);
                assert_options(ASSERT_ACTIVE, false);
                return;
            }

            assert_options(ASSERT_ACTIVE, true);
           
            //断言错误回调
            assert_options(ASSERT_CALLBACK, 'Debug::assertCallback');
        } else {
            assert_options(ASSERT_ACTIVE, false);
        }

        assert_options(ASSERT_QUIET_EVAL, false);//关闭在断言表达式求值时禁用error_reporting
        assert_options(ASSERT_WARNING, $assert['warning']);//为每个失败的断言产生一个 PHP 警告（warning)
        assert_options(ASSERT_BAIL, $assert['bail']);//在断言失败时中止执行
    }

    /**
     * 开启调试输出
     * @method _initDebug
     * @author NewFuture
     */
    public function _initDebug()
    {
        if ($debug = Config::get('debug.type')) {
            error_reporting(E_ALL); //错误回传
            switch (strtolower($debug)) {

                case 'dump': //直接输出错误
                    ini_set('display_errors', 1);
                    break;

                case 'log': //log到文件
                    ini_set('log_errors', 1);
                    ini_set('error_log', Config::get('runtime') . '/error_log.txt');
                    break;

                default:
                    exit("未知调试类型设置,请检查[conf/app.ini]中的debug.type参数配置\n<br>unkown debug type: $debug . check 'debug.type' setting in [conf/app.ini]");
            }
        }
        if (Config::get('debug.dumpsql')) {
            \Service\Database::$debug=true;
        }
    }

    /**
     * 记录数据库查询
     * @method _initSqlListener
     * @author NewFuture
     */
    public function _initSqlListener()
    {
        Database::$before = 'Debug::sqlBeforeListener';
        Database::$after  = 'Debug::sqlAfterListener';
    }

    /**
     * 添加路由
     * @method _initRoute
     * @author NewFuture
     */
    public function _initRoute(Yaf_Dispatcher $dispatcher)
    {
        if ($routes = Config::get('routes')) {
            $dispatcher->getRouter()->addConfig($routes);
        }
    }

    /**
     * 加载插件
     * @method _initPlugin
     * @param  Yaf_Dispatcher $dispatcher [description]
     * @return [type]                     [description]
     * @access private
     * @author NewFuture
     */
    public function _initPlugin(Yaf_Dispatcher $dispatcher)
    {
        if (false===defined('TRACER_OFF')) {
            $tracer = new TracerPlugin();
            $dispatcher->registerPlugin($tracer);
        }
    }
}
