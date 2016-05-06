<?php
/**
 * 键值对存储
 * Function list:
 * - set()
 * - get()
 * - del()
 * - flush()
 */
class Kv
{

	private static $_handler = null; //处理方式

	/**
	 * 设置缓存
	 * @method set
	 * @param  [string]  $name   [缓存名称]
	 * @param  [mixed]  $value  [缓存值]
	 * @param  mixed $expire [有效时间]
	 * @author NewFuture
	 */
	public static function set($name, $value)
	{
		return self::Handler()->set($name, $value);
	}

	/**
	 * 读取缓存数据
	 * @method get
	 * @param  [string] $name [缓存名称]
	 * @return [mixed]       [获取值]
	 * @author NewFuture
	 */
	public static function get($name)
	{
		return self::Handler()->get($name);
	}

	/**
	 * 删除缓存数据
	 * @method del
	 * @param  [string] $name [缓存名称]
	 * @return [bool]
	 * @author NewFuture
	 */
	public static function del($name)
	{
		return self::Handler()->delete($name);
	}

	/**
	 * 清空存储
	 * @method fush
	 * @author NewFuture
	 */
	public static function flush()
	{
		if (Config::get('kv.type') == 'sae')
		{
			/*sae kvdb 逐个删除*/
			$kv = self::Handler();
			while ($ret = $kv->pkrget('', 100))
			{
				foreach ($ret as $k => $v)
				{
					$kv->delete(key($k));
				}
			}
		}
		else
		{
			return self::Handler()->flush();
		}

	}

	/**
	 * 获取处理方式
	 * @return $_handler
	 * @author NewFuture
	 */
	protected static function Handler()
	{
		if (null === self::$_handler)
		{
			switch (Config::get('kv.type'))
			{
				case 'sae':	//sae_memcache
					self::$_handler = memcache_init();
					break;
				case 'file':	//文件缓存
					self::$_handler = new Storage\File(Config::get('tempdir') . 'kv', false);
					break;

				default:
					throw new Exception('未定义方式' . Config::get('kv.type'));
			}
		}
		return self::$_handler;
	}
}
