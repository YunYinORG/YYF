<?php
/**
 * YYF - A simple, secure, and efficient PHP RESTful Framework.
 *
 * @link https://github.com/YunYinORG/YYF/
 *
 * @license Apache2.0
 * @copyright 2015-2017 NewFuture@yunyin.org
 */
// | FileName:  Encrypt.php
// |  加密函数库 使用前需对数据格式进行检查防止异常
// | 改用openssl 函数
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
// | version： 3.0
// +------------------------------------------------------------------
// | Copyright (c) 2014 ~ 2016云印南天团队 All rights reserved.
// +------------------------------------------------------------------

/**
 * 加密类
 *
 * @author NewFuture
 */
class Encrypt
{
    //最大混淆ID限制
    const MAX_ID = 5000;

    const METHOD = 'aes-256-ctr';

    //配置，key
    private static $_config = null;

    /**
     * 路径安全base64编码
     * +,=,/替换成-,_,.
     *
     * @param string $str [编码前字符串]
     *
     * @return string [安全base64编码后字符串]
     */
    public static function base64Encode($str)
    {
        return strtr(base64_encode($str), array('+' => '-', '=' => '_', '/' => '.'));
    }

    /**
     * 路径安全形base64解码
     *
     * @param string $str [解码前字符串]
     *
     * @return string [安全base64解码后字符串]
     */
    public static function base64Decode($str)
    {
        return base64_decode(strtr($str, array('-' => '+', '_' => '=', '.' => '/')));
    }

    /**
     * aesEncode($data, $key)
     * AES加密函数,$data引用传真，直接变成密码
     * 改用openssl加密
     *
     * @param string $data         原文
     * @param string $key          密钥
     * @param bool   $safe64=false 是否进行安全Base64编码
     * @param mixed  $safeB64
     *
     * @return string [加密后的密文 或者 base64编码后密文]
     */
    public static function aesEncode($data, $key, $safe64 = false)
    {
        // openssl_cipher_iv_length(self::METHOD);
        $iv   = openssl_random_pseudo_bytes(16);
        $data = $iv.openssl_encrypt($data, Encrypt::METHOD, $key, true, $iv);
        return $safe64 ? Encrypt::base64Encode($data) : $data;
    }

    /**
     * aes_decode(&$cipher, $key)
     *  aes解密函数,$cipher引用传真也会改变
     *
     * @param string $cipher       密文
     * @param string $key          密钥
     * @param bool   $safe64=false 是否是安全Base64编码的密文
     *
     * @return string 解密后的明文
     */
    public static function aesDecode($cipher, $key, $safe64 = false)
    {
        if ($cipher) {
            $safe64 and $cipher = Encrypt::base64Decode($cipher);

            $iv     = substr($cipher, 0, 16);
            $cipher = substr($cipher, 16);
            return openssl_decrypt($cipher, Encrypt::METHOD, $key, true, $iv);
        }
    }

    /**
     *  encryptEmail($email)
     *  可逆加密邮箱
     *  保留首字母和@之后的内容
     *
     * @param string $email 邮箱
     *
     * @return string 加密后的邮箱
     */
    public static function encryptEmail($email)
    {
        if (!$email) {
            return $email;
        }
        list($name, $domain) = explode('@', $email);
        //aes加密
        $name2 = substr($name, 1);
        if ($name2) {
            /*aes 安全加密*/
            $name2 = Encrypt::aesEncode($name2, Encrypt::config('key_email'), true);
        } else {
            $name2 = rand(); //对于用户名只有一个的邮箱生成随机数掩盖
        }
        return $name[0].$name2.'@'.$domain;
    }

    /**
     *  decryptEmail($email)
     *  解密邮箱
     *
     * @param string $email 邮箱
     *
     * @return string 解密后的邮箱
     */
    public static function decryptEmail($email)
    {
        if (!$email) {
            return $email;
        }
        list($name, $domain) = explode('@', $email);
        $name2               = substr($name, 1);
        if (strlen($name2) < 24) {
            //长度小于24为随机掩码直接去掉
            $name2 = '';
        } else {
            /*aes安全解码*/
            $name2 = Encrypt::aesDecode($name2, Encrypt::config('key_email'), true);
        }
        $email = $name[0].trim($name2).'@'.$domain;
        return $email;
    }

