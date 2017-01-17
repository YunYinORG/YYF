<?php
/**
 * YYF - A simple, secure, and high performance PHP RESTful Framework.
 *
 * @link https://github.com/YunYinORG/YYF/
 *
 * @license Apache2.0
 * @copyright 2015-2017 NewFuture@yunyin.org
 */

use \Debug\Assertion as Assertion;
use \Debug\Tracer as Tracer;
use \Yaf_Bootstrap_Abstract as Bootstrap_Abstract;

define('YYF_INIT_TIME', microtime(true)); //启动时间
define('YYF_INIT_MEM', memory_get_peak_usage()); //启动内存峰值

/**
 * 调试启动加载
 *
 * @author NewFuture
 */
class Bootstrap extends Bootstrap_Abstract
{
    /**
     * 添加路由
     */
    public function _initRoute(Yaf_Dispatcher $dispatcher)
    {
        if ($routes = Config::get('routes')) {
            $dispatcher->getRouter()->addConfig($routes);
        }
    }

    /**
     * 断言设置
     */
    public function _initAssert()
    {
        $config = Config::get('assert')->toArray();
        Assertion::init($config);
    }

    /**
     * 开启调试输出
     */
    public function _initDebug()
    {
        if ($debug = Config::get('debug.error')) {
            error_reporting(E_ALL); //错误回传
            switch (strtolower($debug)) {
                case 'dump': //直接输出错误
                    ini_set('display_errors', 1);
                    break;

                case 'log': //log到文件
                    ini_set('log_errors', 1);
                    ini_set('error_log', Config::get('runtime').'/error_log.txt');
                    break;

                default:
                    exit('未知调试类型设置,请检查[conf/app.ini]中的debug.type参数配置<br>'.
                        "\nunkown debug type: $debug . check 'debug.type' setting in [conf/app.ini]");
            }
        }
    }

    /**
     * 开启日志监控
     */
    public function _initLogListener()
    {
        if ($listen = Config::get('debug.listen')) {
            Debug::instance()->initLog($listen);
        }
    }

    /**
     * 记录数据库查询
     */
    public function _initSqlListener()
    {
        if ($config = Config::get('debug.sql')) {
            if ($output = $config->get('output')) {
                Debug::instance()->initSQL($output, $config->get('result'));
            }
            if ($config->get('dumpdo')) {
                \Service\Database::$debug = true;
            }
        }
    }

    /**
     * 加载统计插件
     *
     * @param Yaf_Dispatcher $dispatcher
     */
    public function _initTracer(Yaf_Dispatcher $dispatcher)
    {
        if ($tacerdebug = Config::get('debug.tracer')) {
            $dispatcher->registerPlugin(Tracer::Instance($tacerdebug));
        }
    }
}
