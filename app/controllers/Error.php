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
        $data = array(
            'code'     => $code,
            'uri'      => $this->_request->getRequestUri(),
            'exception'=> $exception->__toString(),
        );
        Logger::error('[exception: {code}]({uri}): {exception}', $data);

        if ('dev' !== Yaf_Application::app()->environ()) {
            //非开发环境下错误打码
            $data['exception']='请求异常！';
        }
        try {
            $rest=Config::get('rest');
        } catch (Exception $e) {
            //配置读取异常
            $data['exception'] .= '[配置获取出错]';
            $rest=array(
                'status' => 'status',
                'data'   => 'data',
                'error'  => -10,
                'json'   => JSON_UNESCAPED_UNICODE,
            );
        }

        header('Content-Type: application/json; charset=utf-8', true, $code);
        echo json_encode(
            array(
                $rest['status'] => intval($rest['error']),
                $rest['data']   => $data,
            ),
            $rest['json']
        ); //json encode 输出错误状态
    }
}
