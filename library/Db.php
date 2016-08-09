<?php
use Service\Database;

/**
 * 数据库管理类
 * Db
 */
class Db
{

    private static $_dbpool = array();//  数据库连接池
    private static $current = null; //  当前数据库连接

   /**
    * 数据库初始化 并取得数据库类实例
    * @method connect
    * @static
    * @param mixed $config 连接配置
    * @return Object[Database] 返回数据库类
    */
    public static function connect($config='_')
    {
        if (is_array($config)) {
            assert('isset($config["dsn"])', '[Db::connect] 数组参数必须配置dsn');
            $key=md5(serialize($config));
        } else {
            assert('is_string($config)', '[Db::connect]直接收数组或者字符串参数');
            $key=$config;
            $config=Config::getSecret('database', 'db.'.$key);
            assert('!empty($config)', '[Db::connect]数据库配置未设置:db.'.$key);
        }
        if (isset(self::$_dbpool[$key])) {
            return self::$_dbpool[$key];
        }
        $username = isset($config['username']) ? $config['username'] : null;
        $password = isset($config['password']) ? $config['password'] : null;
        return self::$_dbpool[$key]=new Database($config['dsn'], $username, $password);
    }

    /**
    * 获取当前数据库连接,如果没有数据库事例将读取默认配置
    * @method current
    * @return [object] Database
    * @author NewFuture
    */
    public static function current()
    {
        return self::$current?:(self::$current=self::connect());
    }

    /**
    * 指定并切换数据库
    * 之后DB直接查询将使用此数据库
    * @method use
    * @return [object] Database
    * @author NewFuture
    */
    public static function use($config)
    {
        switch (func_num_args()) {
            case 1://一个参数，对象，数组，配置名或者dsn
                if ($config instanceof Database) {
                    return self::$current=$config;
                }
                if (is_string($config)&&strpos($config, ':')>0) {
                    $config['dsn']=$config;
                }
                assert('is_array($config)||is_string($config)', '[Db::use] 单个数组参数必须是字符串或者数组');
                break;
            case 3://三参数最后一个为密码
               $config['password'] =func_get_arg(2);
            case 2://两参数第二个为账号
                assert('is_string($config)', '[Db::use]多参数dsn链接设置必须是字符串');
               $config['dsn'] =$config;
               $config['password']  = func_get_args(1);
               break;
            default:
            throw new Exception("无法解析参数，参数数目异常", 1);
        }
        return self::$current=self::connect($config);
    }

    /**
    * 获取数据库表进行后续操作
    * @static
    * @param 
    * @return Object[Orm] 数据库关系类
    */
   public static function table($name, $pk=false, $prefix = true)
   {
       return new Orm($name, $pk, $prefix);
   }

       /**
    * exec 别名 覆盖Db类的的execute
    * @method execute
    * @return [int] 影响条数
    * @author NewFuture
    */
    public static function execute($sql, array $params=null)
    {
        return self::current()->exec($sql, $params);
    }

    /**
    * 数据库操作(写入)
    * @method exec
    * @return [int] 影响条数
    * @author NewFuture
    */
    public static function exec($sql, array $params=null)
    {
        return self::current()->exec($sql, $params);
    }

    /**
    * 数据库查询加速
    * @method query
    * @return mixed 查询结果
    * @author NewFuture
    */
    public static function query($sql, array $params=null)
    {
        return self::current()->query($sql, $params);
    }


    /**
    * 静态方式调用Database的方法
    */
    public static function __callStatic($method, $params)
    {
        assert('method_exists(self::current(),$method)', '[Db::Database]Database中不存在此方式:'.$method);
        return call_user_func_array([self::current(), $method], $params);
    }
}
