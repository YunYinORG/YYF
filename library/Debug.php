<?php
use \Debug\Header as Header;
use \Debug\SqlListener as SqlListener;
use \Debug\LogListener as LogListener;
use \Debug\Tracer as Tracer;

/**
 * 调试工具
 */
class Debug
{
    protected static $header;

    private static $_instance = null; //单例实体

    
    /**
     * 记录调试日志,写入日志系统而不被拦截
     * 或者打印数据到日志文件
     *
     * @param mixed $msg 日志内容
     * @param string $level 日志级别
     */
    public static function log($msg, $level = 'DEBUG')
    {
        if (is_object($msg)) {
            //字符串dump
            $msg = PHP_EOL . static::dump($msg, true);
        }
        Logger::log(LogListener::safeType($level), $msg);
    }

    /**
     * dump数据,方便在浏览器中显示
     *
     * @param mixed $data dump数据
     * @param boolean $return 返回
     */
    public static function dump($data, $return = false)
    {
        if ($return) {
            ob_start();
            if (extension_loaded('xdebug') && ($xdebug_dump = ini_get('xdebug.overload_var_dump'))) {
                ini_set('xdebug.overload_var_dump', 0);
                var_dump($data);
                ini_set('xdebug.overload_var_dump', $xdebug_dump);
            } else {
                var_dump($data);
            }
            $data = ob_get_clean();
            return preg_replace('/\]\=\>\n(\s+)/m', '] => ', $data);
        } elseif (extension_loaded('xdebug')) {
            xdebug_var_dump($data);
        } else {
            ob_start();
            var_dump($data);
            $data = ob_get_clean();
            $data = preg_replace('/\]\=\>\n(\s+)/m', '] => ', $data);
            $data = htmlspecialchars($data, ENT_NOQUOTES);
            echo '<pre dir="ltr">', $data, '</pre>';
        }
    }

    /**
     * 测试代码运行资源消耗
     *
     * @param callable $function 运行函数
     * @param string $lable 显示标签,为空时返回数组，否则直接输出
     */
    public static function run($function, $lable = '')
    {
        $m0 = memory_get_usage();
        $mp = memory_get_peak_usage();
        $t0 = microtime(true);
        $function();
        $t = microtime(true) - $t0;
        $m = memory_get_peak_usage();
        $m = $m > $mp ? ($m - $m0) : memory_get_usage() - $m0;

        if (!$lable) {
            return array('t' => $t, 'm' => $m);
        }

        $time_unit = array('s', 'ms', 'us', 'ns');
        $mem_unit  = array('B', 'KB', 'MB', 'GB', 'TB', 'PB');

        $i = ($t == 0 || $t >= 1) ? 0 : ceil(log($t, 1 / 1000));
        $t = round($t / pow(1 / 1000, $i), 2) . ' ' . $time_unit[$i];

        $i = ($m == 0) ? 0 : floor(log(abs($m), 1024));
        $m = round($m / pow(1024, $i), 3) . ' ' . $mem_unit[$i];

        echo "$lable: 时间消耗[time] <b> $t </b>; 内存预估[memory]:<b> $m </b><br>";
    }

    /**
     * header头中dump数据
     *
     * @param mixed $data 要输出的数据，可以多个
     */
    public static function header()
    {
        $header = static::$header ?: (static::$header = Header::instance());
        foreach (func_get_args() as $data) {
            $header->dump($data);
        }
        return $header;
    }

    /**
     * 获取Debug实体
     */
    public static function instance()
    {
        return static::$_instance ?: (static::$_instance = new static);
    }

    /**
     * 单例构造函数
     */
    protected function __construct()
    {
        ob_start();
        static::$header = Header::instance();
    }

    /**
     * 监视 数据库sql查询
     */
    public function initSQL($type, $show_result)
    {
        SqlListener::init($type, $show_result);
    }

    /**
     * 监视 日志写入记录
     */
    public function initLog($type)
    {
        LogListener::init($type);
    }
   
    public function __destruct()
    {
        ob_get_length() && ob_end_flush();
    }
}
