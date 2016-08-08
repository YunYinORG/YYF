<?php
/**
* 日志记录
* 遵循 https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-3-logger-interface.md
* Logger::write($msg, $level = 'NOTICE')//快速写入
* Logger::log($level, $message [, array $context = array()])
*
* Logger::emergency($message [,$context])
* Logger::alert($message [,$context])
* Logger::critical($message [,$context])
* Logger::error($message [,$context])
* Logger::warning($message [,$context])
* Logger::warn($message [,$context])
* Logger::notice($message [,$context])
* Logger::info($message [,$context])
* Logger::debug($message [,$context])
 */
class Logger
{
    private static $_conf   = null;
    private static $_stream = null;
    private static $_dir    = null;
    /**
     * 写入日志
     * @method write
     * @param  [string]  $msg   [消息]
     * @param  [integer] $level [日志级别]
     * @return [bool]         [写入状态]
     * @author NewFuture
     */
    public static function write($msg, $level = 'NOTICE')
    {
        if (!$config=&self::$_conf) {
            //读取配置信息
            $config=Config::get('log');
            $config['type']  = strtolower($config['type']);
            $config['allow'] = explode(',', strtoupper($config['allow']));
        }
        $level=strtoupper($level);
        if (in_array($level, $config['allow'])) {
            switch ($config['type']) {
                case 'system':// 系统日志
                    return error_log($level.':'.$msg);
                case 'sae'://sae日志
                    return sae_debug($level .':'. $msg);
                case 'file'://文件日志
                    return fwrite(self::getStream($level), '[' . date('c') . '] ' . $msg . PHP_EOL);
                default:
                    throw new Exception('未知日志类型' . $config['type']);
                    break;
            }
        }
    }

    /**
     * 获取写入流
     * @method getStream
     * @param  [integer]    $tag [日志级别]
     * @return [array]           [description]
     * @author NewFuture
     */
    private static function getStream($tag)
    {
        if (!isset(self::$_stream[$tag])) {
            /*打开文件流*/
            if (!$logdir=&self::$_dir) {
                //日志目录
                $logdir = Config::get('tempdir').DIRECTORY_SEPARATOR.'log';
                if (is_dir($logdir)||mkdir($logdir)) {
                    date_default_timezone_set('PRC');
                } else {
                    throw new Exception('目录文件无法创建' . $logdir, 1);
                    return;
                }
            }
            //打开日志文件
            $file = $logdir . DIRECTORY_SEPARATOR . $tag . '.log';
            if (!self::$_stream[$tag] = fopen($file, 'a')) {
                throw new Exception('Cannot open to log file: ' . $file);
            }
        }
        return self::$_stream[$tag];
    }


    /**
     * System is unusable.
     *
     * @param string $message
     * @param array $context
     * @return boolean [写入状态]
     */
    public static function emergency($message, array $context = array())
    {
        return self::log('EMERGENCY', $message, $context);
    }

    /**
     * Action must be taken immediately.
     *
     * Example: Entire website down, database unavailable, etc. This should
     * trigger the SMS alerts and wake you up.
     *
     * @param string $message
     * @param array $context
     * @return boolean [写入状态]
     */
    public static function alert($message, array $context = array())
    {
        return self::log('ALERT', $message, $context);
    }

    /**
     * Critical conditions.
     *
     * Example: Application component unavailable, unexpected exception.
     *
     * @param string $message
     * @param array $context
     * @return boolean [写入状态]
     */
    public static function critical($message, array $context = array())
    {
        return self::log('CRITICAL', $message, $context);
    }

    /**
     * Runtime errors that do not require immediate action but should typically
     * be logged and monitored.
     *
     * @param string $message
     * @param array $context
     * @return boolean [写入状态]
     */
    public static function error($message, array $context = array())
    {
        return self::log('ERROR', $message, $context);
    }

    /**
     * Exceptional occurrences that are not errors.
     *
     * Example: Use of deprecated APIs, poor use of an API, undesirable things
     * that are not necessarily wrong.
     *
     * @param string $message
     * @param array $context
     * @return boolean [写入状态]
     */
    public static function warning($message, array $context = array())
    {
        return self::log('WARN', $message, $context);
    }

    /**
     * Exceptional occurrences that are not errors.
     *
     * Example: Use of deprecated APIs, poor use of an API, undesirable things
     * that are not necessarily wrong.
     *
     * @param string $message
     * @param array $context
     * @return boolean [写入状态]
     */
    public static function warn($message, array $context = array())
    {
        return self::log('WARN', $message, $context);
    }
    /**
     * Normal but significant events.
     *
     * @param string $message
     * @param array $context
     * @return null
     */
    public static function notice($message, array $context = array())
    {
        return self::log('NOTICE', $message, $context);
    }

    /**
     * Interesting events.
     *
     * Example: User logs in, SQL logs.
     *
     * @param string $message
     * @param array $context
     * @return null
     */
    public static function info($message, array $context = array())
    {
        return self::log('INFO', $message, $context);
    }

    /**
     * Detailed debug information.
     *
     * @param string $message
     * @param array $context
     * @return null
     */
    public static function debug($message, array $context = array())
    {
        return self::log('DEBUG', $message, $context);
    }

    /**
     * Logs with an arbitrary level.
     *
     * @param mixed $level
     * @param string $message
     * @param array $context
     * @return null
     */
    public static function log($level, $message, array $context = array())
    {
        if (is_string($message)) {
            $replace = array();
            foreach ($context as $key => &$val) {
                if (!is_array($val) && (!is_object($val) || method_exists($val, '__toString'))) {
                    $replace['{' . $key . '}'] = $val;
                }
            }
            $message=strtr($message, $replace);
        } else {
            $message=json_encode($mesage, JSON_UNESCAPED_UNICODE);
        }
        return self::write($message, $level);
    }
}
