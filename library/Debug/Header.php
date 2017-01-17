<?php
/**
 * YYF - A simple, secure, and high performance PHP RESTful Framework.
 *
 * @link https://github.com/YunYinORG/YYF/
 *
 * @license Apache2.0
 * @copyright 2015-2017 NewFuture@yunyin.org
 */

namespace Debug;

use \Config as Config;
use \ReflectionClass as ReflectionClass;
use \Yaf_Application as Application;

/**
 * 响应头输出调试信息
 * 支持链式调用
 *
 * @method Header key($value) 快速调用
 *
 * @example
 * Header::log('some log info');
 * Header::warn('unexpected messages');
 * Header::error('error');
 * Header::dump('data');
 * Header::warn('wrong message')->dump($data);
 *
 * @author NewFuture
 */
class Header
{
    const HEADER_BASE = 'Yyf';
    private static $_processed;
    private static $_instance = null;

    protected function __construct()
    {
        $verion = Config::get('version');
        if ($app = Application::app()) {
            $env = $app->environ();
        } else {
            $env = ini_get('yaf.environ');
        }
        header(strtoupper(static::HEADER_BASE).": $verion,$env");
    }

    /**
     * 通过http header dump数据
     *
     * @param string $key   header键
     * @param mixed  $value 对应值
     */
    public function __invoke($key, $value)
    {
        assert('ctype_alnum($key)', 'header 键必须是字符或数字组合');
        switch (gettype($value)) {
            case 'string':
                if (ctype_print($value) && (strpos($value, "\n") === false)) {
                    $key = '-S-'.$key;//字符串
                } else {
                    $key   = '-E-'.$key;//编码字符串
                    $value = rawurlencode($value);
                }
                break;

            case 'integer':
            case 'double':
                $key = '-N-'.$key;//数字
                break;

            case 'boolean':
                $key .= '-B-'.$key;//bool
                $value = $value ? 'true' : 'false';
                break;

            case 'array':
            case 'null':
            case null:
                $key   = '-J-'.$key;//数组JSON
                $value = json_encode($value);
                break;

            case 'object':
                //对象
                $key                = '-O-'.$key;
                static::$_processed = array();
                $value              = json_encode(static::_convertObject($value));
                break;

            case 'unkown':
            default:
                $key   = '-U-'.$key;
                $value = rawurlencode(str_replace('    ', "\t", print_r($value, true)));
                $value = str_replace(array('+', '%3D', '%3E', '%28', '%29', '%5B', '%5D', '%3A'), array(' ', '=', '>', '(', ')', '[', ']', ':'), $value);
        }
        headers_sent() || header(static::HEADER_BASE.$key.': '.$value, false);
        return $this;
    }

    /**
     * 静态调用
     *
     * @param string $Type   函数名
     * @param array  $params 参数
     */
    public static function __callStatic($Type, $params)
    {
        $head = static::instance();
        if (isset($params[1])) {
            //多参数
            return  $head($Type, $params);
        } elseif (isset($params[0])) {
            //单参数
            return $head($Type, $params[0]);
        }
    }

    /**
     * 链式调用
     *
     * @param string $Type   函数名
     * @param array  $params 参数
     */
    public function __call($Type, $params)
    {
        return static::__callStatic($Type, $params);
    }

    /**
     * 添加header调试信息
     *
     * @param string       $type 字段名
     * @param array|string $info [输出的调试信息]
     * @param int          $N=3  数字保留精度0不处理(压缩数字小数点后数据减小header数据量)
     */
    public function debugInfo($type, $info, $N = 3)
    {
        if (is_array($info)) {
            if ($N > 0) {
                array_walk_recursive($info, function (&$v) use ($N) {
                    is_numeric($v) && $v = round($v, $N);
                });
            }
            $info = json_encode($info, 64);//64 JSON_UNESCAPED_SLASHES
        }
        headers_sent() || header(static::HEADER_BASE."-$type: $info", false);
        return $this;
    }

    /**
     * 获取事例
     *
     * @return Header 自身实体
     */
    public static function instance()
    {
        return static::$_instance ?: (static::$_instance = new static());
    }

    /**
     * 转换object->array
     *
     * @param Object $object [description]
     *
     * @return array
     */
    private static function _convertObject($object)
    {
        if (!is_object($object)) {
            return $object;
        }
        static::$_processed[] = $object;
        $object_as_array      = array();

        $object_as_array['__CLASS__'] = get_class($object);

        /* vars*/
        $object_vars = get_object_vars($object);
        foreach ($object_vars as $key => $value) {
            // same instance as parent object
            $object_as_array[$key] = in_array($value, static::$_processed, true) ? '__CLASS__['.get_class($value).']' : static::_convertObject($value);
        }

        $reflection = new ReflectionClass($object);
        /* properties */
        foreach ($reflection->getProperties() as $property) {
            if (array_key_exists($property->getName(), $object_vars)) {
                // if one of these properties was already added above then ignore it
                continue;
            }
            $type = $property->getName();
            $type .= $property->isStatic() ? '_STATIC' : '';
            $type .= $property->isPrivate() ? '_PRIVATE' : $property->isProtected() ? '_PROTECTED' : '_PUBLIC';

            $property->setAccessible(true);
            $value = $property->getValue($object);

            $object_as_array[$type] = in_array($value, static::$_processed, true) ? '__CLASS__['.get_class($value).']' : static::_convertObject($value);
        }
        return $object_as_array;
    }
}
