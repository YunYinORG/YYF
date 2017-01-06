<?php
/**
 * YYF - A simple, secure, and high performance PHP RESTful Framework.
 *
 * @see https://github.com/YunYinORG/YYF/
 *
 * @license Apache2.0
 * @copyright 2015-2017 NewFuture@yunyin.org
 */

namespace Debug;

use Debug as Debug;
use Logger as Logger;

/**
 * 监视日志写入记录.
 *
 * @author NewFuture
 */
class LogListener
{
    /**
     * 日志过滤标记，日志级别加上此后缀不做监听.
     */
    const LOG_SUFFIX = '.LISTENER';

    protected static $listen_log_type = [];

    public static function init($type)
    {
        if ('*' === $type) {
            static::$listen_log_type = '*';
        } else {
            static::$listen_log_type = explode(',', strtoupper($type));
        }
        Logger::$listener = [__CLASS__, 'listener'];
    }

    public static function safeType($level)
    {
        if (static::$listen_log_type === '*'
        || in_array($level, static::$listen_log_type)) {
            return $level.static::LOG_SUFFIX;
        }

        return $level;
    }

    /**
     * 日志监听回调.
     *
     * @param callable $level   日志级别
     * @param string   $message 日志消息
     */
    public static function listener(&$level, &$message)
    {
        if ($p = strpos($level, static::LOG_SUFFIX)) {
            //Debug 不监听日志
            $level = substr($level, 0, $p);
        } elseif (static::$listen_log_type === '*' || in_array($level, static::$listen_log_type)) {
            Debug::header()->debugInfo($level, $message);
        }
    }
}
