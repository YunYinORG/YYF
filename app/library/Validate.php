<?php
/**
 * 数据格式验证
 * - email()
 * - phone()
 * Classes list:
 * - Validate
 */
class Validate
{

	/**
	 * 验证邮箱格式
	 * @method email
	 * @param  [type]  &$email      [description]
	 * @param  boolean $ignore_mx 	[是否忽略对mx记录的检查]
	 * @return [boolean]           	[验证结果]
	 * @author NewFuture
	 * @todo 邮箱存在性验证 https://github.com/zytzagoo/smtp-validate-email
	 */
	public static function email(&$email, $ignore_mx = false)
	{
		return preg_match(Config::get('regex.email'), $email)
		&& ($ignore_mx || checkdnsrr(substr(strrchr($email, '@'), 1)));
	}

	/*验证手机格式*/
	public static function phone(&$phone)
	{
		return preg_match(Config::get('regex.phone'), $phone);
	}

	/*验证账号格式*/
	public static function account(&$account)
	{
		return preg_match(Config::get('regex.account'), $account);
	}

	/*验证姓名格式*/
	public static function name(&$name)
	{
		return preg_match(Config::get('regex.name'), $name);
	}

	/*验证字符串是否仅由字母，_，数字线组成*/
	public static function char_num($str)
	{
		return ctype_alnum(strtr($str, '_', 'A'));
	}


	/*验证字符串是否仅由字母，_，数字线组成*/
	public static function fileName($str)
	{
		return ctype_alnum(strtr($str,'_.','AA'));
	}

	/*md5后的字符串*/
	public static function isMD5($md5pwd)
	{
		return (strlen($md5pwd) == 32) && ctype_alnum($md5pwd);
	}

	/**
	 * 验证字符串是否安全含有不安全字符
	 * @todo 过于简单暴力
	 * @method safeChar
	 * @param  [type]   $str [description]
	 * @return [type]        [description]
	 * @author NewFuture
	 */
	public static function safeChar($str)
	{
		return strpbrk($str, '<>&#\\%') === false;
	}
}