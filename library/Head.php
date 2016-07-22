<?php
/**
 * 响应头输出调试信息
 * Head::log('some log info');
 * Head::warn('unexpected messages');
 * Head::error('error');
 * Head::dump('data');
 * 支持链式调用
 * Head::warn('wrong message')->dump($data);
 */
class Head
{
	CONST HEADER_BASE = 'Yyf-Debug';
	private static $_processed;
	private static $_instance = null;

	/**
	 * 添加header
	 * @param [type] $key   [description]
	 * @param [type] $value [description]
	 */
	public function add($key, $value)
	{
		assert('ctype_alnum($key)', 'header 键必须是字符或数字组合');
		switch (gettype($value))
		{
			case 'string': 
				$key .= '-S';//字符串
				$value = urlencode($value);
				break;

			case 'integer':
			case 'double':				
				$key .= '-N';//数字
				break;

			case 'boolean': 
				$key .= '-B';//bool
				$value = $value ? 'true' : 'false';
				break;

			case 'array':
			case 'null':
			case null:
				$key .= '-J';//数组JSON
				$value = json_encode($value);
				break;

			case 'object':
				//对象
				$key .= '-O';
				self::$_processed = array();
				$value            = json_encode(self::_convertObject($value));
				break;

			case 'unkown':
			default:
				$key .= '-U';
				$value = urlencode(str_replace('    ', "\t", print_r($value, true)));
				$value = str_replace(array('+', '%3D', '%3E', '%28', '%29', '%5B', '%5D', '%3A'), array(' ', '=', '>', '(', ')', '[', ']', ':'), $value);

		}
		header(self::HEADER_BASE . '-' . $key . ': ' . $value, FALSE);
	}

	/**
	 * 静态调用
	 */
	public static function __callStatic($Type, $params)
	{
		$Type = ucfirst($Type);
		$head = self::getInstance();
		if (isset($params[1]))
		{
			//多参数
			$head->add($Type, $params);
		}
		elseif (isset($params[0]))
		{
			//单参数
			$head->add($Type, $params[0]);
		}
		return $head;
	}

	/**
	 * 链式调用
	 */
	public function __call($Type, $params)
	{
		return self::__callStatic($Type, $params);
	}

	/**
	 * 获取事例
	 * @return [type] [description]
	 */
	private static function getInstance()
	{
		return self::$_instance ?: (self::$_instance = new self());
	}

	private function __construct()
	{
		header(self::HEADER_BASE . ': ' . Config::get('version'));
	}

	/**
	 * 转换object->array
	 * @param  [type] $object [description]
	 * @return array
	 */
	private static function _convertObject($object)
	{
		if (!is_object($object))
		{
			return $object;
		}
		self::$_processed[] = $object;
		$object_as_array    = array();

		$object_as_array['__CLASS__'] = get_class($object);

		/* vars*/
		$object_vars = get_object_vars($object);
		foreach ($object_vars as $key => $value)
		{
			// same instance as parent object
			$object_as_array[$key] = in_array($value, self::$_processed, true) ? '__CLASS__[' . get_class($value) . ']' : self::_convertObject($value);
		}

		$reflection = new ReflectionClass($object);
		/* properties */
		foreach ($reflection->getProperties() as $property)
		{

			if (array_key_exists($property->getName(), $object_vars))
			{
				// if one of these properties was already added above then ignore it
				continue;
			}
			$type = $property->getName();
			$type .= $property->isStatic() ? '_STATIC' : '';
			$type .= $property->isPrivate() ? '_PRIVATE' : $property->isProtected() ? '_PROTECTED' : '_PUBLIC';

			$property->setAccessible(true);
			$value = $property->getValue($object);

			$object_as_array[$type] = in_array($value, self::$_processed, true) ? '__CLASS__[' . get_class($value) . ']' : self::_convertObject($value);
		}
		return $object_as_array;
	}
}