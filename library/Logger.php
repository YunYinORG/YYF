<?php
/**
 * YYF - A simple, secure, and high performance PHP RESTful Framework.
 *
 * @link https://github.com/YunYinORG/YYF/
 *
 * @license Apache2.0
 * @copyright 2015-2017 NewFuture@yunyin.org
 */

use Storage\File as File;

/**
 * Logger 日志记录
 *
 * @link https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-3-logger-interface.md
 *
 * @author NewFuture
 *
 * @example
 * Logger::write($msg, $level = 'NOTICE')//快速写入
 * Logger::log($level, $message [, array $context = array()])
 *
 * Logger::emergency($message [,array $context=null])
 * Logger::alert($message [,array $context=null])
 * Logger::critical($message [,array $context=null])
 * Logger::error($message [,array $context=null])
 * Logger::warning($message [,array $context=null])
 * Logger::warn($message [,array $context=null])
 * Logger::notice($message [,array $context=null])
 * Logger::info($message [,array $context=null])
 * Logger::debug($message [,array $context=null])
 */
class Logger
{
    /**
     * 日志监控回调,可以修改message和level
     *
     * @var callable
     */
    public static $listener = null;

    /**
     * 配置
     *
     * @var array
     */
    private static $_conf   = null;

    /**
     * 写入文件或者位置
     *
     * @var array
     */
    private static $_files  = null;

    /**
     * 写入日志
     *
     * @param string $msg   [消息]
     * @param string $level [日志级别]
     *
     * @return bool         [写入状态]
     */
    public static function write($msg, $level = 'NOTICE')
    {
        $level = strtoupper($level);
        if ($listener = &Logger::$listener) {
            //日志监控回调
            assert('is_callable($listener)');
            call_user_func_array($listener, array(&$level, &$msg));
        }

        if (!$config = &Logger::$_conf) {
            //读取配置信息
            $config          = Config::get('log')->toArray();
            $config['type']  = strtolower($config['type']);
            $config['allow'] = explode(',', strtoupper($config['allow']));
            isset($config['timezone']) && date_default_timezone_set($config['timezone']);
        }

        if (in_array($level, $config['allow'])) {
            switch ($config['type']) {
                case 'system': // 系统日志
                    return error_log($level.': '.$msg);

                case 'file': //文件日志
                    return file_put_contents(
                                Logger::getFile($level),
                                date('[d-M-Y H:i:s e] (').$_SERVER['REQUEST_URI'].') '.$msg.PHP_EOL,
                                FILE_APPEND);

                case 'sae': //sae日志
                    return sae_debug($level.': '.$msg);

                default:
                    throw new Exception('未知日志类型'.$config['type']);
            }
        }
    }

    /**
     * 清空日志(仅对文件模式有效)
     */
    public static function clear()
    {
        $type = Config::get('log.type');
        if ('file' === $type) {
            File::cleanDir(Config::get('runtime').DIRECTORY_SEPARATOR.'log'.DIRECTORY_SEPARATOR);
        } elseif ('system' == $type) {
            if ($file = ini_get('error_log')) {
                file_put_contents($file, '');
            }
        }
    }

    /**
     * System is unusable.
     *
     * @param string $message
     * @param array  $context
     *
     * @return bool [写入状态]
     */
    public static function emergency($message, array $context = array())
    {
        return static::log('EMERGENCY', $message, $context);
    }

    /**
     * Action must be taken immediately.
     *
     * Example: Entire website down, database unavailable, etc. This should
     * trigger the SMS alerts and wake you up.
     *
     * @param string $message
     * @param array  $context
     *
     * @return bool [写入状态]
     */
    public static function alert($message, array $context = null)
    {
        return static::log('ALERT', $message, $context);
    }

    /**
     * Critical conditions.
     *
     * Example: Application component unavailable, unexpected exception.
     *
     * @param string $message
     * @param array  $context
     *
     * @return bool [写入状态]
     */
    public static function critical($message, array $context = null)
    {
        return static::log('CRITICAL', $message, $context);
    }

    /**
     * Runtime errors that do not require immediate action but should typically
     * be logged and monitored.
     *
     * @param string $message
     * @param array  $context
     *
     * @return bool [写入状态]
     */
    public static function error($message, array $context = null)
    {
        return static::log('ERROR', $message, $context);
    }

    /**
     * Exceptional occurrences that are not errors.
     *
     * Example: Use of deprecated APIs, poor use of an API, undesirable things
     * that are not necessarily wrong.
     *
     * @param string $message
     * @param array  $context
     *
     * @return bool [写入状态]
     */
    public static function warning($message, array $context = null)
    {
        return static::log('WARN', $message, $context);
    }

    /**
     * Exceptional occurrences that are not errors.
     *
     * Example: Use of deprecated APIs, poor use of an API, undesirable things
     * that are not necessarily wrong.
     *
     * @param string $message
     * @param array  $context
     *
     * @return bool [写入状态]
     */
    public static function warn($message, array $context = null)
    {
        return static::log('WARN', $message, $context);
    }

    /**
     * Normal but significant events.
     *
     * @param string $message
     * @param array  $context
     *
     * @return null
     */
    public static function notice($message, array $context = null)
    {
        return static::log('NOTICE', $message, $context);
    }

    /**
     * Interesting events.
     *
     * Example: User logs in, SQL logs.
     *
     * @param string $message
     * @param array  $context
     *
     * @return null
     */
    public static function info($message, array $context = null)
    {
        return static::log('INFO', $message, $context);
    }

    /**
     * Detailed debug information.
     *
     * @param string $message
     * @param array  $context
     *
     * @return null
     */
    public static function debug($message, array $context = null)
    {
        return static::log('DEBUG', $message, $context);
    }

    /**
     * Logs with an arbitrary level.
     *
     * @param mixed  $level
     * @param string $message
     * @param array  $context
     *
     * @return null
     */
    public static function log($level, $message, array $context = null)
    {
        if ($context) {
            $replace = array();
            foreach ($context as $key => &$val) {
                $replace['{'.$key.'}']=is_scalar($val) || method_exists($val, '__toString') ? $val : json_endcode($val, 256);
            }
            $message = strtr($message, $replace);
        } elseif (!(is_scalar($message) || method_exists($message, '__toString'))) {
            //无法之间转成字符的数据json格式化
            $message = json_encode($message, 256); //256isJSON_UNESCAPED_UNICODE 兼容php5.3
        }
        return Logger::write($message, $level);
    }

    /**
     * 获取写入流
     *
     * @param string $tag [日志级别]
     *
     * @return string 写入的文件
     */
    private static function getFile($tag)
    {
        $files = &Logger::$_files;
        if (!isset($files[$tag])) {
            /*打开文件流*/
            if (!isset($files['_dir'])) {
                //日志目录
                umask(intval(Config::get('umask', 0077), 8));
                $logdir =isset(Logger::$_conf['path']) ? Logger::$_conf['path'] : Config::get('runtime').'log';
                if (!is_dir($logdir)) {
                    mkdir($logdir, 0777, true);
                }
                $files['_dir'] = $logdir.DIRECTORY_SEPARATOR.date('y-m-d-');

                //如果没有设置REQUEST_URI[命令行模式],自动补为null
                isset($_SERVER['REQUEST_URI']) || $_SERVER['REQUEST_URI'] = null;
            }

            $file        = $files['_dir'].$tag.'.log';
            $files[$tag] = $file;
        }
        return $files[$tag];
    }
}
