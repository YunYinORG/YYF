<?php
/**
 * 对应用配置的封装，方便读取
 * Config::get('config')
 */
class Config
{
    private static $_config = null;
    private static $_secret = null;

    /**
     * 获取配置
     * @method get
     * @param  [string]	$key     [键值]
     * @param  [type] 	$default [默认值]
     * @return [mixed]         	 [返回结果]
     * @author NewFuture
     */
    public static function get($key = null, $default = null)
    {
        if (!$config=&Config::$_config) {
            $config=Yaf_Application::app()->getConfig();
        }
        $value = $config -> get($key);
        return null===$value ? $default : $value;
    }

    /**
     * 获取私密配置
     * @method secret
     * @param  [string] $name     [配置名]
     * @param  [string] $key 		[键值]
     * @return [mixed]          [结果]
     * @author NewFuture
     * @example
     *  Config::getSecrect('encrypt') 获取取私密配置中的encrypt所有配置
     *  Config::getSecrect('encrypt'，'key') 获取取私密配置中的encrypt配置的secret值
     */
    public static function getSecret($name = '', $key = null)
    {
        if (!$secret=&Config::$_secret) {
            $secret = new Yaf_Config_Ini(Config::get('secret_config_path'));
        }
        return $key?$secret->get($name)->get($key):$secret->get($name);
    }
}
