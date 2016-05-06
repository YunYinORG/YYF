<?PHP
/**
 * RSA加密解密
 * 依赖openssl扩展
 */
class Rsa
{
	/**
	 * 获取公钥文件
	 * @method pubKey
	 * @return string
	 * @author NewFuture
	 */
	public static function pubKey()
	{
		return (Kv::get('RSA_life_time') > $_SERVER['REQUEST_TIME'] && $key = kv::get('RSA_pub_key')) ? $key : self::init();
	}

	/**
	 * 解密
	 * @method decode
	 * @return string
	 * @author NewFuture
	 */
	public static function decode($str)
	{
		$str = base64_decode($str);
		if ($key = Kv::get('RSA_pri_key'))
		{
			$pri_key = openssl_pkey_get_private($key);
			return openssl_private_decrypt($str, $decrypted, $pri_key) ? $decrypted : false;
		}
		return false;
	}

	/**
	 * 加密
	 * @method encode
	 * @param  [type] $str [原文]
	 * @return string
	 * @author NewFuture
	 */
	public static function encode($str)
	{
		$pub = openssl_pkey_get_public(self::pubKey());
		return openssl_public_encrypt($str, $crypttext, $pub) ? base64_encode($crypttext) : false;
	}

	/**
	 * 生成和保存密钥对
	 * @method init
	 * @param  boolean $return_pri [返回公钥或者私钥]
	 * @return [string]              [公钥或者私钥]
	 * @author NewFuture
	 */
	private static function init($return_pri = false)
	{
		$res = openssl_pkey_new();
		openssl_pkey_export($res, $pri);
		$d   = openssl_pkey_get_details($res);
		$pub = $d['key'];

		$time = time() + Config::get('rsa.lifetime') ?: 604800;

		kv::set('RSA_life_time', $time);
		kv::set('RSA_pri_key', $pri);
		Kv::set('RSA_pub_key', $pub);
		return $return_pri ? $pri : $pub;
	}
}