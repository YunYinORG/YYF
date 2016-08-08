<?php
namespace Service;
use \Logger as Log;
/**
 * 上传文件管理
 * 封装七牛API
 */
class Qiniu
{
	const QINIU_RS  = 'http://rs.qbox.me';
	static $_config = null;

	/**
	 * 获取文件
	 * @method download
	 * @param  string $domain 域名
	 * @param  string $name  [文件名]
	 * @param  string $param [附加参数]
	 * @return string        url
	 * @author NewFuture
	 */
	public static function download($domain, $name, $param = [])
	{
		$url   = $domain . $name . '?' . http_build_query($param);
		$token = self::sign($url);
		return $url . '&token=' . $token;
	}

	/**
	 * 重命名【移动】
	 * @method move
	 * @param  [type] $from     [description]
	 * @param  [type] $to [description]
	 * @author NewFuture
	 */
	public static function move($from, $to)
	{
		// $bucket = $this->_config['bucket'];
		$op = '/move/' . self::qiniuEncode($from) . '/' . self::qiniuEncode($to);
		return self::opration($op);
	}

	/**
	 * 复制文件
	 * @method copy
	 * @param  [type] $file     [description]
	 * @param  [type] $copyName [description]
	 * @return [type]           [description]
	 * @author NewFuture
	 */
	public static function copy($from, $saveas)
	{
		// $bucket = $this->_config['bucket'];
		$op = '/copy/' . self::qiniuEncode($from) . '/' . self::qiniuEncode($saveas);
		return self::opration($op);
	}

	/**
	 * 获取token
	 * @method getToken
	 * @param  [type]   $uri     [description]
	 * @param  integer  $timeout [description]
	 * @return [type]            [description]
	 * @author NewFuture
	 */
	public static function getToken($bucket, $key, $max = 10485760, $timeout = 600)
	{
		$setting = array(
			'scope' => $bucket,
			'saveKey' => $key,
			'deadline' => $timeout + $_SERVER['REQUEST_TIME'],
			'fsizeLimit' => intval($max),
		);
		$setting = self::qiniuEncode(json_encode($setting));
		return self::sign($setting) . ':' . $setting;
	}

	/**
	 * 删除
	 * @method delete
	 * @param  string $file [文件名]
	 * @return bool      [description]
	 * @author NewFuture
	 */
	public static function delete($uri)
	{
		$file = self::qiniuEncode($uri);
		return self::opration('/delete/' . $file);
	}

	/**
	 * 判断文件是否存在
	 * @param  [type]  $bucket [description]
	 * @param  [type]  $key    [description]
	 * @return boolean         [description]
	 */
	public static function has($uri)
	{
		$op = '/stat/' . self::qiniuEncode($uri);
		return self::opration($op);
	}

	/**
	 * 转pdf
	 * @param  [type] $file     [description]
	 * @param  [type] $saveName [description]
	 * @return [type]           [description]
	 */
	public static function toPdf($bucket, $key, $saveas)
	{
		$API  = 'http://api.qiniu.com';
		$op   = '/pfop/';
		$data = 'bucket=' . $bucket . '&key=' . $key . '&fops=yifangyun_preview|saveas/' . self::qiniuEncode($saveas);
		return self::opration($op, $data, $API);
	}

	/**
	 * 七牛操作
	 * @method opration
	 * @param  string   $op [操作命令]
	 * @return bool     	[操作结果]
	 * @author NewFuture
	 */
	private static function opration($op, $data = null, $host = self::QINIU_RS)
	{
		$token  = self::sign(is_string($data) ? $op . "\n" . $data : $op . "\n");
		$url    = $host . $op;
		$header = array('Authorization: QBox ' . $token);

		if ($ch = curl_init($url))
		{
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
			if($data)
			{
				curl_setopt($ch, CURLOPT_POST, true);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
			}
			curl_setopt($ch, CURLOPT_HEADER, 1);
			$response = curl_exec($ch);
			$status   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			curl_close($ch);

			if ($status == 200)
			{
				return true;
			}
			// elseif (\Config::get('debug'))
			// {
			// 	/*操作出错*/
			// 	\PC::debug($response, '七牛请求出错');
			// }
		}
		Log::write('[QINIU]七牛错误' . $url . ':' . ($response ?: '请求失败'), 'ERROR');
		return false;
	}

	/**
	 * 获取url签名
	 * @method sign
	 * @param  [type] $url [description]
	 * @return [type]      [description]
	 * @author NewFuture
	 */
	private static function sign($url)
	{
		$config = self::$_config ?: (self::$_config = \Config::getSecret('qiniu'));
		$sign   = hash_hmac('sha1', $url, $config['secretkey'], true);
		$ak     = $config['accesskey'];
		return $ak . ':' . self::qiniuEncode($sign);
	}

	/**
	 * 七牛安全编码
	 */
	private static function qiniuEncode($str)
	{
		return strtr(base64_encode($str), ['+' => '-', '/' => '_']);
	}
}