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
		$r = self::filter($input, $name, $export, $filter) OR ($export = $default);
		return $r;
	}

	/*put,get，post等输入过滤*/
	public static function put($name, &$export, $filter = null, $default = null)
	{
		($r = self::filter($GLOBALS['_PUT'], $name, $export, $filter)) OR ($export = $default);
		return $r;
	}

	public static function get($name, &$export, $filter = null, $default = null)
	{
		($r = self::filter($_GET, $name, $export, $filter)) OR ($export = $default);
		return $r;
	}

	public static function post($name, &$export, $filter = null, $default = null)
	{
		($r = self::filter($_POST, $name, $export, $filter)) OR ($export = $default);
		return $r;
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

			switch (gettype($filter))
			{
				case 'NULL':
				case NULL:	//无需过滤
					return true;

				case 'int'://整型常量
				case 'integer':

				/*系统过滤函数*/
					return $export = filter_var($export, $filter);

				case 'object':
				/*匿名回调函数*/
					$r = $filter($export);
					return $r ? ($export = $r) : false;

				case 'string':	//字符串
					if(strlen($filter)<1)
					{
						return $export;
					}
					elseif ($filter[0] == '/')
					{
					/*正则表达式验证*/
						return preg_match($filter, $export);
					}
					elseif (function_exists($filter))
					{
					/*已经定义的函数*/
						$r = $filter($export);
						//返回值不是true型的进行赋值（过滤），否则进行验证
						return $r ? (is_bool($r) OR $export = $r) : $export = $r;
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
					elseif ($filterid = filter_id($filter))
					{
					/*系统过滤函数*/
						return $export = filter_var($export, $filterid);
					}
					elseif ($regex = (string) Config::get('regex.' . $filter))
					{
					/*尝试配置正则*/
						return preg_match($regex, $export);
					}
				//继续往下走
				default:
					if (Config::get('debug'))
					{
						throw new Exception('未知过滤方法' . $filter);
					}
					return false;
			}
		}
		else
		{
			/*不存在*/
			return null;
		}
	}
}