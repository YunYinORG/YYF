<?php

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
            header('Content-type: text/html; charset=utf-8');
            if (version_compare(PHP_VERSION, '7.0.0', '>=')) { //for php7

                //判断环境
                (-1 == ini_get('zend.assertions')) and exit("调试环境，请开启php7的断言，以便更早发现问题！\n<br>(<u>在php.ini 中的设置 zend.assertions = 1 开启断言【推荐】</u>;或者在 conf/app.ini 中设置 assert.active = 0 关闭此警告【不推荐】。)\n<br>In development environment, please open assertion for php7 to debug ! \n<br> (set 'zend.assertions = 1' in [php.ini][recommended]; or set 'assert.active = 0' in [conf/app.ini] to ignore this [not recommender].)");
                //PHP7配置
                ini_set('zend.assertions', 1);//开启断言
                ini_set('assert.exception', 0);//关闭异常
            }

            assert_options(ASSERT_ACTIVE, true);
           
            //断言错误回调
            $assert_callback = function ($script, $line, $code, $message = null) {
                echo "\n断言错误触发：\n<br>",
                     '<b><q>', $message, "</q></b><br>\n",
                    "触发位置$script 第$line 行:<br>\n 判断逻辑<b><code> $code </code></b>\n<br/>",
                    '(这里通常不是错误位置，是错误的调用方式或者参数引起的，请仔细检查)',
                     "<br>\n<small>(tips:断言错误是在正常逻辑中不应出现的情况，生产环境关闭系统断言提高性能)</small>\n<br>";
            };
            assert_options(ASSERT_CALLBACK, $assert_callback);
        } else {
            assert_options(ASSERT_ACTIVE, false);
        }

        assert_options(ASSERT_QUIET_EVAL, true);//在断言表达式求值时禁用error_reporting
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
                    ini_set('error_log', Config::get('tempdir') . '/error_log.txt');
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
     * @method _initSqlLog
     * @author NewFuture
     */
    public function _initSqlLog()
    {
        /*记录执行次数*/
        Yaf_Registry::set('_sql_exec_id', 0);
        /*执行前调用*/
        \Service\Database::$before=function (&$sql, &$param, $name) {
            $id=Yaf_Registry::get('_sql_exec_id');
            Yaf_Registry::set('_sql_exec_id', $id+1);
            Yaf_Registry::set('_sql_exec_t', microtime(true));
            Logger::write('[SQL'. str_pad($id, 3, '0', STR_PAD_LEFT).'] '.$sql, 'SQL');
            if ($param) {
                Logger::write('[params] '.json_encode($param, JSON_UNESCAPED_UNICODE), 'SQL');
            }
        };
        /*执行后调用*/
        \Service\Database::$after=function (&$db, &$result, $name) {
            Logger::write('[result] '.json_encode($result, JSON_UNESCAPED_UNICODE), 'SQL');
            if ($db->errorCode()!=0) {
                Logger::write('[ERROR!] '.json_encode($db->errorInfo(), JSON_UNESCAPED_UNICODE), 'SQL');
            }
            $timer=microtime(true)-Yaf_Registry::get('_sql_exec_t');
            Logger::write('[inform] '.($timer*1000).' ms ('.$name. ") \n\r", 'SQL');
        };
    }

    /**
     * 开启调试输出
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
        $tracer = new TracerPlugin();
        $dispatcher->registerPlugin($tracer);
    }
}
