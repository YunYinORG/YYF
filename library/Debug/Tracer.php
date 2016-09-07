<?php
namespace Debug;

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
    protected $time = array();
    protected $mem  = array();
    protected static $observer = array();
    protected static $_instance = null;


    public static function Instance()
    {
        return static::$_instance?:(static::$_instance=new static);
    }

    protected function __construct()
    {
        ob_start();
        $this->time['request'] = $_SERVER['REQUEST_TIME_FLOAT'] * 1000;
        $this->time['start']   = YYF_INIT_TIME * 1000;
        $this->mem['startm']   = YYF_INIT_MEM / 1024; //启动内存，包括调试插件占用
        $this->mem['start']    = memory_get_usage() / 1024; //启动内存，包括调试插件占用
    }

    //在路由之前触发，这个是7个事件中, 最早的一个. 但是一些全局自定的工作, 还是应该放在Bootstrap中去完成
    public function routerStartup(Request $request, Response $response)
    {
        $this->time['routerstartup'] = microtime(true) * 1000;
    }

    //路由结束之后触发，此时路由一定正确完成, 否则这个事件不会触发
    // public function routerShutdown(Yaf_Request_Abstract $request, Yaf_Response_Abstract $response)
    // {
    // 	$this->time['routershutdown'] = microtime(true) * 1000;
    // }

    //分发循环开始之前被触发
    public function dispatchLoopStartup(Request $request, Response $response)
    {
        $this->time['dispatchloopstartup'] = microtime(true) * 1000;
        $this->mem['dispatchm']            = memory_get_peak_usage() / 1024;
        $this->mem['dispatch']             = memory_get_usage() / 1024;
    }

    //分发之前触发	如果在一个请求处理过程中, 发生了forward, 则这个事件会被触发多次
    // 	public function preDispatch(Yaf_Request_Abstract $request, Yaf_Response_Abstract $response)
    // 	{
    // 		$this->time['predispatch'] = microtime(true) * 1000;
    // 	}

    //分发结束之后触发，此时动作已经执行结束, 视图也已经渲染完成. 和preDispatch类似, 此事件也可能触发多次
    public function postDispatch(Request $request, Response $response)
    {
        $this->time['postdispatch'] = microtime(true) * 1000;
    }

    //分发循环结束之后触发，此时表示所有的业务逻辑都已经运行完成, 但是响应还没有发送
    // public function dispatchLoopShutdown(Yaf_Request_Abstract $request, Yaf_Response_Abstract $response)
    // {
    // 	$this->time['dispatchloopshutdown'] = microtime(true) * 1000;
    // }

    // public function preResponse(Yaf_Request_Abstract $request, Yaf_Response_Abstract $response)
    // {
    // 	$this->time['preresponse'] = microtime(true) * 1000;
    // }

    /**
    * 添加统计观察者
    * 统计完成时输传递调结果
    * @param callable $callback 回调
    */
    public static function addObserver($callback)
    {
        static::$observer[]=$callback;
    }

    public function __destruct()
    {
        $files = get_included_files();
        array_walk($files, function (&$file) {
            if (strpos($file, APP_PATH) === 0) {
                $file=ltrim(substr($file, strlen(APP_PATH)), '\\/');
            }
        });

        $mem  = &$this->mem;
        $time = &$this->time;
        
        $mem['max']  = memory_get_peak_usage()/1024;
        $time['end'] = microtime(true) * 1000;

        /*传递回调*/
        foreach (static::$observer as &$callback) {
            call_user_func($callback, $mem, $time, $files);
        }
        ob_end_flush();
    }
}
