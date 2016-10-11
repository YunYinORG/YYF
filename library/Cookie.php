<?php
/**
 * 安全cookie
 * 对cookie存取进行加密
 */
class Cookie
{
	private static $_config = null; //配置
	/**
	 * 设置cookie
	 * @method set
	 * @param  [string] $name   [cookie名称]
	 * @param  [mixed] $value  [cookie值]
	 * @param  [string] $path   [存取路径]
	 * @param  [int] $expire 有效时间
	 * @author NewFuture
	 */
	public static function set($name, $value, $path = '', $expire = null, $domain = null)
	{
		if ($value = self::encode($value))
		{
			$path = $path ?: self::config('path');

			if (!$expire)
			{
				$expire = ($expire === 0) ? null : self::config('expire');
			}
			$expire = $expire ? $_SERVER['REQUEST_TIME'] + $expire : null;
			$domain = $domain === null ? self::config('domain') : $domain;
			return setcookie($name, $value, $expire, $path, $domain, self::config('secure'), self::config('httponly'));
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
		if (isset($_COOKIE[$name]) && $data = $_COOKIE[$name])
		{
			return self::decode($data);
		}
	}

	/**
	 * 删除
	 * @method del
	 * @param  [string] $name [cookie名称]
	 * @author NewFuture
	 */
	public static function del($name, $path = null, $domain = null)
	{
		if (isset($_COOKIE[$name]))
		{
			unset($_COOKIE[$name]);
			$path   = $path ?: self::config('path');
			$domain = $domain === null ? self::config('domain') : $domain;
			setcookie($name, '', 100, $path, $domain, self::config('secure'), self::config('httponly'));
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
		else
		{
			/*逐个删除*/
			foreach ($_COOKIE as $key => $val)
			{
				self::del($key);
			}
		}

	}

	/**
	 * Cookie数据加密编码
	 * @method encode
	 * @param  [type] $data         [description]
	 * @return [type] [description]
	 * @author NewFuture
	 */
	private static function encode($data)
	{
		return Encrypt::aesEncode(json_encode($data), self::config('key'), true);
	}

	/**
	 * Cookie数据解密
	 * @method encode
	 * @param  [type] $data         [description]
	 * @return [type] [description]
	 * @author NewFuture
	 */
	private static function decode($data)
	{
		if ($data = Encrypt::aesDecode($data, self::config('key'), true))
		{
			return @json_decode($data, true);
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
			$config = Config::get('cookie')->toArray();

			$config['key'] = self::key();
			self::$_config = $config;
		}
		return isset($config[$name]) ? $config[$name] : null;
	}

	/**
	 * 获取加密密钥
	 * @method key
	 * @return [type] [description]
	 * @author NewFuture
	 */
	public static function key()
	{
		if (!$key = Kv::get('COOKIE_aes_key'))
		{
			/*重新生成加密密钥*/
			$key = Random::word(32);
			Kv::set('COOKIE_aes_key', $key);
		}
		return $key;
	}
}