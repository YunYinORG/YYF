<?php
// ===================================================================
// | FileName:  Encrypt.php
// |  加密函数库 使用前需对数据格式进行检查防止异常
// |  必须开启mcrypt扩展（兼容任意版本）
// ===================================================================
// # 手机号格式保留加密
// ## 核心思想是格式保留加密，核心加密算法AES可逆加密
// ## 加密步骤：
// #### 尾号四位全局统一加密（通过相同尾号查找手机号或者去重）
// #### 中间六位单独混淆加密前两位基本保留,后四位加密 （每个人的密码表唯一）
//
// # 邮箱加密邮箱
//   只对邮箱用户名加密（@字符之前的）并保留第一个字符
//   邮箱长度限制：63位（保留一位）
//   邮箱用户名（@之前的）长度限制：17位（加密后25位）
//   邮箱域名（@之后）长度限制：37位
// ## 加密原理
// #### 截取用户名——>AES加密——>base64转码 ——>特殊字符替换——>字符拼接
// +------------------------------------------------------------------
// | author： NewFuture
// | version： 1.1
// +------------------------------------------------------------------
// | Copyright (c) 2014 ~ 2015云印南天团队 All rights reserved.
// +------------------------------------------------------------------

class Encrypt
{
	//配置，key
	private static $_config = null;

	/**
	 * 加密密码
	 * @method encodePwd
	 * @param  [string]    $pwd [密码]
	 * @param  [string]    $salt  [混淆salt]
	 * @return [string]         [加密后的32字符串]
	 * @author NewFuture
	 */
	public static function encryptPwd($pwd, $salt)
	{
		return md5(crypt($pwd, $salt));
	}

	/**
	 * 路径安全base64编码
	 * @method base64Encode
	 * @param  string       $str [编码前字符串]
	 * @return string                  [编码后字符串]
	 * @author NewFuture
	 */
	public static function base64Encode($str)
	{
		return strtr(base64_encode($str), array('+' => '_', '=' => '.', '/' => '-'));
	}

	/**
	 * 路径安全形base64解码
	 * @method base64Decode
	 * @param  string      $tr [解码前字符串]
	 * @return string           [解码后字符串]
	 * @author NewFuture
	 */
	public static function base64Decode($str)
	{
		return base64_decode(strtr($str, array('_' => '+', '.' => '=', '-' => '/')));
	}

