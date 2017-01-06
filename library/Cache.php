<?php
/**
 * YYF - A simple, secure, and high performance PHP RESTful Framework.
 *
 * @see https://github.com/YunYinORG/YYF/
 *
 * @license Apache2.0
 * @copyright 2015-2017 NewFuture@yunyin.org
 */

use \Config as Config;
use \Logger as Logger;
use Storage\File as File;

/**
 * 缓存类 Cache.
 *
 * @author NewFuture
 * Function list:
 * - set()
 * - get()
 * - del()
 * - flush()
 */
class Cache
{
    private static $type = null;
    private static $_handler = null; //处理方式

    /**
     * 设置缓存.
     *
     * @method set
     *
     * @param string|array $name   键
     * @param mixed        $value  值
     * @param int          $expire [缓存时间]
     */
    public static function set($name, $value = 0, $expire = 0)
    {
        $handler = self::handler();
        $type = &self::$type;
        if (is_array($name)) {
            //数组批量设置
            //$value is $expire
            assert(func_num_args() < 3, '[Cache::set]第一个参数为数组时(批量设置)，最多两个参数');
            assert('is_numeric($value)', '[Cache::set]批量设置时，第二个参数时间必须为数字');

            switch ($type) {
                case 'memcached':
                    return $handler->setMulti($name, $value);

                case 'redis':

                    $result = true;
                     if ($value) {
                         foreach ($name as $k => &$v) {
                             $result = $result && $handler->setEx($k, $value, serialize($v)); //memcache 原始时间
                         }
                     } else {
                         foreach ($name as $k => &$v) {
                             $result = $result && $handler->set($k, serialize($v)); //memcache 原始时间
                         }
                     }

                    return  $result;

                case 'file':
                    return $handler->mset($name, $value);

                case 'memcache':
                    $result = true;
                    foreach ($name as $k => &$v) {
                        $result = $result && $handler->set($k, $v, null, $value); //memcache 原始时间
                    }

                    return $result;
            }
        } else {
            //单条设置
            assert(func_num_args() > 1, '[Cache::set]第一个参数为数组时(批量设置)，最多两个参数');
            if ('memcached' === $type || 'file' === $type) {
                return  $handler->set($name, $value, $expire);
            } elseif ('redis' === $type) {
                $value = serialize($value);

                return $expire ? $handler->setEx($name, $expire, $value) : $handler->set($name, $value);
            }
            assert('"memcache" ===$type', '缓存驱动不支持');

            return $handler->set($name, $value, null, $expire);
        }
    }

    /**
     * 读取缓存数据.
     *
     * @method get
     *
     * @param string|array $name    键
     * @param mixed        $default [默认值false]
     *
     * @return mixed 获取结果
     */
    public static function get($name, $default = false)
    {
        $handler = self::handler();
        if (is_array($name)) {
            //数组批量获取
            assert(func_num_args() === 1, '[Cache::get]参数为数组时(批量设置)，只能有一个参数');
            switch (self::$type) {
                case 'memcached':
                    $default = $handler->getMulti($name);
                    if (count($default) === count($name)) {
                        return $default;
                    }

                        return array_merge(array_fill_keys($name, false), $default);

                case 'file':
                    return $handler->mget($name);

                case 'redis':
                    if ($value = $handler->mget($name)) {
                        return array_combine($name, array_map('unserialize', $value));
                    }

                        return array_fill_keys($name, $default);

                case 'memcache':
                    $result = [];
                    foreach ($name as &$key) {
                        $result[$key] = $handler->get($key); //memcache 原始时间
                    }

                    return $result;
            }
        } else {
            $value = $handler->get($name);

            return false === $value ? $default : ('redis' === self::$type ? unserialize($value) : $value);
        }
    }

    /**
     * 删除缓存数据.
     *
     * @method del
     *
     * @param string $name 键值
     *
     * @return bool
     */
    public static function del($name)
    {
        return self::handler()->delete($name);
    }

    /**
     * 清空缓存.
     *
     * @method fush
     *
     * @return bool 操作结果
     */
    public static function flush()
    {
        $handler = self::handler();
        if ('redis' === self::$type) {
            return $handler->flushDB();
        }

        return $handler->flush();
    }

    /**
     * 获取处理方式.
     *
     * @return $_handler
     */
    public static function handler()
    {
        if ($handler = &self::$_handler) {
            return $handler;
        }

        switch (self::$type = Config::get('cache.type')) {
            case 'memcached': //redis 存储
                  $config = Config::getSecret('memcached');
                  $config = $config->get('cache') ?: $config->get('_');
                  if ($mcid = $config->get('mcid')) {
                      //共享长连接
                      $handler = new \Memcached($mcid);
                      if (!$handler->getServerList()) {
                          //无可用服务器时建立连接
                          $handler->addServer($config->get('host'), $config->get('port'));
                      }
                  } else {
                      $handler = new \Memcached();
                      $handler->addServer($config->get('host'), $config->get('port'));
                  }
               break;

            case 'redis': //redis 存储
                  $config = Config::getSecret('redis');
                  $config = $config->get('cache') ?: $config->get('_');

                  $handler = new \Redis();
                  $handler->connect($config->get('host'), $config->get('port'));
                  //密码验证
                  ($value = $config->get('auth')) && $handler->auth($value);
                  //限定数据库
                  ($value = $config->get('db')) && $handler->select($value);
               break;

            case 'file': //文件存储
               $handler = new File(Config::get('runtime').'cache', true);
               break;

            case 'memcache': // memcahe 包括 sae
                $config = Config::getSecret('memcahe', 'cache');
                $handler = new \Memcache();
                if ($port = $config->get('port')) {
                    $handler->addServer($config->get('host'), $port);
                } else {
                    //sae memcahe
                    $handler->connect($config->get('host'));
                }
                break;

            default:
                Logger::write('缓存初始化失败[cache init failed]'.$type, 'ALERT');
                throw new Exception('未知缓存方式'.self::$type);
        }

        return  $handler;
    }
}
