<?php
/**
 * 生产环境启动加载
 */
class Bootstrap extends Yaf_Bootstrap_Abstract
{

	/**
	 * 初始化路由
	 * @method _initRoute
	 * @author NewFuture
	 */
	public function _initRoute(Yaf_Dispatcher $dispatcher)
	{
		$dispatcher->getRouter()->addConfig(Config::get('routes'));
	}
}