<?php
/**
 * YYF - A simple, secure, and high performance PHP RESTful Framework.
 *
 * @link https://github.com/YunYinORG/YYF/
 *
 * @license Apache2.0
 * @copyright 2015-2017 NewFuture@yunyin.org
 */

use \Yaf_Application as Application;
use \Yaf_Config_Ini as Ini;

/**
 * Config 对应用配置的封装，方便读取
 * Config::get('config')
 *
 * @author NewFuture
 */
class Config
{
    private static $_config = null;
    private static $_secret = null;

    /**
     * 获取配置
     *
     * @param string $key     键值
     * @param type   $default [默认值]
     *
     * @return mixed [返回结果]
     */
    public static function get($key, $default = null)
    {
        if (!$config = &Config::$_config) {
            $config = Application::app()->getConfig();
        }
        $value = $config->get($key);
        return null === $value ? $default : $value;
    }

    /**
     * 获取私密配置
     *
     * @param string $name 配置名
     * @param string $key [键]
     *
     * @return mixed 结果
     *
     * @example
     *  Config::getSecrect('encrypt') 获取取私密配置中的encrypt所有配置
     *  Config::getSecrect('encrypt'，'key') 获取取私密配置中的encrypt配置的secret值
     */
    public static function getSecret($name, $key = null)
    {
        if (!$secret = &Config::$_secret) {
            $secret = new Ini(Config::get('secret_path'));
        }
        return $key ? $secret->get($name)->get($key) : $secret->get($name);
    }
}
