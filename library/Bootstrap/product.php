<?php
/**
 * YYF - A simple, secure, and high performance PHP RESTful Framework.
 *
 * @see https://github.com/YunYinORG/YYF/
 *
 * @license Apache2.0
 * @copyright 2015-2017 NewFuture@yunyin.org
 */

/**
 * Bootstrap 启动.
 *
 * @author NewFuture
 */
class Bootstrap extends Yaf_Bootstrap_Abstract
{
    /**
     * 初始化路由.
     *
     * @method _initRoute
     */
    public function _initRoute(Yaf_Dispatcher $dispatcher)
    {
        if ($routes = Config::get('routes')) {
            $dispatcher->getRouter()->addConfig($routes);
        }
    }
}