    /**
     *  encrypt_phone($phone, $salt, $id)
     *  手机号格式保留加密
     *  根据不同长度采用不同方式加密,
     *  所有手机号加密后四位的加密方式全局一致
     *  大于10位[6(2混淆+4)+4] 大于8位[4+4] 其他[4]
     *
     * @param string $phone 手机号[4位以上]
     * @param string $salt  字符串用于混淆密钥
     * @param int    $id    用户id,在1~100000之间的整数,用于混淆原文
     *
     * @return string 加密后的手机号
     */
    public static function encryptPhone($phone, $salt = null, $id = null)
    {
        $len = strlen($phone);
        if ($len > 10) {
            /*手机号长度大于10位*/
            if ($salt && $id) {
                /*拆分6(双混淆)+4加密*/
                $mid = substr($phone, -10, 6);
                $end = substr($phone, -4);
                return substr($phone, 0, -10).Encrypt::_encryptMid($mid, $salt, $id).Encrypt::encryptPhoneTail($end);
            }
            throw new Exception('超长手机号加密手机参数不足 salt 和 sid 混淆必须');
        } elseif ($len >= 8 && is_numeric($phone[0])) {
            /*手机号数字部分长度大于8-10位*/
            if ($salt) {
                /*拆分4(单混淆)+4加密*/
                $mid = substr($phone, -8, 4);
                $end = substr($phone, -4);
                return substr($phone, 0, -8).Encrypt::_encryptShortMid($mid, $salt).Encrypt::encryptPhoneTail($end);
            }
            throw new Exception('长手机号加密手机混淆参数salt 必须');
        } elseif ($len > 4) {
            /*4到7位手机号,只加密后4位，不混淆*/
            return substr($phone, 0, -4).Encrypt::encryptPhoneTail(substr($phone, -4));
        } elseif ($len == 0) {
            /*空直接返回*/
            return $phone;
        }
        throw new Exception('此手机号无法加密');
    }

    /**
     *  dncrypt_phone($phone, $salt, $id)
     *  手机号格式保留解密
     *
     * @param string $phone 11位手机号
     * @param string $salt  用户编号
     * @param int    $id    唯一混淆ID,
     *
     * @return string (11) 加密后的手机号
     */
    public static function decryptPhone($phone, $salt, $id)
    {
        $len = strlen($phone);
        if ($len > 10) {
            /*手机号长度大于10位*/
            if ($salt && $id) {
                /*拆分6(双混淆)+4加密*/
                $mid = substr($phone, -10, 6);
                $end = substr($phone, -4);
                return substr($phone, 0, -10).Encrypt::_decryptMid($mid, $salt, $id).Encrypt::_decryptEnd($end);
            }

            throw new Exception('超长手机号解密参数不足 salt 和 sid 混淆必须');
        } elseif ($len >= 8 && is_numeric($phone[0])) {
            /*手机号数字部分长度大于8-10位*/
            if ($salt) {
                /*拆分4(单混淆)+4加密*/
                $mid = substr($phone, -8, 4);
                $end = substr($phone, -4);
                return substr($phone, 0, -8).Encrypt::_decryptShortMid($mid, $salt).Encrypt::_decryptEnd($end);
            }

            throw new Exception('长手机号解密参数不足,$salt必须');
        } elseif ($len > 4) {
            /*4到7位手机号,只加密后4位，不混淆*/
            return substr($phone, 0, -4).Encrypt::_decryptEnd(substr($phone, -4));
        } elseif ($len == 0) {
            /*空直接返回*/
            return $phone;
        }
        throw new Exception('此手机号无法解密');
    }

    /**
     *  encryptPhoneTail($endNum)
     *  4位尾号加密
     *
     * @param string $endNum 4位尾号
     *
     * @return string (4) 加密后的4位数字
     */
    public static function encryptPhoneTail($endNum)
    {
        if (strlen($endNum) !== 4) {
            throw new Exception('尾号不是4位数');
            return false;
        }
        $key    = Encrypt::config('key_phone_end'); //获取配置密钥
        $endNum = (int) $endNum;
        $cipher = Encrypt::aesEncode($endNum, $key); //对后四位进行AES加密
        /*加密后内容查找密码表进行匹配*/
        $table      = Encrypt::_cipherTable($key);
        $encryption = array_search($cipher, $table);

        if (false === $encryption) {
            //密码表查找失败
            //抛出异常
            throw new Exception('加密手机参数不足');
            return false;
        }
        return sprintf('%04s', $encryption); //转位4位字符串,不足4位补左边0
    }

    /**
     *  encrypt_mid($midNum, $snum, $id)
     *  中间6位数加密
     *
     * @param string $midNum 6位数字
     * @param string $snum   编号字符串,用于混淆密钥
     * @param int    $id     用户id,在1~100000之间的整数,用于混淆原文
     *
     * @return string (6) 加密后的6位数字
     */
    private static function _encryptMid($midNum, $snum, $id)
    {
        $key   = Encrypt::config('key_phone_mid'); //获取配置密钥
        $key   = substr($snum.$key, 0, 32);   //混淆密钥,每个人的密钥均不同
        $table = Encrypt::_cipherTable($key);
        //拆成两部分进行解密
        $midNum += $id % Encrypt::MAX_ID;
        $mid2 = (int) substr($midNum, 2, 4);
        //后4位加密
        $mid2 = array_search(Encrypt::aesEncode($mid2, $key), $table);
        if (false === $mid2) {
            //前密码表查找失败
            throw new Exception('中间加密异常,密码表匹配失败');
        }
        $mid2 = sprintf('%04s', $mid2);
        return substr_replace($midNum, $mid2, 2);
    }

