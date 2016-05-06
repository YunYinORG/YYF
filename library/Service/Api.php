<?php
namespace Service;
/**
 * 第三方服务接口
 */
class Api
{

	/**
	 * 连接远程服务器
	 * curl扩展未开启时尝试file_get_contents
	 * @method connect
	 * @param  string  $url    [服务器url地址]
	 * @param  array   $header [请求头]
	 * @param  string  $method [请求方式POST,GET]
	 * @param  string  $data   [附加数据，body]
	 * @return array($header,$body)[请求响应结果]
	 * @author NewFuture
	 */
	public static function connect($url, $header = array(), $method = 'POST', $data = '')
	{
		$response = null;
		if (function_exists('curl_init'))
		{
			if ($ch = curl_init($url))
			{
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
				if ($method == 'POST')
				{
					curl_setopt($ch, CURLOPT_POST, true);
					curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
				}
				curl_setopt($ch, CURLOPT_HEADER, 1);
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
				curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
				$result = curl_exec($ch);
				// $status   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
				curl_close($ch);

				if ($result)
				{
					list($header, $response['body']) = explode("\r\n\r\n", $result, 2);
					$response['header']              = explode("\r\n", $header);
				}
			}
		}
		else
		{
			throw new \Exception('不支持的请求curl');

			// $request['method']           = $method;
			// $request['protocol_version'] = '1.1';
			// $data                        = is_array($data) ? http_build_query($data) : $data;
			// $request['content']          = $data;
			// $http_header                 = [];
			// $http_header[]               = 'Content-length: ' . strlen($data);
			// // $http_header[]     = 'Content-Type:   application/x-www-form-urlencoded';
			// $http_header       = array_merge($http_header, $header);
			// $request['header'] = implode("\r\n", $http_header) . "\r\n\r\n";
			// $opts['http']      = $request;
			// var_dump($opts);
			// $response_body = @file_get_contents($url, false, stream_context_create($opts));
			// //$http_response_header 是系统变量，记录http头
			// if (isset($http_response_header) || $response_body)
			// {
			// 	$response['header'] = $http_response_header;
			// 	$response['body']   = $response_body;
			// }
		}

		return $response;
	}

}