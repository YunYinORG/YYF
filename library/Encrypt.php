<?php
// ===================================================================
// | FileName:  Encrypt.php
// |  加密函数库 使用前需对数据格式进行检查防止异常
// |  必须开启mcrypt扩展（兼容任意版本）
// ===================================================================
// # 电话号格式保留加密
//   加密尾巴4位到10位(根据长度而定)
//   支持带+和不带+号的国际区号前缀
//   不支持带[空格]或者-或者括号的分隔符
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
// | version： 2.0
// +------------------------------------------------------------------
// | Copyright (c) 2014 ~ 2016云印南天团队 All rights reserved.
// +------------------------------------------------------------------

class Encrypt
{
	//配置，key
	private static $_config = null;

	//最大混淆ID限制
	const MAX_ID = 5000;

	/**
	 * 加密密码
	 * @method encodePwd
	 * @param  [string]    $pwd  [密码]
	 * @param  [string]    $salt [混淆salt]
	 * @return [string]          [加密后的32字符串]
	 * @author NewFuture
	 */
	public static function encryptPwd($pwd, $salt)
	{
		return md5(crypt($pwd, $salt));
	}

	/**
	 * 路径安全base64编码
	 * +,=,/替换成-,_,.
	 * @method base64Encode
	 * @param  string       $str [编码前字符串]
	 * @return string            [安全base64编码后字符串]
	 * @author NewFuture
	 */
	public static function base64Encode($str)
	{
		return strtr(base64_encode($str), array('+' => '-', '=' => '_', '/' => '.'));
	}

	/**
	 * 路径安全形base64解码
	 * @method base64Decode
	 * @param  string      $str [解码前字符串]
	 * @return string          [安全base64解码后字符串]
	 * @author NewFuture
	 */
	public static function base64Decode($str)
	{
		return base64_decode(strtr($str, array('-' => '+', '_' => '=', '.' => '/')));
	}

