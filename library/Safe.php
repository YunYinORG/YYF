<?php
/**
 * YYF - A simple, secure, and high performance PHP RESTful Framework.
 *
 * @link https://github.com/YunYinORG/YYF/
 *
 * @license Apache2.0
 * @copyright 2015-2017 NewFuture@yunyin.org
 */

/**
 * Safe 安全验证
 *
 * @author NewFuture
 *
 * @todo 调整接口
 */
class Safe
{
    /**
     * 检查尝试次数是否超限
     *
     * @param string $key        [标识KEY]
     * @param int    $timesLimit 次数
     *
     * @return bool 是否有效
     */
    public static function checkTry($key, $timesLimit = 0)
    {
        $name       = 's_t_'.$key;
        $times      = intval(Cache::get($name));
        $timesLimit = intval($timesLimit) ?: intval(Config::get('try.times'));
        if ($times >= $timesLimit) {
            $msg = '多次尝试警告:'.$key.'IP信息:'.self::ip();
            Logger::write($msg, 'WARN');
            return false;
        }

        Cache::set($name, ++$times, Config::get('try.expire'));
        return $times;
    }

    public static function del($key)
    {
        Cache::del('s_t_'.$key);
    }

    public static function ip()
    {
        $request_ip = getenv('REMOTE_ADDR');
        $orign_ip   = getenv('HTTP_X_FORWARDED_FOR') ?: getenv('HTTP_CLIENT_IP');
        return $request_ip.'[client：'.$orign_ip.']';
    }
}
