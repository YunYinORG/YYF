<?php
/**
 * 短信发送
 */
namespace Service;
class Ucpaas
{
	private $_url = false;
	private $_appid;

	public function __construct($accountSid, $appId, $token)
	{
		$softVersion = '2014-06-30';
		$baseUrl     = 'https://api.ucpaas.com/';
		date_default_timezone_set('Asia/Shanghai');
		$timestamp    = date('YmdHis');
		$sig          = strtoupper(md5($accountSid . $token . $timestamp));
		$this->_appid = $appId;
		$this->_url   = $baseUrl . $softVersion . '/Accounts/' . $accountSid . '/Messages/templateSMS?sig=' . $sig;
		$this->auth   = trim(base64_encode($accountSid . ':' . $timestamp));
	}

	/**
	 * 连接服务器回尝试curl
	 * @param  $data          post数据
	 * @return mixed|string
	 */
	private function _connection($data)
	{

		$ch = curl_init($this->_url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json;charset=utf-8', 'Authorization:' . $this->auth));
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
		$result = curl_exec($ch);
		curl_close($ch);

		return $result;
	}

	/**
	 * 发送短信
	 * @param $phone      到达手机号
	 * @param $msg        短信参数
	 * @param $templateId 短信模板ID
	 */
	public function send($phone, $msg, $templateId)
	{
		$body_json = array(
			'templateSMS' => array(
				'appId' => $this->_appid,
				'templateId' => $templateId,
				'to' => $phone,
				'param' => $msg));
		$data = json_encode($body_json);

		$result = $this->_connection($data);
		$result = json_decode($result);
		return isset($result->resp->respCode) ? ($result->resp->respCode == 0) : false;
	}
}