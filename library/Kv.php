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
    public static function set($name, $value=null)
    {
        $handler=Kv::handler();
        if (is_array($name)) {
            //数组设置
            assert(func_num_args() === 1, '[Kv::set]数组同步设置时,只支持一个参数');
            if ('kvdb'===Kv::$type) {
                //for sae
                $result = true;
                foreach ($name as $key => &$v) {
                    $result = $result && $handler->set($key, $v);
                }
                return $result;
            } else {
                return $handler->mset($name);
            }
        } else {
            assert('is_scalar($value)||is_null($value)', '[Kv::set]只支持保存字符串');
            return $handler->set($name, $value);
        }
    }

    /**
     * 读取缓存数据
     * @method get
     * @param  [string|array] $name [缓存名称]
     * @return [mixed]       [获取值]
     * @author NewFuture
     */
    public static function get($name, $default=false)
    {
        $handler=Kv::handler();
        if (is_array($name)) {
            //数组获取
            assert('false===$default', '[Kv::get]数组获取时，不能设置默认值');
            if ('file'===Kv::$type) {
                return $handler->mget($name);
            } else {
                return array_combine($name, $handler->mget($name));
            }
        } else {
            //单个值获取
            $result = $handler->get($name);
            return (false===$result)? $default: $result;
        }
    }

    /**
     * delete 别名
     * @author NewFuture
     */
    public static function del($name, $time=0)
    {
        return Kv::handler()->delete($name, $time);
    }

    /**
     * 清空存储
     * @method fush
     * @author NewFuture
     */
    public static function flush()
    {
        $handler=Kv::handler();
        if ('redis'===Kv::$type) {
            return $handler->flushDB();
        } elseif ('kvdb'===Kv::$type) {
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
    public static function handler()
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

            case 'kvdb':    //sae KVdb
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
