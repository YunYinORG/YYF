<?php
/**
 * YYF - A simple, secure, and efficient PHP RESTful Framework.
 *
 * @link https://github.com/YunYinORG/YYF/
 *
 * @license Apache2.0
 * @copyright 2015-2017 NewFuture@yunyin.org
 */

/**
 * AES加密解密封装类
 *
 * @author NewFuture
 */
class Aes
{
    const MOD = 'aes-256-ctr';

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
     * AES加密函数,$data引用传真，直接变成密码
     * 改用openssl加密
     *
     * @param string $data         原文
     * @param string $key          密钥
     * @param bool   $safe64=false 是否进行安全Base64编码
     *
     * @return string [加密后的密文 或者 base64编码后密文]
     */
    public static function encrypt($data, $key, $safe64 = false)
    {
        // openssl_cipher_iv_length(self::MOD);
        $iv   = openssl_random_pseudo_bytes(16);
        $data = $iv.openssl_encrypt($data, Aes::MOD, $key, true, $iv);
        return $safe64 ? Aes::base64Encode($data) : $data;
    }

    /**
     * aes解密函数
     *
     * @param string $cipher       密文
     * @param string $key          密钥
     * @param bool   $safe64=false 是否是安全Base64编码的密文
     *
     * @return string 解密后的明文
     */
    public static function decrypt($cipher, $key, $safe64 = false)
    {
        if ($cipher) {
            $safe64 and $cipher = Aes::base64Decode($cipher);

            $iv     = substr($cipher, 0, 16);
            $cipher = substr($cipher, 16);
            return openssl_decrypt($cipher, Aes::MOD, $key, true, $iv);
        }
    }
}
