<?php
use \Config as Config;
use \Logger as Logger;

/**
 * 键值对存储
 * Function list:
 * - set()
 * - get()
 * - del()
 * - flush()
 */
class Kv
{

    private static $type = null;
    private static $_handler = null; //处理方式

    /**
     * 设置缓存
     * @method set
     * @param  [string]  $name   [缓存名称]
     * @param  [mixed]  $value  [缓存值]
     * @param  mixed $expire [有效时间]
     * @author NewFuture
     */
    public static function set($name, $value)
    {
        return Kv::Handler()->set($name, $value);
    }

    /**
     * 读取缓存数据
     * @method get
     * @param  [string] $name [缓存名称]
     * @return [mixed]       [获取值]
     * @author NewFuture
     */
    public static function get($name, $default=false)
    {
        $v = Kv::Handler()->get($name);
        return (false===$v)? $default: $v;
    }

    /**
     * delete 别名
     * @author NewFuture
     */
    public static function del($name)
    {
        return Kv::Handler()->delete($name);
    }

    /**
     * 删除缓存数据
     * @method del
     * @param  [string] $name [缓存名称]
     * @return [bool]
     * @author NewFuture
     */
    public static function delete($name)
    {
        return Kv::Handler()->delete($name);
    }

    /**
     * 清空存储
     * @method fush
     * @author NewFuture
     */
    public static function flush()
    {
        $handler=Kv::Handler();
        if ('redis'===Kv::$type) {
            return $handler->flushDB();
        } elseif ('sae'===Kv::$type) {
            /*sae KVDB 逐个删除*/
            while ($ret = $handler->pkrget('', 100)) {
                foreach ($ret as $k => &$v) {
                    $handler->delete($k);
                }
            }
            return true;
        } else {
            return $handler->flush();
        }
    }

    /**
     * 清空存储
     * @method clear
     * @author NewFuture
     */
    public static function clear()
    {
        Kv::flush();
        return Kv::$_handler;
    }

    /**
     * 获取处理方式
     * @return $_handler
     * @author NewFuture
     */
    public static function Handler()
    {
        if ($handler = &Kv::$_handler) {
            return $handler;
        }
        
        switch (Kv::$type=Config::get('kv.type')) {
            case 'redis':    //redis 存储
                  $config=Config::getSecret('redis');
                  $config=$config->get('kv')?:$config->get('_');

                  $handler=new \Redis();
                  $handler->connect($config->get('host'), $config->get('port'));
                  //密码验证
                  ($value=$config->get('auth')) && $handler->auth($value);
                  //限定数据库
                  ($value=$config->get('db')) && $handler->select($value);
               break;

            case 'file':    //文件存储
               $handler = new Storage\File(Config::get('runtime') . 'kv', false);
               break;

            case 'sae':    //sae KVdb
                $handler = new \SaeKV();
                if (!$handler->init()) {
                    Logger::write('SAE KV cannot init'.$handler->errmsg(), 'ERROR');
                };
              break;

            default:
                throw new Exception('未定义方式' . Kv::$type);
        }
        return  $handler;
    }
}