	/**
	 *  aes_encode(&$data, $key)
	 *  aes加密函数,$data引用传真，直接变成密码
	 *  采用mcrypt扩展,为保证一致性,初始向量设为0
	 * @param $data 原文
	 * @param $key 密钥
	 * @param $safe_view=false 是否进行安全Base64编码
	 * @return string [加密后的密文 或者 base64编码后密文]
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
	 * @param $safe_view=false 是否是安全Base64编码的密文
	 * @return string 解密后的明文
	 */
	public static function aesDecode($cipher, $key, $safe_view = false)
	{
		if ($cipher)
		{
			$safe_view AND $cipher = self::base64Decode($cipher);

			$td = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_ECB, '');
			mcrypt_generic_init($td, $key, '0000000000000000');
			$cipher = mdecrypt_generic($td, $cipher);
			mcrypt_generic_deinit($td);
			$cipher = trim($cipher);
			return $cipher;
		}
	}

	/**
	 *  encryptEmail($email)
	 *  可逆加密邮箱
	 *  保留首字母和@之后的内容
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
	 *  decryptEmail($email)
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
	 *  encrypt_phone($phone, $salt, $id)
	 *  手机号格式保留加密
	 *  根据不同长度采用不同方式加密,
	 *  所有手机号加密后四位的加密方式全局一致
	 *  大于10位[6(2混淆+4)+4] 大于8位[4+4] 其他[4]
	 * @param $phone string 手机号[4位以上]
	 * @param $salt string 字符串用于混淆密钥
	 * @param $id int 用户id,在1~100000之间的整数,用于混淆原文
	 * @return string(11) 加密后的手机号
	 */
	public static function encryptPhone($phone, $salt = null, $id = null)
	{
		$len = strlen($phone);
		if ($len > 10)
		{
			/*手机号长度大于10位*/
			if ($salt && $id)
			{
				/*拆分6(双混淆)+4加密*/
				$mid = substr($phone, -10, 6);
				$end = substr($phone, -4);
				return substr($phone, 0, -10) . self::_encryptMid($mid, $salt, $id) . self::encryptPhoneTail($end);
			}
			else
			{
				throw new Exception('超长手机号加密手机参数不足 salt 和 sid 混淆必须');
			}

		}
		elseif ($len >= 8 && is_numeric($phone[0]))
		{
			/*手机号数字部分长度大于8-10位*/
			if ($salt)
			{
				/*拆分4(单混淆)+4加密*/
				$mid = substr($phone, -8, 4);
				$end = substr($phone, -4);
				return substr($phone, 0, -8) . self::_encryptShortMid($mid, $salt) . self::encryptPhoneTail($end);
			}
			else
			{
				throw new Exception('长手机号加密手机混淆参数salt 必须');
			}
		}
		elseif ($len > 4)
		{
			/*4到7位手机号,只加密后4位，不混淆*/
			return substr($phone, 0, -4) . self::encryptPhoneTail(substr($phone, -4));
		}
		elseif ($len == 0)
		{
			/*空直接返回*/
			return $phone;
		}
		throw new Exception('此手机号无法加密');
	}

	/**
	 *  dncrypt_phone($phone, $salt, $id)
	 *  手机号格式保留解密
	 * @param $phone string 11位手机号
	 * @param $salt string 用户编号
	 * @param $id int 用户id,
	 * @return string(11) 加密后的手机号
	 */
	public static function decryptPhone($phone, $salt, $id)
	{
		$len = strlen($phone);
		if ($len > 10)
		{
			/*手机号长度大于10位*/
			if ($salt && $id)
			{
				/*拆分6(双混淆)+4加密*/
				$mid = substr($phone, -10, 6);
				$end = substr($phone, -4);
				return substr($phone, 0, -10) . self::_decryptMid($mid, $salt, $id) . self::_decryptEnd($end);
			}
			else
			{
				throw new Exception('超长手机号解密参数不足 salt 和 sid 混淆必须');
			}

		}
		elseif ($len >= 8 && is_numeric($phone[0]))
		{
			/*手机号数字部分长度大于8-10位*/
			if ($salt)
			{
				/*拆分4(单混淆)+4加密*/
				$mid = substr($phone, -8, 4);
				$end = substr($phone, -4);
				return substr($phone, 0, -8) . self::_decryptShortMid($mid, $salt) . self::_decryptEnd($end);
			}
			else
			{
				throw new Exception('长手机号解密参数不足,$salt必须');
			}
		}
		elseif ($len > 4)
		{
			/*4到7位手机号,只加密后4位，不混淆*/
			return substr($phone, 0, -4) . self::_decryptEnd(substr($phone, -4));
		}
		elseif ($len == 0)
		{
			/*空直接返回*/
			return $phone;
		}
		throw new Exception('此手机号无法解密');

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
		$midNum += $id % self::MAX_ID;
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
	 *  _encryptShortMid($midNum, $snum)
	 *  短加密中间4位数加密
	 * @param $midNum string 4位数字
	 * @param $snum string 编号字符串,用于混淆密钥
	 * @return string(4) 加密后的4位数字
	 */
	private static function _encryptShortMid($midNum, $snum)
	{
		$key   = self::config('key_phone_mid'); //获取配置密钥
		$key   = substr($snum . $key, 0, 32);   //混淆密钥,每个人的密钥均不同
		$table = self::_cipherTable($key);
		//后4位加密
		$midNum = array_search(self::aesEncode($midNum, $key), $table);
		if (false === $midNum)
		{
			//前密码表查找失败
			throw new Exception('中间加密异常,密码表匹配失败');
		}
		else
		{
			return sprintf('%04s', $midNum);
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
		$num -= $id % self::MAX_ID;
		return $num;
	}

	/**
	 *  _decryptShortMid($midNum, $snum)
	 *  短加密中间4位数解密
	 * @param $midNum string 4位数字
	 * @param $snum string 编号字符串,用于混淆密钥
	 * @return string(4) 加密后的4位数字
	 */
	private static function _decryptShortMid($midEncode, $snum)
	{
		$key   = self::config('key_phone_mid'); //获取配置密钥
		$key   = substr($snum . $key, 0, 32);   //混淆密钥,每个人的密钥均不同
		$table = self::_cipherTable($key);

		$mid    = intval($midEncode);
		$cipher = $table[$mid];
		if (!$cipher)
		{
			throw new Exception('中间4位密码解密查找失败');
		}
		$mid = (int) self::aesDecode($cipher, $key); //对密码进行解密
		return sprintf('%04s', $mid);
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
		$tableName = 'et_' . urlencode($key); //缓存表名称
		if ($table = Kv::get($tableName))
		{
			/*读取缓存中的密码表*/
			$table = unserialize($table);
		}
		else
		{
			/*密码表不存在则重新生成*/
			//对所有数字,逐个进行AES加密生成密码表
			$td = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_ECB, '');
			mcrypt_generic_init($td, $key, '0000000000000000');
			for ($i = 0; $i < 10000; ++$i)
			{
				$table[] = mcrypt_generic($td, $i);
			}
			mcrypt_generic_deinit($td);
			sort($table);                           //根据加密后内容排序得到密码表
			Kv::set($tableName, serialize($table)); //缓存密码表
		}
		return $table;
	}

	/**
	 * 读取配置
	 * @method config
	 * @param  [string] $key [配置变量名]
	 * @return [mixed]      [配置信息]
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