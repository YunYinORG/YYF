<?php
/**
 * 安全cookie
 * 对cookie存取进行加密
 */
class Cookie
{
	private static $_config = null;

	/**
	 * 设置cookie
	 * @method set
	 * @param  [string] $name   [cookie名称]
	 * @param  [mixed] $value  [cookie值]
	 * @param  [string] $path   [存取路径]
	 * @param  [int] $expire 有效时间
	 * @author NewFuture
	 */
	public static function set($name, $value, $path = '', $expire = null)
	{
		if ($value = Encrypt::aesEncode(json_encode($value), self::config('key'), true))
		{
			$path   = $path ?: self::config('path');
			$expire = $expire ? ($_SERVER['REQUEST_TIME'] + $expire) : null;
			return setcookie($name, $value, $expire, $path, self::config('domain'), self::config('secure'), self::config('httponly'));
		}
	}

	/**
	 * 获取cookie
	 * @method get
	 * @param  [string] $name [cookie名称]
	 * @return [json]
	 * @author NewFuture
	 */
	public static function get($name)
	{
		if (isset($_COOKIE[$name]) && $data = Encrypt::aesDecode($_COOKIE[$name], self::config('key'), true)) //AES解密
		{
			return @json_decode($data);
		}
	}

	/**
	 * 删除
	 * @method del
	 * @param  [string] $name [cookie名称]
	 * @author NewFuture
	 */
	public static function del($name, $path = null)
	{
		if (isset($_COOKIE[$name]))
		{
			unset($_COOKIE[$name]);
			$path = $path ?: self::config('path');
			return setcookie($name, '', 100, $path, self::config('domain'), self::config('secure'), self::config('httponly'));
		}
	}

	/**
	 * 清空cookie
	 */
	public static function flush()
	{
		if (empty($_COOKIE))
		{
			return null;
		}
		/*逐个删除*/
		foreach ($_COOKIE as $key => $val)
		{
			self::del($key);
		}
	}

	/**
	 * 获取cookie配置
	 * @method config
	 * @param  [string] $name [配置变量名]
	 * @return [mixed]       [description]
	 * @author NewFuture
	 */
	private static function config($name)
	{
		if (!$config = self::$_config)
		{
			$config = Config::get('cookie');
			if (!$key = Kv::get('COOKIE_aes_key'))
			{
				/*重新生成加密密钥*/
				$key = Random::word(32);
				Kv::set('COOKIE_aes_key', $key);
			}
			$config['key'] = $key;
			self::$_config = $config;
		}
		return isset($config[$name]) ? $config[$name] : null;
	}
}