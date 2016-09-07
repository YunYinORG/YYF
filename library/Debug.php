<?php
use \Logger as Logger;
use \Debug\Tracer as Tracer;
use \Debug\Header as Header;
use \Debug\Listener as Listener;

/**
 * 调试工具
 * 开发环境使用
 */
class Debug
{

    protected $tracer_output;

    protected static $listen_log_type;

    private static $_instance = null;//单例实体


    /**
    * 日志过滤标记，日志级别加上此后缀不做监听
    */
    const LOG_SUFFIX='.LISTENER';

    
    /**
    * 记录调试日志,写入日志系统而不被拦截
    * @param string $msg 日志内容
    * @param string $level 日志级别
    */
    public static function log($msg, $level='DEBUG')
    {
        if (static::$listen_log_type) {
            $level=$level.Debug::LOG_SUFFIX;
        }
        Logger::write($msg, $level);
    }
    
    /**
    * header头中dump数据
    * @param mixed $data 要输出的数据，可以多个
    */
    public static function header()
    {
        $header=Header::instance();
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
        return static::$_instance?:(static::$_instance=new static);
    }

    /**
    * 单例构造函数
    */
    protected function __construct()
    {
        ob_start();
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
        if ('*'===$type) {
            static::$listen_log_type =  '*';
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
        if ($p=strpos($level, Debug::LOG_SUFFIX)) {
            //Debug 不监听日志 
            $level=substr($level, 0, $p);
        } elseif (static::$listen_log_type === '*' || in_array($level, static::$listen_log_type)) {
            Header::instance()->debugInfo($level, $message);
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
            $header = (isset($_SERVER['REQUEST_METHOD'])?$_SERVER['REQUEST_METHOD']:getenv('REQUEST_METHOD'))
                  .' 资源消耗统计:'. PHP_EOL;

            $file_msg = ltrim(strstr(print_r($files, true), '('), '(');
            $file_msg = str_replace("    ", '  ', $file_msg);
            $file_msg = '[文件加载顺序] (总计：' . count($files) . ')'.strstr($file_msg, ')', true);

            $mem_msg  = PHP_EOL . '[内存消耗估计] (峰值: '.$mem['max'].' Kb)';
            $mem_msg .= PHP_EOL . '  启动消耗内存: ' . $mem['startm'] . ' Kb';
            $mem_msg .= PHP_EOL . '  路由消耗内存: ' . ($mem['dispatchm'] - $mem['start']) . ' Kb';
            $mem_msg .= PHP_EOL . '  处理消耗内存: ' . ($mem['max'] - $mem['dispatch']) . ' Kb';
            $mem_msg .= PHP_EOL . '  最大消耗内存: ' . $mem['max'] . ' Kb';

            $time_msg  = '[时间消耗统计] (总计: ' .  ($time['end'] - $time['request']).' ms)';
            $time_msg .= PHP_EOL . '  定位启动耗时：' . ($time['start'] - $time['request']) . ' ms';
            $time_msg .= PHP_EOL . '  加载插件耗时：' . ($time['routerstartup'] - $time['start']) . ' ms';
            $time_msg .= PHP_EOL . '  路由分发耗时：' . ($time['dispatchloopstartup'] - $time['routerstartup']) . ' ms';
            $time_msg .= PHP_EOL . '  处理过程耗时: ' . ($time['end'] - $time['dispatchloopstartup']) . ' ms';

            $time_msg .= PHP_EOL . '  执行总共耗时：' . ($time['end'] - $time['start']) . ' ms';
            static::log($header.$file_msg . $time_msg . $mem_msg . PHP_EOL, 'TRACER');
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
            Header::instance()
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