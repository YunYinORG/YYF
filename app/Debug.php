<?php
/**
 * 调试启动加载
 */
class Bootstrap extends Yaf_Bootstrap_Abstract
{

	/**
	 * 开启断言
	 * @method _initAssert
	 * @author NewFuture
	 */
	public function _initAssert()
	{

		$assert = Config::get('assert');
		if ($assert['active'])
		{
			if (version_compare(PHP_VERSION, '7.0.0', '>=') && (-1 == ini_get('zend.assertions')))
			{
				//PHP7配置
				exit("调试环境，请开启php7的断言，以便更早发现问题！\n<br>(<u>在php.ini 中的设置 zend.assertions = 1 开启断言【推荐】</u>;或者在 conf/app.ini 中设置 assert.active = 0 关闭此警告【不推荐】。)\n<br>In development environment, please open assertion for php7 to debug ! \n<br> (set 'zend.assertions = 1' in [php.ini][recommended]; or set 'assert.active = 0' in [conf/app.ini] to ignore this [not recommender].)");
			}

			//for php7
			ini_set('zend.assertions', 0);
			ini_set('assert.exception', 0);

			assert_options(ASSERT_ACTIVE, TRUE);
			//断言错误回调
			$assert_callback = function ($script, $line, $code, $message = null)
			{
				echo "断言错误触发：\n<br>", '<b>', $message, "</b><br>\n", "【断言错误是在正常逻辑中不应出现的错误，生产环境关闭断言提高性能】\n<br>",
					"触发位置$script 第$line 行 判断逻辑 $code (这里通常不是错误位置，是错误的调用方式或者参数引起的，请仔细检查)";
				exit;
			};
			assert_options(ASSERT_CALLBACK, $assert_callback);

		}
		else
		{
			assert_options(ASSERT_ACTIVE, FALSE);
		}
		assert_options(ASSERT_QUIET_EVAL, TRUE);
		assert_options(ASSERT_WARNING, $assert['warning']);
		assert_options(ASSERT_BAIL, $assert['bail']);
	}

	/**
	 * 开启调试输出
	 * @method _initDebug
	 * @author NewFuture
	 */
	public function _initDebug()
	{
		if ($debug = Config::get('debug'))
		{
			error_reporting(E_ALL); //错误回传
			switch (strtolower($debug))
			{
				case 'dump': //直接输出错误
					ini_set('display_errors', 1);
					break;

				case 'log': //log到文件
					ini_set('log_errors', 1);
					ini_set('error_log', Config::get('tempdir') . '/error_log.txt');
					break;

				default:
					exit("未知调试类型设置,请检查[conf/app.ini]中的debug参数配置\n<br>unkown debug type: $debug . check 'debug' setting in [conf/app.ini]");
			}
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
		if ($routes = Config::get('routes'))
		{
			$dispatcher->getRouter()->addConfig($routes);
		}
	}
}