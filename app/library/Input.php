<?php

/**
 * 输入过滤
 * 支持PUT GET POST 和 COOKIE , ENV ,SYSTEM
 * PUT 仅对 PUT 有些
 */
class Input
{
	/**
	 * 输入过滤
	 * @method I
	 * @param  [string] $param   [输入参数]
	 * @param  [mixed] &$export [description]
	 * @param  [mixed] $filter  [过滤条件]
	 * @param  [type] $default [description]
	 * @author NewFuture
	 * @example if(I('post.phone',$phone,'phone')){}//phone()方法验证
	 * @example if(I('get.id',$uid,'int',1)){}//数字，int函数验证,默认1
	 * @example if(I('put.text',$uid,'/^\w{5,50}$/'){}//正则验证
	 * @example if(I('cookie.token',$uid,'token'){}//使用配置中的regex.token的值进行验证
	 */
	public static function I($param, &$export, $filter = null, $default = null)
	{
		if (strpos($param, '.'))
		{
			list($method, $name) = explode('.', $param, 2);
			/*PUT请求已在REST中注入到$GLOBAL中*/
			$method = '_' . strtoupper($method);
			$input  = &$GLOBALS[$method];
		}
		else
		{
			// 默认为自动判断
			$input = &$_REQUEST;
			$name  = $param;
		}
		return self::filter($input, $name, $export, $filter) || ($export = $default);
	}

	/*put,get，post等输入过滤*/
	public static function put($name, &$export, $filter = null, $default = null)
	{
		return self::filter($GLOBALS['_PUT'], $name, $export, $filter) || ($export = $default);
	}

	public static function get($name, &$export, $filter = null, $default = null)
	{
		return self::filter($_GET, $name, $export, $filter) || ($export = $default);
	}

	public static function post($name, &$export, $filter = null, $default = null)
	{
		return self::filter($_POST, $name, $export, $filter) || ($export = $default);
	}

	/**
	 * 过滤器
	 * @method filter
	 * @param  [string] &$input  [输入参数]
	 * @param  [mixed] &$index  [description]
	 * @param  [mixed] &$export [description]
	 * @param  [mixed] $filter  [过滤条件]
	 * @return [bool]          [description]
	 * @author NewFuture
	 */
	private static function filter(&$input, &$index, &$export, $filter)
	{
		if (isset($input[$index]))
		{
			$export = $input[$index];
			if (empty($filter))
			{
				/*不用过滤*/
				return true;
			}
			elseif (is_int($filter))
			{
				/*使用PHP自带的的过滤器*/
				return (bool) $export = filter_var($export, $filter);
			}
			elseif ($filter[0] == '/')
			{
				/*正则表达式验证*/
				return preg_match($filter, $export);
			}
			elseif (method_exists('Parse\Filter', $filter))
			{
				/*过滤器过滤*/
				return (bool) $export = call_user_func_array(array('Parse\Filter', $filter), [$export]);
			}
			elseif (method_exists('Validate', $filter))
			{
				/*Validate方法验证*/
				return call_user_func_array(array('Validate', $filter), [$export]);
			}
			elseif (function_exists($filter))
			{
				/*函数*/
				return $filter($export);
			}
			elseif ($filter = (string) Config::get('regex.' . $filter))
			{
				/*尝试配置正则*/
				return preg_match($filter, $export);
			}
			else
			{
				throw new Exception('未知过方法' . $filter);
			}
		}
		else
		{
			/*不存在*/
			return false;
		}
	}
}