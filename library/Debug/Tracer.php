<?php
namespace Debug;

use Debug as Debug;
use \Logger as log;
use \Yaf_Plugin_Abstract as Plugin;
use \Yaf_Request_Abstract as Request;
use \Yaf_Response_Abstract as Response;

/**
 * 时间跟踪统计
 * 和文件加载统计
 */
class Tracer extends Plugin
{
    protected static $observer = array();
    protected static $_instance = null;

    protected $time = array();
    protected $mem  = array();
    protected $output;

    public static function instance($type = null)
    {
        return static::$_instance ?: (static::$_instance = new static($type));
    }

    protected function __construct($type)
    {
        ob_start();
        $this->output =  explode(',', strtoupper($type));
        $this->time['request'] = isset($_SERVER['REQUEST_TIME_FLOAT']) ?
                            $_SERVER['REQUEST_TIME_FLOAT'] : $_SERVER['REQUEST_TIME'];
        $this->time['start']   = YYF_INIT_TIME;
        $this->mem['startm']   = YYF_INIT_MEM / 1024; //启动内存，包括调试插件占用
        $this->mem['start']    = memory_get_usage() / 1024; //启动内存，包括调试插件占用
    }

    //在路由之前触发，这个是7个事件中, 最早的一个. 但是一些全局自定的工作, 还是应该放在Bootstrap中去完成
    public function routerStartup(Request $request, Response $response)
    {
        $this->time['routerstart'] = microtime(true);
    }

    //分发循环开始之前被触发
    public function dispatchLoopStartup(Request $request, Response $response)
    {
        $this->time['dispatchstart'] = microtime(true);
        $this->mem['dispatchm'] = memory_get_peak_usage() / 1024;
        $this->mem['dispatch'] = memory_get_usage() / 1024;
    }

    //分发结束之后触发，此时动作已经执行结束, 视图也已经渲染完成. 和preDispatch类似, 此事件也可能触发多次
    public function postDispatch(Request $request, Response $response)
    {
        $this->time['postdispatch'] = microtime(true);
    }

    /**
     * 添加统计观察者
     * 统计完成时输传递调结果
     *
     * @param callable $callback 回调
     */
    public static function addObserver($callback)
    {
        static::$observer[] = $callback;
    }

    /**
     * dispaly 资源统计监听回调
     *
     * @param array $mem 内存消耗记录
     * @param array $time 时间消耗记录
     * @param array $files 文件加载记录
     */
    public function dispaly(array $mem, array $time, array $files)
    {
        if (!isset($time['dispatchstart'])) {
            return false;
        }
        
        if (in_array('LOG', $this->output)) {
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
            $time_msg .= PHP_EOL . '  加载插件耗时：' . ($time['routerstart'] - $time['start']) . ' ms';
            $time_msg .= PHP_EOL . '  路由分发耗时：' . ($time['dispatchstart'] - $time['routerstart']) . ' ms';
            $time_msg .= PHP_EOL . '  处理过程耗时: ' . ($time['end'] - $time['dispatchstart']) . ' ms';

            $time_msg .= PHP_EOL . '  执行总共耗时：' . ($time['end'] - $time['start']) . ' ms';
            Debug::log($header . $file_msg . $time_msg . $mem_msg . PHP_EOL, 'TRACER');
        }

        if (in_array('HEADER', $this->output)) {
            $memory = array(
                'S' => $mem['startm'],
                'M' => $mem['max'],
                'U' => $mem['max'] - $mem['start'],
            );
            $time = array(
                'S' => $time['start'] - $time['request'],
                'P' => $time['dispatchstart'] - $time['start'],
                'U' => $time['end'] - $time['dispatchstart'],
            );
            Debug::header()
                ->debugInfo('Mem', $memory)
                ->debugInfo('Time', $time)
                ->debugInfo('File', $files);
        }
    }

    
    public function __destruct()
    {
        $files = get_included_files();
        array_walk($files, function (&$file) {
            if (strpos($file, APP_PATH) === 0) {
                $file = ltrim(substr($file, strlen(APP_PATH)), '\\/');
            }
        });

        $mem  = &$this->mem;
        $time = &$this->time;
        
        $mem['max']  = memory_get_peak_usage() / 1024;
        $time['end'] = microtime(true);

        foreach ($time as &$t) {
            $t *= 1000;
        }
        
        /*传递回调*/
        foreach (static::$observer as &$callback) {
            call_user_func($callback, $mem, $time, $files);
        }
        $this->dispaly($mem, $time, $files);
        ob_get_length() && ob_end_flush();
    }
}
