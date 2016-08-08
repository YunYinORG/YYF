<?php
/**
 * 安全防护
 */
Class Safe
{

	/**
	 * 检查尝试次数是否超限
	 * @method checkTry
	 * @param  [type]   $key        [description]
	 * @param  integer  $timesLimit [description]
	 * @return [type]               [description]
	 * @author NewFuture
	 */
	public static function checkTry($key, $timesLimit = 0)
	{
		$name       = 's_t_' . $key;
		$times      = intval(Cache::get($name));
		$timesLimit = intval($timesLimit) ?: intval(Config::get('try.times'));
		if ($times >= $timesLimit)
		{
			$msg = '多次尝试警告:' . $key . 'IP信息:' . self::ip();
			Logger::write($msg, 'WARN');
			return false;
		}
		else
		{
			Cache::set($name, ++$times, Config::get('try.expire'));
			return $times;
		}
	}

	public static function del($key)
	{
		Cache::del('s_t_' . $key);
	}

	public static function ip()
	{
		$request_ip = getenv('REMOTE_ADDR');
		$orign_ip   = getenv('HTTP_X_FORWARDED_FOR') ?: getenv('HTTP_CLIENT_IP');
		return $request_ip . '[client：' . $orign_ip . ']';
	}
}