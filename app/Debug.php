<?php
/**
 * 调试启动加载
 */
class Bootstrap extends Yaf_Bootstrap_Abstract
{

	/**
	 * 开启调试输出
	 * @method _initDebug
	 * @author NewFuture
	 */
	public function _initDebug()
	{
		if($debug=Config::get('debug'))
		{
			error_reporting(E_ALL); //错误回传
		}

		switch (strtolower($debug)) 
		{
			case 'dump': //直接输出错误
				ini_set('display_errors', 1); 
				break;

			case 'log': //log到文件
				ini_set('log_errors', 1); 
				ini_set('error_log', Config::get('tempdir') . '/error_log.txt'); 
				break;
			
			case 'phpconsle':
				/*加载 PHP Console Debug模块*/
				Yaf_Loader::import('PhpConsole/__autoload.php');
				$connector = PhpConsole\Connector::getInstance();
				if ($connector->isActiveClient())
				{
					Log::write('PHP Console 已经链接', 'INFO');

					$handler    = PhpConsole\Handler::getInstance();
					$dispatcher = $connector->getDebugDispatcher();
					$handler->start();

					$connector->setSourcesBasePath(APP_PATH);
					$connector->setServerEncoding('utf8');
					$dispatcher->detectTraceAndSource = true; //跟踪信息
				}
				PhpConsole\Helper::register();
				break;
		}
	}

	/**
	 * 加载插件
	 * @method _initPlugin
	 * @param  Yaf_Dispatcher $dispatcher [description]
	 * @return [type]                     [description]
	 * @access private
	 * @author NewFuture
	 */
	public function _initPlugin(Yaf_Dispatcher $dispatcher)
	{
		$tracer = new TracerPlugin();
		$dispatcher->registerPlugin($tracer);
	}

	/**
	 * 开启调试输出
	 * @method _initRoute
	 * @author NewFuture
	 */
	public function _initRoute(Yaf_Dispatcher $dispatcher)
	{
		if($routes=Config::get('routes'))
		{
			$dispatcher->getRouter()->addConfig($routes);
		}		
	}
}