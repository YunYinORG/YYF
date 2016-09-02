<?php
use \Config as Config;
use Storage\File as File;
use \Logger as Logger;

/**
 * 缓存类
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
     * 设置缓存
     * @method set
     * @param  [type]  $name   [description]
     * @param  [type]  $value  [description]
     * @param  mixed $expire [有效时间]
     * @author NewFuture
     */
    public static function set($name, $value, $expire = 0)
    {
        $handler = Cache::handler();
        if ('memcache'=== Cache::$type) {
            return $handler->set($name, $value, null, $expire);
        } else {
            $expire = $expire > 0 ? $_SERVER['REQUEST_TIME'] + $expire : 0;
            return  $handler->set($name, $value, $expire);
        }
    }

    /**
     * 读取缓存数据
     * @method get
     * @param  [type] $name [description]
     * @return [type]       [description]
     * @author NewFuture
     */
    public static function get($name, $default=false)
    {
        $value = Cache::handler()->get($name);
        return false===$value?$default:$value;
    }

    /**
     * 删除缓存数据
     * @method del
     * @param  [type] $name [description]
     * @return [bool]
     * @author NewFuture
     */
    public static function del($name, $time=0)
    {
        return Cache::handler()->delete($name, $time);
    }

    /**
     * 清空缓存
     * @method fush
     * @return [type] [description]
     * @author NewFuture
     */
    public static function flush()
    {
        return Cache::handler()->flush();
    }

    /**
     * 获取处理方式
     * @return $_handler
     * @author NewFuture
     */
    public static function handler()
    {
        if ($handler = &Cache::$_handler) {
            return $handler;
        }

        switch (Cache::$type=Config::get('cache.type')) {
            case 'memcahed':    //redis 存储
                  $config = Config::getSecret('memcached');
                  $config = $config->get('cache')?:$config->get('_');
                  if ($mcid=$config->get('mcid')) {
                      //共享长连接
                      $handler=new \Memcached($mcid);
                      if (!$handler->getServerList()) {
                          //无可用服务器时建立连接
                          $handler->addServer($config->get('host'), $config->get('port'));
                      }
                  } else {
                      $handler=new \Memcached();
                      $handler->addServer($config->get('host'), $config->get('port'));
                  }
               break;

            case 'file':    //文件存储
               $handler = new File(Config::get('runtime') . 'cache', true);
               break;

            case 'memcache': // memcahe 包括 sae
                $config  = Config::getSecret('memcahe', 'cache');
                $handler = new \Memcache;
                if ($port=$config->get('port')) {
                    $handler->addServer($config->get('host'), $port);
                } else {
                    //sae memcahe
                    $handler->connect($config->get('host'));
                }
                break;

            default:
                Logger::write('缓存初始化失败[cache init failed]'.$type, 'ALERT');
                throw new Exception('未知缓存方式' . Cache::$type);
        }
        return  $handler;
    }
}
