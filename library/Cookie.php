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
 * Cookie 加密cookie管理
 *
 * @author NewFuture
 */
class Cookie
{
    /**
     * 配置
     *
     *@var array
     */
    private static $_config = null;

    private function __construct()
    {
        self::$_config        = Config::get('cookie')->toArray();
        self::$_config['key'] = self::key();
    }

    /**
     * 设置cookie
     *
     * @param string      $name   cookie名称
     * @param mixed       $value  cookie值
     * @param string      $path   [存取路径]
     * @param int         $expire 有效时间
     * @param null|string $domain 域名
     */
    public static function set($name, $value, $path = '', $expire = null, $domain = null)
    {
        if ($value = self::encode($value)) {
            $path = $path ?: self::config('path');

            if (!$expire) {
                $expire = ($expire === 0) ? null : self::config('expire');
            }
            $expire = $expire ? $_SERVER['REQUEST_TIME'] + $expire : null;
            if ($domain === null) {
                $domain = self::config('domain');
            }
            return setrawcookie($name, $value, $expire, $path, $domain, self::config('secure'), self::config('httponly'));
        }
    }

    /**
     * 获取cookie
     *
     * @param string $name    cookie名称
     * @param mixed  $default 默认值
     *
     * @return mixed string|array
     */
    public static function get($name, $default = null)
    {
        if (isset($_COOKIE[$name]) && $data = $_COOKIE[$name]) {
            return self::decode($data);
        }
        return $default;
    }

    /**
     * 删除
     *
     * @param string      $name   cookie名称
     * @param null|string $path   路径
     * @param null|string $domain 域名
     */
    public static function del($name, $path = null, $domain = null)
    {
        if (isset($_COOKIE[$name])) {
            unset($_COOKIE[$name]);
            $path   = $path ?: self::config('path');
            $domain = $domain === null ? self::config('domain') : $domain;
            setrawcookie($name, '', 100, $path, $domain, self::config('secure'), self::config('httponly'));
        }
    }

    /**
     * 清空cookie
     */
    public static function flush()
    {
        if (empty($_COOKIE)) {
            return null;
        }
        /*逐个删除*/
        foreach ($_COOKIE as $key => $val) {
            self::del($key);
        }
    }

    /**
     * 获取加密密钥
     *
     * @return string 密钥
     */
    public static function key()
    {
        if (!$key = Kv::get('COOKIE_aes_key')) {
            /*重新生成加密密钥*/
            $key = Random::word(32);
            Kv::set('COOKIE_aes_key', $key);
        }
        return $key;
    }

    /**
     * 设置cookie配置
     *
     * @param string $key   配置变量名
     * @param mixed  $value 配置值
     *
     * @return Cookie self 设置
     */
    public static function setConfig($key, $value)
    {
        //修改配置参数
        $config[$key] = $value;
        return self;
    }

    /**
     * Cookie数据加密编码
     *
     * @param mixed $data 数据
     *
     * @return string
     */
    private static function encode($data)
    {
        return Encrypt::aesEncode(json_encode($data), self::config('key'), true);
    }

    /**
     * Cookie数据解密
     *
     * @param string $data 待解密字符串
     *
     * @return mixed
     */
    private static function decode($data)
    {
        if ($data = Encrypt::aesDecode($data, self::config('key'), true)) {
            return @json_decode($data, true);
        }
    }

    /**
     * 获取cookie配置
     *
     * @param string $name 配置变量名
     *
     * @return mixed 配置项
     */
    private function config($name)
    {
        if (!$config = self::$_config) {
            $config = Config::get('cookie')->toArray();

            $config['key'] = self::key();
            self::$_config = $config;
        }
        return isset($config[$name]) ? $config[$name] : null;
    }
}
