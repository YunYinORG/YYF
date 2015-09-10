<?php
/**
 * 启动加载
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
		if (Config::get('isdebug'))
		{

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

				if ($pwd = Config::get('debug.auth')) //是否需要验证
				{
					$connector->setPassword($pwd);
					$evalProvider = $connector->getEvalDispatcher()->getEvalProvider();
					// $evalProvider->disableFileAccessByOpenBaseDir();             // means disable functions like include(), require(), file_get_contents() & etc
					// $evalProvider->addSharedVar('uri', $_SERVER['REQUEST_URI']); // so you can access $_SERVER['REQUEST_URI'] just as $uri in terminal
					// $evalProvider->addSharedVarReference('post', $_POST);
					$connector->startEvalRequestsListener();
				}
			}
			PhpConsole\Helper::register();
		}
	}
}