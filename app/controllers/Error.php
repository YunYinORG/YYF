<?php
/**
 * 错误处理
 */
class ErrorController extends Yaf_Controller_Abstract
{
    public function errorAction($exception)
    {
        Yaf_Dispatcher::getInstance()->disableView();
        $code = $exception->getCode()?:500;
        $url  = $this->_request->getRequestUri();
    
        Logger::error('[exception: {code}]({url}): {exception}', array(
            'code'     => $code,
            'url'      => $url,
            'exception'=> $exception->__toString(),
        ));
    
        $response['status'] = -10;
        $response['info']   = ['code' => $code, 'msg' => '请求异常！', 'uri' => $url];

        header('Content-Type: application/json; charset=utf-8', true, $code);
        echo json_encode($response, JSON_UNESCAPED_UNICODE); //unicode不转码
    }
}