    /**
     *  _encryptShortMid($midNum, $snum)
     *  短加密中间4位数加密
     *
     * @param string $midNum 4位数字
     * @param string $snum   编号字符串,用于混淆密钥
     *
     * @return string (4) 加密后的4位数字
     */
    private static function _encryptShortMid($midNum, $snum)
    {
        $key   = Encrypt::config('key_phone_mid'); //获取配置密钥
        $key   = substr($snum.$key, 0, 32);   //混淆密钥,每个人的密钥均不同
        $table = Encrypt::_cipherTable($key);
        //后4位加密
        $midNum = array_search(Encrypt::aesEncode($midNum, $key), $table);
        if (false === $midNum) {
            //前密码表查找失败
            throw new Exception('中间加密异常,密码表匹配失败');
        }
        return sprintf('%04s', $midNum);
    }

    /**
     * decrypt_end($encodeEnd)
     *  4位尾号解密
     *
     * @param string $encodeEnd 加密后4位尾号
     *
     * @return string (4) 解密后的后四位
     */
    private static function _decryptEnd($encodeEnd)
    {
        $key   = Encrypt::config('key_phone_end'); //获取配置密钥
        $table = Encrypt::_cipherTable($key);    //读取密码表

        $end    = intval($encodeEnd);
        $cipher = $table[$end];//获取对应aes密码
        if (!$cipher) {
            throw new Exception('尾号密码查找失败');
        }
        $endNum = Encrypt::aesDecode($cipher, $key); //对密码进行解密
        return sprintf('%04s', intval($endNum));
    }

    /**
     *  中间6位数解密函数
     *
     * @param string $midEncode 加密后的6位数字
     * @param string $snum      编号字符串,用于混淆密钥
     * @param int    $id        用户id,在1~100000之间的整数,用于混淆原文
     *
     * @return int 解密后的6位数字
     */
    private static function _decryptMid($midEncode, $snum, $id)
    {
        //获取密码表
        $key   = Encrypt::config('key_phone_mid');
        $key   = substr($snum.$key, 0, 32);
        $table = Encrypt::_cipherTable($key);
        //解密
        $mid2 = substr($midEncode, 2, 4);
        $mid2 = $table[intval($mid2)];
        $mid2 = sprintf('%04s', Encrypt::aesDecode($mid2, $key));
        //还原
        $num = substr_replace($midEncode, $mid2, 2);
        $num -= $id % Encrypt::MAX_ID;
        return $num;
    }

    /**
     *  短加密中间4位数解密
     *
     * @param string $midEncode 4位数字
     * @param string $snum      编号字符串,用于混淆密钥
     *
     * @return string (4) 加密后的4位数字
     */
    private static function _decryptShortMid($midEncode, $snum)
    {
        $key   = Encrypt::config('key_phone_mid'); //获取配置密钥
        $key   = substr($snum.$key, 0, 32);   //混淆密钥,每个人的密钥均不同
        $table = Encrypt::_cipherTable($key);

        $mid    = $midEncode;
        $cipher = $table[intval($mid)];
        if (!$cipher) {
            throw new Exception('中间4位密码解密查找失败');
        }
        $mid = Encrypt::aesDecode($cipher, $key); //对密码进行解密
        return sprintf('%04s', intval($mid));
    }

    /**
     *  获取密码表
     *  现在缓存中查询,如果存在,则直接读取,否则重新生成
     *
     * @param string $key 加密的密钥
     *
     * @return array 密码映射表
     */
    private static function _cipherTable($key)
    {
        $tableName = 'et_'.urlencode($key); //缓存表名称
        if ($table = Kv::get($tableName)) {
            /*读取缓存中的密码表*/
            $table = unserialize($table);
        } else {
            for ($i = 0; $i < 10000; ++$i) {
                $table[] = openssl_encrypt($i, 'aes-256-ecb', $key, true);
            }
            sort($table);                           //根据加密后内容排序得到密码表
            Kv::set($tableName, serialize($table)); //缓存密码表
        }
        return $table;
    }

    /**
     * 读取配置
     *
     * @param string $key [配置变量名]
     *
     * @return mixed [配置信息]
     */
    private static function config($key)
    {
        if (null == Encrypt::$_config) {
            Encrypt::$_config = Config::getSecret('encrypt');
        }
        return Encrypt::$_config->get($key);
    }
}
