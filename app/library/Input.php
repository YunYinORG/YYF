<?php
/**
 * 输入过滤
 * 支持PUT GET POST 和 COOKIE , ENV ,SYSTEM
 * PUT 仅对 REST 路由有效
 */
class Input
{
	/**
	 * 输入过滤
	 * @method I
	 * @param  [string] $param   [输入参数]
	 * @param  [mixed] &$export [变量输出]
	 * @param  [mixed] $filter  [过滤条件，支持：正则表达式，函数名，回调函数，和filter_var]
	 * @param  [type] $default [默认值]
	 * @return bool|null
	 *         			null表示变量不存在，
	 *         			false表示格式验证失败，
	 * @author NewFuture
	 * @example I('get.id',$uid,'int',1){}//数字，,默认1
	 * @example if(I('put.text',$uid,'/^\w{5,50}$/'){}//正则验证
	 * @example if(I('cookie.token',$uid,'token'){}//
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

	/*put输入过滤*/
	public static function put($name, &$export, $filter = null, $default = null)
	{
		isset($GLOBALS['_PUT']) OR parse_str(file_get_contents('php://input'), $GLOBALS['_PUT']);
		($r = self::filter($GLOBALS['_PUT'], $name, $export, $filter)) OR ($export = $default);
		return $r;
	}

	/*get等输入过滤*/
	public static function get($name, &$export, $filter = null, $default = null)
	{
		($r = self::filter($_GET, $name, $export, $filter)) OR ($export = $default);
		return $r;
	}

	/*post等输入过滤*/
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
				case NULL://无需过滤
				case 'NULL':
					return true;

				case 'int':	//整型常量
				/*系统过滤函数*/
					return $export = filter_var($export, $filter);

				case 'object':
				/*匿名回调函数*/
					$r = $filter($export);
					return $r ? ($export = $r) : false;

				case 'string':	//字符串
					if ($filter[0] == '/')
					{
					/*正则表达式验证*/
						return preg_match($filter, $export);
					}
					elseif (function_exists($filter))
					{
					/*已经定义的函数*/
						return $export = $filter($export);
					}
					elseif ($filterid = filter_id($filter))
					{
					/*尝试系统过滤函数*/
						return $export = filter_var($export, $filterid);
					}
					elseif ($regex = (string) Config::get('regex.' . $filter))
					{
					/*尝试配置正则*/
						return preg_match($regex, $export);
					} //继续往下走
				default:
					if (Config::get('isdebug'))
					{
						throw new Exception('未知过方法' . $filter);
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