<?php
/**
 * 安全cookie
 * 对cookie存取进行加密
 */
class Session
{
	/**
	 * 设置session
	 * @method set
	 * @param  [string] $name   [description]
	 * @param  [mixed] $value  [description]
	 */
	public static function set($name, $value)
	{
		return Yaf_Session::getInstance()->set($name, $value);
	}

	/**
	 * 读取
	 * @method get
	 * @param  [string] $name [description]
	 */
	public static function get($name)
	{
		return Yaf_Session::getInstance()->get($name);
	}

	/**
	 * 删除
	 * @method del
	 * @param  [string] $name [description]
	 */
	public static function del($name)
	{
		return Yaf_Session::getInstance()->del($name);
	}

	/*清空session*/
	public static function flush()
	{
		session_unset();
		unset($_COOKIE);
	}
}