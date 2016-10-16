<?php
use \Service\Database as Database;
use \Orm as Orm;

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
     *
     * @method connect
     * @static
     *
     * @param mixed $config 连接配置
     *
     * @return Object[Database] 返回数据库类
     */
    public static function connect($config = '_')
    {
        if (is_array($config)) {
            assert('isset($config["dsn"])', '[Db::connect] 数组参数必须配置dsn');
            $key = md5(json_encode($config));
        } else {
            assert('is_string($config)', '[Db::connect]直接收数组或者字符串参数');
            $key = $config;
            $config = Config::getSecret('database', 'db.'.$key)->toArray();
            assert('!empty($config)', '[Db::connect]数据库配置未设置:db.'.$key);
        }
        if (isset(Db::$_dbpool[$key])) {
            return Db::$_dbpool[$key];
        }
        $username = isset($config['username']) ? $config['username'] : null;
        $password = isset($config['password']) ? $config['password'] : null;
     
        $options = Config::getSecret('database', 'options')->toArray();
        if (isset($config['options'])) {
            assert('is_array($config["options"])', '[Db::connect]数据库连接参数options配置应是数组');
            $options = $config['options'] + $options;
        }
        
        try {
            return Db::$_dbpool[$key] = new Database($config['dsn'], $username, $password, $options);
        } catch (Exception $e) {
            Logger::log(
                'ALERT',
                '[Db::connect]数据库[{KEY}]({DSN})无法连接:{MSG}',
                array('KEY' => $key, 'DSN' => $config['dsn'], 'MSG' => $e->getMessage())
            );
            throw $e;
        }
    }

    /**
     * 获取或者设置当前数据库连接,如果没有数据库事例将读取默认配置
     *
     * @method current
     *
     * @param  Database  $db  要设定的数据库，空返回当前数据库
     *
     * @return [object] Database
     *
     * @author NewFuture
     */
    public static function current(Database $db = null)
    {
        if (null === $db) {
            return Db::$current ?: (Db::$current = Db::connect());
        } else {
            return Db::$current = $db;
        }
    }

    /**
     * @method get
     * 获取数据库连接，用于读写分离，如果不存在配置使用默认数据库
     *
     * @param  string $name         [数据库名]
     *
     * @return [type] [description]
     *
     * @author NewFuture
     */
    public static function get($name = '_')
    {
        if (isset(Db::$_dbpool[$name])) {
            return Db::$_dbpool[$name];
        }
        return  Db::$_dbpool[$name] = Config::getSecret('database', 'db.'.$name.'.dsn') ? Db::connect($name) : Db::connect('_');
    }

    /**
     * 设定换数据库,可以覆盖默认数据库配置
     *
     * @method set
     *
     * @param string $name 设置名称，‘_’,'_read','_write'
     * @param mixed  $config 配置名称
     *
     * @return [object] Database
     *
     * @author NewFuture
     */
    public static function set($name, $config)
    {
        $conf = array();
        switch (func_num_args()) {
            case 2://一个参数，对象，数组，配置名或者dsn
                if ($config instanceof Database) {
                    return Db::$_dbpool[$name] = $config;
                }
                if (is_string($config) && strpos($config, ':') > 0) {
                    $conf['dsn'] = &$config;
                } else {
                    assert('is_string($config)||is_array($config)', '[Db::set] 单个数组参数必须是字符串或者数组');
                    $conf = $config;
                }
                break;
            case 5://三参数最后一个为密码
               $conf['options'] = func_get_arg(4);
            case 4://三参数最后一个为密码
               $conf['password'] = func_get_arg(3);
            case 3://两参数第二个为账号
                assert('is_string($config)', '[Db::set]多参数dsn链接设置必须是字符串');
                $conf['username']  = func_get_arg(2);
                $conf['dsn'] = $config;
                break;
            default:
                throw new Exception('无法解析参数，参数数目异常'.func_num_args());
        }
        return Db::$_dbpool[$name] = Db::connect($conf);
    }

   /**
    * 获取数据库表进行后续操作
    *
    * @static
    *
    * @param
    *
    * @return Object[Orm] 数据库关系类
    */
   public static function table($name, $pk = false, $prefix = true)
   {
       return new Orm($name, $pk, $prefix);
   }

    /**
     * exec 别名 覆盖Db类的的execute
     *
     * @method execute
     *
     * @return [int] 影响条数
     *
     * @author NewFuture
     */
    public static function execute($sql, array $params = null)
    {
        return Db::get('_write')->exec($sql, $params);
    }

    /**
     * 数据库操作(写入)
     *
     * @method exec
     *
     * @return [int] 影响条数
     *
     * @author NewFuture
     */
    public static function exec($sql, array $params = null)
    {
        return Db::get('_write')->exec($sql, $params);
    }

    /**
     * 数据库查询加速
     *
     * @method query
     *
     * @return mixed 查询结果
     *
     * @author NewFuture
     */
    public static function query($sql, array $params = null, $fetchAll = true, $fetchmode = null)
    {
        return Db::get('_read')->query($sql, $params, $fetchAll, $fetchmode);
    }


    /**
     * 数据库查询加速
     *
     * @method column
     *
     * @return mixed 查询结果
     *
     * @author NewFuture
     */
    public static function column($sql, array $params = null)
    {
        return Db::get('_read')->column($sql, $params);
    }

    /**
     * 静态方式调用Database的方法
     */
    public static function __callStatic($method, $params)
    {
        assert('method_exists("\Service\Database",$method)', '[Db::Database]Database中不存在此方式:'.$method);
        return call_user_func_array(array(Db::current(), $method), $params);
    }
}
