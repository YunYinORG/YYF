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
 *Session seession管理
 *
 * @author NewFuture
 */
class Session
{
    private static $_id;

    /**
     * @param string $id session_id
     *
     * @return string [session id]
     */
    public static function start($id = null)
    {
        if (!$sid = self::$_id) {
            if (($sid = $id) || Input::I('SERVER.HTTP_SESSION_ID', $sid, 'ctype_alnum')) {
                session_id($sid);
                session_start();
            } else {
                session_start();
                $sid = session_id();
            }
            self::$_id = $sid;
        }
        return $sid;
    }

    /**
     * 设置session
     *
     * @param string $name  键值
     * @param mixed  $value 对应值
     */
    public static function set($name, $value)
    {
        self::start();
        return $_SESSION[$name] = $value;
    }

    /**
     * 读取
     *
     * @param string $name 键值
     */
    public static function get($name)
    {
        self::start();
        return isset($_SESSION[$name]) ? $_SESSION[$name] : null;
    }

    /**
     * 删除
     *
     * @param string $name 键值
     */
    public static function del($name)
    {
        self::start();
        unset($_SESSION[$name]);
    }

    /**
     * 清空session
     */
    public static function flush()
    {
        self::start();
        unset($_SESSION);
        session_unset();
        session_destroy();
        self::$_id = null;
    }
}