	/**
	 * aes_encode(&$data, $key)
	 *  aes加密函数,$data引用传真，直接变成密码
	 *  采用mcrypt扩展,为保证一致性,初始向量设为0
	 * @param &$data 原文
	 * @param $key 密钥
	 * @return string(16) 加密后的密文
	 */
	public static function aesEncode($data, $key, $safe_view = false)
	{
		$td = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_ECB, '');
		mcrypt_generic_init($td, $key, '0000000000000000');
		$data = mcrypt_generic($td, $data);
		mcrypt_generic_deinit($td);
		return $safe_view ? self::base64Encode($data) : $data;
	}

	/**
	 * aes_decode(&$cipher, $key)
	 *  aes解密函数,$cipher引用传真也会改变
	 * @param &$cipher 密文
	 * @param $key 密钥
	 * @return string 解密后的明文
	 */
	public static function aesDecode($cipher, $key, $safe_view = false)
	{
		$cipher = $safe_view ? self::base64Decode($cipher) : trim($cipher);
		$td     = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_ECB, '');
		mcrypt_generic_init($td, $key, '0000000000000000');
		$cipher = mdecrypt_generic($td, $cipher);
		mcrypt_generic_deinit($td);
		$cipher = trim($cipher);
		return $cipher;
	}

	/**
	 *  encrypt_email($email)
	 *  加密邮箱
	 * @param $email 邮箱
	 * @return string 加密后的邮箱
	 */
	public static function encryptEmail($email)
	{
		if (!$email)
		{
			return $email;
		}
		list($name, $domain) = explode('@', $email);
		//aes加密
		$name2 = substr($name, 1);
		if ($name2)
		{
			/*aes 安全加密*/
			$name2 = self::aesEncode($name2, self::config('key_email'), true);
		}
		else
		{
			$name2 = rand(); //对于用户名只有一个的邮箱生成随机数掩盖
		}
		return $name[0] . $name2 . '@' . $domain;
	}

	/**
	 *  decrypt_email(&$email)
	 *  解密邮箱
	 * @param $email 邮箱
	 * @return string 解密后的邮箱
	 */
	public static function decryptEmail($email)
	{
		if (!$email)
		{
			return $email;
		}
		list($name, $domain) = explode('@', $email);
		$name2               = substr($name, 1);
		if (strlen($name2) < 24)
		{
			//长度小于24为随机掩码直接去掉
			$name2 = '';
		}
		else
		{
			/*aes安全解码*/
			$name2 = self::aesDecode($name2, self::config('key_email'), true);
		}
		$email = $name[0] . trim($name2) . '@' . $domain;
		return $email;
	}

	/**
	 *  encrypt_phone($phone, $snum, $id)
	 *  手机号格式保留加密
	 * @param $phone string 11位手机号
	 * @param $snum string 用户编号字符串,用于混淆密钥
	 * @param $id int 用户id,在1~100000之间的整数,用于混淆原文
	 * @return string(11) 加密后的手机号
	 */
	public static function encryptPhone($phone, $snum, $id)
	{

		if (!$phone)
		{
			return $phone;
		}
		if ($snum && $id)
		{
			$mid = substr($phone, -10, 6);
			$end = substr($phone, -4);
			return substr($phone, 0, -10) . self::_encryptMid($mid, $snum, $id) . self::encryptPhoneTail($end);
		}
		else
		{
			throw new Exception('加密手机参数不足');
		}
	}

	/**
	 *  dncrypt_phone($phone, $snum, $id)
	 *  手机号格式保留解密
	 * @param $phone string 11位手机号
	 * @param $snum string 用户编号
	 * @param $id int 用户id,
	 * @return string(11) 加密后的手机号
	 */
	public static function decryptPhone($phone, $snum, $id)
	{
		if (!$phone)
		{
			return $phone;
		}
		if ($snum && $id)
		{
			$mid          = substr($phone, -10, 6);
			$end          = substr($phone, -4);
			return $phone = substr($phone, 0, -10) . self::_decryptMid($mid, $snum, $id) . self::_decryptEnd($end);
		}
		else
		{
			throw new Exception('加密手机参数不足');
		}
	}

	/**
	 *  encryptPhoneTail($endNum)
	 *  4位尾号加密
	 * @param $endNum 4位尾号
	 * @return string(4) 加密后的4位数字
	 */
	public static function encryptPhoneTail($endNum)
	{
		if (strlen($endNum) !== 4)
		{
			throw new Exception('尾号不是4位数');
			return false;
		}
		$key    = self::config('key_phone_end'); //获取配置密钥
		$endNum = (int) $endNum;
		$cipher = self::aesEncode($endNum, $key); //对后四位进行AES加密
		/*加密后内容查找密码表进行匹配*/
		$table      = self::_cipherTable($key);
		$encryption = array_search($cipher, $table);

		if (false === $encryption)
		{
			//密码表查找失败
			//抛出异常
			throw new Exception('加密手机参数不足');
			return false;
		}
		else
		{
			return sprintf('%04s', $encryption); //转位4位字符串,不足4位补左边0
		}
	}

	/**
	 *  encrypt_mid($midNum, $snum, $id)
	 *  中间6位数加密
	 * @param $midNum string 6位数字
	 * @param $snum string 编号字符串,用于混淆密钥
	 * @param $id int 用户id,在1~100000之间的整数,用于混淆原文
	 * @return string(6) 加密后的6位数字
	 */
	private static function _encryptMid($midNum, $snum, $id)
	{
		$key   = self::config('key_phone_mid'); //获取配置密钥
		$key   = substr($snum . $key, 0, 32);   //混淆密钥,每个人的密钥均不同
		$table = self::_cipherTable($key);
		//拆成两部分进行解密
		$midNum += $id;
		$mid2 = (int) substr($midNum, 2, 4);
		//后4位加密
		$mid2 = array_search(self::aesEncode($mid2, $key), $table);
		if (false === $mid2)
		{
			//前密码表查找失败
			throw new Exception('中间加密异常,密码表匹配失败');
		}
		else
		{
			$mid2 = sprintf('%04s', $mid2);
			return substr_replace($midNum, $mid2, 2);
		}
	}

	/**
	 * decrypt_end($encodeEnd)
	 *  4位尾号解密
	 * @param $encodeEnd 加密后4位尾号
	 * @return string(4) 解密后的后四位
	 */
	private static function _decryptEnd($encodeEnd)
	{
		$key   = self::config('key_phone_end'); //获取配置密钥
		$table = self::_cipherTable($key);      //读取密码表
		                                        //获取对应aes密码
		$end    = intval($encodeEnd);
		$cipher = $table[$end];
		if (!$cipher)
		{
			throw new Exception('尾号密码查找失败');
		}
		$endNum = (int) self::aesDecode($cipher, $key); //对密码进行解密
		return sprintf('%04s', $endNum);
	}

	/**
	 *  decrypt_mid($midEncode, $snum, $id)
	 *  中间6位数解密函数
	 * @param $midEncode string 加密后的6位数字
	 * @param $snum string 编号字符串,用于混淆密钥
	 * @param $id int 用户id,在1~100000之间的整数,用于混淆原文
	 * @return string(6)/int 解密后的6位数字
	 */
	private static function _decryptMid($midEncode, $snum, $id)
	{
		//获取密码表
		$key   = self::config('key_phone_mid');
		$key   = substr($snum . $key, 0, 32);
		$table = self::_cipherTable($key);
		//解密
		$mid2 = (int) substr($midEncode, 2, 4);
		$mid2 = $table[$mid2];
		$mid2 = sprintf('%04s', self::aesDecode($mid2, $key));
		//还原
		$num = substr_replace($midEncode, $mid2, 2);
		$num -= $id;
		return $num;
	}

	/**
	 * cipher_table($key)
	 *  获取密码表
	 *  现在缓存中查询,如果存在,则直接读取,否则重新生成
	 * @param $key 加密的密钥
	 * @return array 密码映射表
	 */
	private static function _cipherTable($key)
	{
		$tableName = 'et_' . $key;        //缓存表名称
		$table     = Kv::get($tableName); //读取缓存中的密码表
		if (!$table)
		{
			//密码表不存在则重新生成
			//对所有数字,逐个进行AES加密生成密码表
			$td = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_ECB, '');
			mcrypt_generic_init($td, $key, '0000000000000000');
			for ($i = 0; $i < 10000; ++$i)
			{
				$table[] = mcrypt_generic($td, $i);
			}
			mcrypt_generic_deinit($td);
			sort($table);                //根据加密后内容排序得到密码表
			Kv::set($tableName, $table); //缓存密码表
		}
		return $table;
	}

	/**
	 * 读取配置
	 * @method config
	 * @param  [string] $key [配置变量名]
	 * @return [mixed]      [description]
	 * @author NewFuture
	 */
	private static function config($key)
	{
		if (null == self::$_config)
		{
			$path          = Config::get('secret_config_path');
			self::$_config = (new Yaf_Config_Ini($path, 'encrypt'))->toArray();
		}
		return self::$_config[$key];
	}

}