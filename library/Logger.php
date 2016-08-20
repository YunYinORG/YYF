<?php
use Storage\File as File;

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
    /**
     * 日志监控回调,可以修改message和level
     * @var callable
     */
    public static $listener = null;
    /**
     * 日志和日志目录权限,默认只允许php读写
     * @var callable
     */
    public static $mode     = 0700;

    private static $_conf   = null;
    private static $_files  = null;
    
    /**
     * 写入日志
     * @method write
     * @param  string  $msg   [消息]
     * @param  [string] $level [日志级别]
     * @return [bool]         [写入状态]
     * @author NewFuture
     */
    public static function write($msg, $level = 'NOTICE')
    {
        $level=strtoupper($level);
        if ($listener=&Logger::$listener) {
            //日志监控回调
            assert('is_callable($listener)', '[Logger::$listener] 应该是可执行的回调函数');
            call_user_func_array($listener, array(&$level, &$msg));
        }

        if (!$config=&Logger::$_conf) {
            //读取配置信息
            $config=Config::get('log')->toArray();
            $config['type']  = strtolower($config['type']);
            $config['allow'] = explode(',', strtoupper($config['allow']));
        }

        if (in_array($level, $config['allow'])) {
            switch ($config['type']) {
                case 'system':// 系统日志
                    return error_log('['.$level .']:'. $msg);
                case 'sae'://sae日志
                    return sae_debug('['.$level .']:'. $msg);
                case 'file'://文件日志
                    $msg='[' . date('c') . '] ' . $msg . PHP_EOL;
                    return file_put_contents(Logger::getFile($level), $msg, FILE_APPEND);
                default:
                    throw new Exception('未知日志类型' . $config['type']);
            }
        }
    }

    /**
    * 清空日志(仅对文件模式有效)
    */
    public static function clear()
    {
        if ('file'=== Config::get('log.type')) {
            File::cleanDir(Config::get('runtime').DIRECTORY_SEPARATOR.'log'.DIRECTORY_SEPARATOR);
        }
    }

    /**
     * 获取写入流
     * @method getFile
     * @param  [string]    $tag [日志级别]
     * @return [array]           [description]
     * @author NewFuture
     */
    private static function getFile($tag)
    {
        $files=&Logger::$_files;
        if (!isset($files[$tag])) {
            /*打开文件流*/
            if (!isset($files['_dir'])) {
                //日志目录
                $logdir = Config::get('runtime').DIRECTORY_SEPARATOR.'log';
                if (!is_dir($logdir)) {
                    $oldmask=umask(0);
                    mkdir($logdir, Logger::$mode, true);
                    umask($oldmask);
                }
                date_default_timezone_set('PRC');
                $files['_dir']=$logdir.DIRECTORY_SEPARATOR . date('y-m-d-');
            }

            $file =  $files['_dir'] . $tag . '.log';
            //检查权限
            if (($mode= @fileperms($file))&&(($mode&Logger::$mode) !== Logger::$mode)) {
                chmod($file, Logger::$mode);
                clearstatcache();
            }
            $files[$tag] = $file ;
        }
        return $files[$tag];
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
        return static::log('EMERGENCY', $message, $context);
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
        return static::log('ALERT', $message, $context);
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
        return static::log('CRITICAL', $message, $context);
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
        return static::log('ERROR', $message, $context);
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
        return static::log('WARN', $message, $context);
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
        return static::log('WARN', $message, $context);
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
        return static::log('NOTICE', $message, $context);
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
        return static::log('INFO', $message, $context);
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
        return static::log('DEBUG', $message, $context);
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
        return Logger::write($message, $level);
    }
}
