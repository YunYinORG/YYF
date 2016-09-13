<?php
use \Debug\Header as Header;
use \Debug\Listener as Listener;
use \Debug\Tracer as Tracer;
use \Logger as Logger;

/**
 * 调试工具
 */
class Debug
{

    protected $tracer_output;
    protected $header;

    protected static $listen_log_type = array();

    private static $_instance = null; //单例实体

    /**
     * 日志过滤标记，日志级别加上此后缀不做监听
     */
    const LOG_SUFFIX = '.LISTENER';

    /**
     * 记录调试日志,写入日志系统而不被拦截
     * 或者打印数据到日志文件
     * @param mixed $msg 日志内容
     * @param string $level 日志级别
     */
    public static function log($msg, $level = 'DEBUG')
    {
        if (static::$listen_log_type === '*' || in_array($level, static::$listen_log_type)) {
            $level = $level . Debug::LOG_SUFFIX;
        }
        if (is_array($msg)) {
            //数组转成json格式
            $msg = PHP_EOL . json_encode($msg, 448); //JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES
        } elseif (is_object($msg)) {
            //字符串dump
            $msg = PHP_EOL . static::dump($msg, true);
        }
        Logger::write($msg, $level);
    }

    /**
     * dump数据,方便在浏览器中显示
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
     * @param mixed $data 要输出的数据，可以多个
     */
    public static function header()
    {
        $header = Header::instance();
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
        $this->header = Header::instance();
    }

    /**
     * 监视 tracer数据
     */
    public function initTracer($type)
    {
        $this->tracer_output = explode(',', strtoupper($type));
        Tracer::addObserver(array($this, 'tracerCallback'));
    }

    /**
     * 监视 数据库sql查询
     */
    public function initSQL($type, $show_result)
    {
        Listener::listenSQL($type);
        Listener::showSqlResultInHeader($show_result);
    }

    /**
     * 监视日志写入记录
     */
    public function listenLog($type)
    {
        if ('*' === $type) {
            static::$listen_log_type = '*';
        } else {
            static::$listen_log_type = explode(',', strtoupper($type));
        }
        Logger::$listener = array($this, 'loggerListener');
        return $this;
    }

    /**
     * 日志监听回调
     * @param callable $level 日志级别
     * @param string $message 日志消息
     */
    public function loggerListener(&$level, &$message)
    {
        if ($p = strpos($level, Debug::LOG_SUFFIX)) {
            //Debug 不监听日志
            $level = substr($level, 0, $p);
        } elseif (static::$listen_log_type === '*' || in_array($level, static::$listen_log_type)) {
            $this->header->debugInfo($level, $message);
        }
    }

    /**
     * tracer 资源统计监听回调
     * @param array $mem 内存消耗记录
     * @param array $time 时间消耗记录
     * @param array $files 文件加载记录
     */
    public function tracerCallback(array $mem, array $time, array $files)
    {
        if (in_array('LOG', $this->tracer_output)) {
            $header = (isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : getenv('REQUEST_METHOD'))
                . ' 资源消耗统计:' . PHP_EOL;

            $file_msg = ltrim(strstr(print_r($files, true), '('), '(');
            $file_msg = str_replace('    ', '  ', $file_msg);
            $file_msg = '[文件加载顺序] (总计：' . count($files) . ')' . strstr($file_msg, ')', true);

            $mem_msg = PHP_EOL . '[内存消耗估计] (峰值: ' . $mem['max'] . ' KB)';
            $mem_msg .= PHP_EOL . '  启动消耗内存: ' . $mem['startm'] . ' KB';
            $mem_msg .= PHP_EOL . '  路由消耗内存: ' . ($mem['dispatchm'] - $mem['start']) . ' KB';
            $mem_msg .= PHP_EOL . '  处理消耗内存: ' . ($mem['max'] - $mem['dispatch']) . ' KB';
            $mem_msg .= PHP_EOL . '  最大消耗内存: ' . $mem['max'] . ' KB';

            $time_msg = '[时间消耗统计] (总计: ' . ($time['end'] - $time['request']) . ' ms)';
            $time_msg .= PHP_EOL . '  定位启动耗时：' . ($time['start'] - $time['request']) . ' ms';
            $time_msg .= PHP_EOL . '  加载插件耗时：' . ($time['routerstartup'] - $time['start']) . ' ms';
            $time_msg .= PHP_EOL . '  路由分发耗时：' . ($time['dispatchloopstartup'] - $time['routerstartup']) . ' ms';
            $time_msg .= PHP_EOL . '  处理过程耗时: ' . ($time['end'] - $time['dispatchloopstartup']) . ' ms';

            $time_msg .= PHP_EOL . '  执行总共耗时：' . ($time['end'] - $time['start']) . ' ms';
            static::log($header . $file_msg . $time_msg . $mem_msg . PHP_EOL, 'TRACER');
        }

        if (in_array('HEADER', $this->tracer_output)) {
            $memory = array(
                'S' => $mem['startm'],
                'M' => $mem['max'],
                'U' => $mem['max'] - $mem['start'],
            );
            $time = array(
                'S' => $time['start'] - $time['request'],
                'P' => $time['dispatchloopstartup'] - $time['start'],
                'U' => $time['end'] - $time['dispatchloopstartup'],
            );
            $this->header
                ->debugInfo('Mem', $memory)
                ->debugInfo('Time', $time)
                ->debugInfo('File', $files);
        }
    }

    public function __destruct()
    {
        ob_end_flush();
    }
}
