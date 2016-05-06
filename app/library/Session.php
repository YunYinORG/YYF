<?php
/**
 * Session操作
 */
class Session
{

	private static $_id;

	/**
	 * @method start
	 * @return string [session id]
	 * @author NewFuture
	 */
	public static function start($id = null)
	{
		if (!$sid = self::$_id)
		{
			if (($sid = $id) || Input::I('SERVER.HTTP_SESSION_ID', $sid, 'ctype_alnum'))
			{
				session_id($sid);
				session_start();
			}
			else
			{
				session_start();
				$sid = session_id();
			}
			self::$_id = $sid;
		}
		return $sid;
	}

	/**
	 * 设置session
	 * @method set
	 * @param  [string] $name   [description]
	 * @param  [mixed] $value  [description]
	 */
	public static function set($name, $value)
	{
		self::start();
		return $_SESSION[$name] = $value;
	}

	/**
	 * 读取
	 * @method get
	 * @param  [string] $name [description]
	 */
	public static function get($name)
	{
		self::start();
		return isset($_SESSION[$name]) ? $_SESSION[$name] : null;
	}

	/**
	 * 删除
	 * @method del
	 * @param  [string] $name [description]
	 */
	public static function del($name)
	{
		self::start();
		unset($_SESSION[$name]);
	}

	/*清空session*/
	public static function flush()
	{
		self::start();
		unset($_SESSION);
		session_unset();
		session_destroy();
		self::$_id = null;
	}
}