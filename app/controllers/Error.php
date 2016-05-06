<?php
/**
 * 错误处理
 */
class ErrorController extends Yaf_Controller_Abstract
{
	public function errorAction($exception = null)
	{
		Yaf_Dispatcher::getInstance()->disableView();
		$code    = $exception->getCode();
		$message = $exception->getMessage();
		$url     = $this->_request->getRequestUri();
		Log::write('[' . $code . '](' . $url . '):' . $message, 'ERROR');
		$response['status'] = -10;
		$response['info']   = ['code' => $code, 'msg' => '请求异常！', 'uri' => $url];
		header('HTTP/1.1 ' . $code);
		header('Content-type: application/json');
		echo json_encode($response, JSON_UNESCAPED_UNICODE); //unicode不转码
	}
}