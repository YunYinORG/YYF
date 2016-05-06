<?php
namespace Parse;
/**
 * 字符串过滤
 */
class Filter
{
	public static function int($int)
	{
		return intval($int);
	}

	/*标题过滤*/
	public static function title($str)
	{
		return filter_var($str, FILTER_SANITIZE_SPECIAL_CHARS, FILTER_FLAG_STRIP_LOW);
	}

	/*富文本过滤*/
	public static function text($str)
	{
		return filter_var($str, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_ENCODE_AMP);
	}

	/*标签字符过滤*/
	public static function tag($str)
	{
		$str = filter_var(trim($str), FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW);
		return (strpbrk($str, '<>&/\\%|{} ,;　，；、') === false) ? $str : false;
	}
}