<?php
/**
 * YYF - A simple, secure, and high performance PHP RESTful Framework.
 *
 * @link https://github.com/YunYinORG/YYF/
 *
 * @license Apache2.0
 * @copyright 2015-2017 NewFuture@yunyin.org
 */

/**
 * 错误处理控制器
 */
class ErrorController extends Yaf_Controller_Abstract
{
    /**
     * 错误处理函数
     *
     * @param Exception $exception 异常
     */
    public function errorAction($exception)
    {
        Yaf_Dispatcher::getInstance()->disableView();
        $code = $exception->getCode() ?: 500;
        $url  = $this->_request->getRequestUri();

        Logger::error('[exception: {code}]({url}): {exception}', array(
            'code'     => $code,
            'url'      => $url,
            'exception'=> $exception->__toString(),
        ));

        $response['status'] = -10;
        $response['info']   = array(
            'code' => $code,
            'msg'  => '请求异常！',
            'uri'  => $url
        );

        header('Content-Type: application/json; charset=utf-8', true, $code);
        echo json_encode($response, JSON_UNESCAPED_UNICODE); //unicode不转码
    }
}
