<?php
use \Storage\File as File;
use \Logger as Logger;
use \Service\Database as Database;
use \Debug\Assertion as Assertion;

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
        $config = Config::get('assert')->toArray();
        Assertion::init($config);
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
