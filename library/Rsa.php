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
 * Rsa 非对称加密管理类
 * 依赖openssl扩展
 *
 * @author NewFuture
 */
class Rsa
{
    /**
     * 获取公钥文件
     *
     * @param mixed $prefix 前缀用来区分多对key
     *
     * @return string
     */
    public static function pubKey($prefix = '')
    {
        $pair = Cache::get("${prefix}_pair.rsa") ?: Rsa::init($prefix);
        return $pair['pub'];
    }

    /**
     * 解密
     *
     * @param string $str    密文
     * @param mixed  $prefix 密钥前缀
     *
     * @return string 原文
     */
    public static function decode($str, $prefix = '')
    {
        $str = base64_decode($str);
        if ($pair = Cache::get("${prefix}_pair.rsa")) {
            $pri_key = openssl_pkey_get_private($key['pri']);
            return openssl_private_decrypt($str, $decrypted, $pri_key) ? $decrypted : false;
        }
        return false;
    }

    /**
     * 加密
     *
     * @param string $str    [原文]
     * @param mixed  $prefix 秘钥前缀
     *
     * @return string 加密后base64编码
     */
    public static function encode($str, $prefix = '')
    {
        $pub = openssl_pkey_get_public(Rsa::pubKey($prefix));
        return openssl_public_encrypt($str, $crypttext, $pub) ? base64_encode($crypttext) : false;
    }

    /**
     * 生成和保存密钥对
     *
     * @param mixed $prefix 前缀
     *
     * @return array [公钥和私钥对]
     */
    private static function init($prefix = '')
    {
        $res = openssl_pkey_new();
        openssl_pkey_export($res, $pri);
        $d    = openssl_pkey_get_details($res);
        $pair =array(
            'pub'=> $d['key'],
            'pri'=> $pri,
        );

        Cache::set("${prefix}_rsa.pair", $pair, Config::get('rsa.lifetime'));
        return $pair;
    }
}
