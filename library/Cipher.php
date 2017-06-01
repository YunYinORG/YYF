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
// | 加密函数库 使用前需对数据格式进行检查防止异常
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
// | version： 3.2
// +------------------------------------------------------------------
// | Copyright (c) 2014 ~ 2017云印南天团队 All rights reserved.
// +------------------------------------------------------------------

/**
 * 加密类
 *
 * @author NewFuture
 */
class Cipher
{
    //最大混淆ID限制
    const MAX_ID = 5000;

    //配置，key
    private static $_config = null;

    /**
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
            $iv    = isset($domain[15]) ? substr($domain, 0, 16) : str_pad($domain, 16, $name[0]);
            $name2 = openssl_encrypt($name2, 'aes-256-ctr', Cipher::config('key_email'), 0, $iv);
            $name2 = strtr($name2, array('+' => '-', '=' => '_', '/' => '.'));
        } else {
            //对于用户名只有一个字符的邮箱生成hash进行掩盖
            $name2 = substr(current(unpack('H*', $domain)), 0, 2);
        }
        return $name[0].$name2.'@'.$domain;
    }

    /**
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
        if (isset($name2[3])) {
            /*aes安全解码*/
            $name2 = strtr($name2, array('-' => '+', '_' => '=', '.' => '/'));
            $iv    = isset($domain[15]) ? substr($domain, 0, 16) : str_pad($domain, 16, $name[0]);
            $name2 = openssl_decrypt($name2, 'aes-256-ctr', Cipher::config('key_email'), 0, $iv);
        } else {
            //长度小于24为随机掩码直接去掉
            $name2 = '';
        }
        $email = $name[0].trim($name2).'@'.$domain;
        return $email;
    }

    /**
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
    public static function encryptPhone($phone, $salt, $id = false)
    {
        $len = strlen($phone);
        if ($len > 10) {
            /*手机号长度大于10位*/
            if ($salt) {
                /*拆分6(双混淆)+4加密*/
                $mid = substr($phone, -10, 6);
                $end = substr($phone, -4);
                $id  = Cipher::_genId($salt, $id);
                return substr($phone, 0, -10).Cipher::_encryptMid($mid, $salt, $id).Cipher::encryptPhoneTail($end);
            }
            throw new Exception('超长手机号加密手机参数不足 salt混淆必须');
        } elseif ($len >= 8 && is_numeric($phone[0])) {
            /*手机号数字部分长度大于8-10位*/
            if ($salt) {
                /*拆分4(单混淆)+4加密*/
                $mid = substr($phone, -8, 4);
                $end = substr($phone, -4);
                return substr($phone, 0, -8).Cipher::_encryptShortMid($mid, $salt).Cipher::encryptPhoneTail($end);
            }
            throw new Exception('长手机号加密手机混淆参数salt 必须');
        } elseif ($len >= 4) {
            /*4到7位手机号,只加密后4位，不混淆*/
            return substr($phone, 0, -4).Cipher::encryptPhoneTail(substr($phone, -4));
        } elseif ($len == 0) {
            /*空直接返回*/
            return $phone;
        }
        throw new Exception('此手机号无法加密');
    }

    /**
     *  手机号格式保留解密
     *
     * @param string $phone 11位手机号
     * @param string $salt  用户编号
     * @param int    $id    唯一混淆ID,
     *
     * @return string (11) 加密后的手机号
     */
    public static function decryptPhone($phone, $salt, $id = false)
    {
        $len = strlen($phone);
        if ($len > 10) {
            /*手机号长度大于10位*/
            if ($salt) {
                /*拆分6(双混淆)+4加密*/
                $mid = substr($phone, -10, 6);
                $end = substr($phone, -4);
                $id  = Cipher::_genId($salt, $id);
                return substr($phone, 0, -10).Cipher::_decryptMid($mid, $salt, $id).Cipher::_decryptTail($end);
            }
            throw new Exception('超长手机号解密参数不足 salt 和 sid 混淆必须');
        } elseif ($len >= 8 && is_numeric($phone[0])) {
            /*手机号数字部分长度大于8-10位*/
            if ($salt) {
                /*拆分4(单混淆)+4加密*/
                $mid = substr($phone, -8, 4);
                $end = substr($phone, -4);
                return substr($phone, 0, -8).Cipher::_decryptShortMid($mid, $salt).Cipher::_decryptTail($end);
            }

            throw new Exception('长手机号解密参数不足,$salt必须');
        } elseif ($len >= 4) {
            /*4到7位手机号,只加密后4位，不混淆*/
            return substr($phone, 0, -4).Cipher::_decryptTail(substr($phone, -4));
        } elseif ($len == 0) {
            /*空直接返回*/
            return $phone;
        }
        throw new Exception('此手机号无法解密');
    }

    /**
     *  4位尾号加密
     *
     * @param string $endNum 4位尾号
     *
     * @return string (4) 加密后的4位数字
     */
    public static function encryptPhoneTail($endNum)
    {
        assert('strlen($endNum) == 4', '[Cipher::encryptPhoneTail]尾号不是4位数');
        $key    = Cipher::config('key_phone_end'); //获取配置密钥
        $table  = Cipher::_cipherTable($key);
        /*加密后内容查找密码表进行匹配*/
        //对后四位进行AES加密
        $endNum     = intval($endNum);
        $cipher     = openssl_encrypt($endNum, 'aes-256-ecb', $key, true);
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
     *  4位尾号解密
     *
     * @param string $encodeEnd 加密后4位尾号
     *
     * @return string (4) 解密后的后四位
     */
    private static function _decryptTail($encodeEnd)
    {
        $key    = Cipher::config('key_phone_end'); //获取配置密钥
        $table  = Cipher::_cipherTable($key);    //读取密码表
        $end    = intval($encodeEnd);
        $cipher = $table[$end];//获取对应aes密码
        if (!$cipher) {
            throw new Exception('尾号密码查找失败');
        }
        //对密码进行解密
        $endNum = openssl_decrypt($cipher, 'aes-256-ecb', $key, true);
        return sprintf('%04s', intval($endNum));
    }

    /**
     * @param mixed $salt
     * @param mixed $id
     */
    private static function _genId($salt, $id)
    {
        $id = $id ?: current(unpack('i', substr('0000'.$salt, -4)));
        return $id % Cipher::MAX_ID;
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
        $key   = Cipher::config('key_phone_mid'); //获取配置密钥
        $key   = substr($snum.$key, 0, 32);   //混淆密钥,每个人的密钥均不同
        $table = Cipher::_cipherTable($key);
        //拆成两部分进行解密
        assert('$midNum<1000000 && $id > 0', '[Cipher::_encryptMid] unexcepted midNum');
        $midNum = ($midNum + $id) % 1000000;
        //后4位加密
        $mid2 = $midNum % 10000;
        $key  = openssl_encrypt($mid2, 'aes-256-ecb', $key, true);
        $mid2 = array_search($key, $table);
        if (false === $mid2) {
            //前密码表查找失败
            throw new Exception('中间加密异常,密码表匹配失败');
        }
        $mid2 = sprintf('%04s', $mid2);
        return substr_replace($midNum, $mid2, 2);
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
        $key   = Cipher::config('key_phone_mid');
        $key   = substr($snum.$key, 0, 32);
        $table = Cipher::_cipherTable($key);
        //解密
        $mid2 = substr($midEncode, 2, 4);
        $mid2 = $table[intval($mid2)];
        $mid2 = openssl_decrypt($mid2, 'aes-256-ecb', $key, true);
        $mid2 = sprintf('%04s', $mid2);
        //还原
        $num = substr_replace($midEncode, $mid2, 2);
        $num = ($num - $id + 1000000) % 1000000;
        return $num;
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
        //获取配置密钥 //混淆密钥,每个人的密钥均不同
        $key    = substr($snum.Cipher::config('key_phone_mid'), 0, 32);
        $table  = Cipher::_cipherTable($key);
        $key    = openssl_encrypt($midNum, 'aes-256-ecb', $key, true);
        $midNum = array_search($key, $table);//后4位加密
        if (false === $midNum) {
            //前密码表查找失败
            throw new Exception('中间加密异常,密码表匹配失败');
        }
        return sprintf('%04s', $midNum);
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
        //获取配置密钥 //混淆密钥,每个人的密钥均不同
        $key    = substr($snum.Cipher::config('key_phone_mid'), 0, 32);
        $table  = Cipher::_cipherTable($key);
        $cipher = $table[intval($midEncode)];
        if (!$cipher) {
            throw new Exception('中间4位密码解密查找失败');
        }
        //对密码进行解密
        $mid = openssl_decrypt($cipher, 'aes-256-ecb', $key, true);
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
            sort($table);//根据加密后内容排序得到密码表
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
        if (null == Cipher::$_config) {
            Cipher::$_config = Config::getSecret('encrypt');
        }
        return Cipher::$_config->get($key);
    }
}
