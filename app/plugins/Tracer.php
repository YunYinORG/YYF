<?php
use \Logger as log;
/**
 * 时间跟踪统计
 * 和文件加载统计
 */
class TracerPlugin extends Yaf_Plugin_Abstract
{
	private $time = [];
	private $mem  = [];
	
	public function __construct()
	{
		Log::write(getenv('REQUEST_METHOD').' ' . getenv('REQUEST_URI'), 'TRACER');
		$this->time['request'] = $_SERVER['REQUEST_TIME_FLOAT'] * 1000;
		$this->time['start']   = YYF_INIT_TIME*1000;
		$this->mem['startm']   = YYF_INIT_MEM / 1024; //启动内存，包括调试插件占用
		$this->mem['start']    = memory_get_usage() / 1024; //启动内存，包括调试插件占用
	}

	//在路由之前触发，这个是7个事件中, 最早的一个. 但是一些全局自定的工作, 还是应该放在Bootstrap中去完成
	public function routerStartup(Yaf_Request_Abstract $request, Yaf_Response_Abstract $response)
	{
		$this->time['routerstartup'] = self::mtime();
	}

	//路由结束之后触发，此时路由一定正确完成, 否则这个事件不会触发
	// public function routerShutdown(Yaf_Request_Abstract $request, Yaf_Response_Abstract $response)
	// {
	// 	$this->time['routershutdown'] = self::mtime();
	// }

	//分发循环开始之前被触发
	public function dispatchLoopStartup(Yaf_Request_Abstract $request, Yaf_Response_Abstract $response)
	{
		$this->time['dispatchloopstartup'] = self::mtime();
		$this->mem['dispatchm']            = memory_get_peak_usage() / 1024;
		$this->mem['dispatch']             = memory_get_usage() / 1024;
	}

	//分发之前触发	如果在一个请求处理过程中, 发生了forward, 则这个事件会被触发多次
	// 	public function preDispatch(Yaf_Request_Abstract $request, Yaf_Response_Abstract $response)
	// 	{
	// 		$this->time['predispatch'] = self::mtime();
	// 	}

	//分发结束之后触发，此时动作已经执行结束, 视图也已经渲染完成. 和preDispatch类似, 此事件也可能触发多次
	public function postDispatch(Yaf_Request_Abstract $request, Yaf_Response_Abstract $response)
	{
		$this->time['postdispatch'] = self::mtime();
	}

	//分发循环结束之后触发，此时表示所有的业务逻辑都已经运行完成, 但是响应还没有发送
	// public function dispatchLoopShutdown(Yaf_Request_Abstract $request, Yaf_Response_Abstract $response)
	// {
	// 	$this->time['dispatchloopshutdown'] = self::mtime();
	// }

	// public function preResponse(Yaf_Request_Abstract $request, Yaf_Response_Abstract $response)
	// {
	// 	$this->time['preresponse'] = self::mtime();
	// }

	/**
	 * 获取当前毫秒数
	 * @method mtime
	 * @return [type] [description]
	 * @author NewFuture
	 */
	public static function mtime()
	{
		return microtime(true) * 1000;
	}

	public function __destruct()
	{
		$includes = get_included_files();
		$file_msg = ltrim(strstr(print_r($includes, true),'('),'(');
		$file_msg = str_replace("    ",'  ',$file_msg);
		$file_msg = '资源消耗统计:'.PHP_EOL.'[文件加载顺序] (总计：' . count($includes) . ')'.strstr($file_msg,')',true);

		$mem     = &$this->mem;
		$mem['max'] = memory_get_peak_usage()/1024;
		// $mem['end'] = memory_get_usage() / 1024;
		$mem_msg = PHP_EOL . '[内存消耗估计] (峰值: '.$mem['max'].' Kb)';
		$mem_msg .= PHP_EOL . '  启动消耗内存: ' . $mem['startm'] . ' Kb';
		$mem_msg .= PHP_EOL . '  路由消耗内存: ' . ($mem['dispatchm'] - $mem['start']) . ' Kb';
		$mem_msg .= PHP_EOL . '  处理消耗内存: ' . ($mem['max'] - $mem['dispatch']) . ' Kb';
		// $mem_msg .= PHP_EOL . '  结束占用内存: ' . $mem['end'] . ' Kb';
		$mem_msg .= PHP_EOL . '  最大消耗内存: ' . $mem['max'] . ' Kb';

		$time     = &$this->time;
		$time['end'] = self::mtime();
		$time_msg   = '[时间消耗统计] (总计: ' .  ($time['end'] - $time['request']).' ms)';
		$time_msg .= PHP_EOL . '  定位启动耗时：' . ($time['start'] - $time['request']) . ' ms';
		$time_msg .= PHP_EOL . '  加载插件耗时：' . ($time['routerstartup'] - $time['start']) . ' ms';
		$time_msg .= PHP_EOL . '  路由分发耗时：' . ($time['dispatchloopstartup'] - $time['routerstartup']) . ' ms';
		$time_msg .= PHP_EOL . '  处理过程耗时: ' . ($time['end'] - $time['dispatchloopstartup']) . ' ms';

		$time_msg .= PHP_EOL . '  执行总共耗时：' . ($time['end'] - $time['start']) . ' ms';
		Log::write($file_msg . $time_msg . $mem_msg . PHP_EOL, 'TRACER');
	}
}