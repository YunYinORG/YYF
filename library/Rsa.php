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
     * @param mixed $id 前缀用来区分多对key
     *
     * @return string
     */
    public static function pubKey($id = '')
    {
        $pair = Cache::get("rsa.${id}.pair") ?: Rsa::init($id);
        return $pair[1];
    }

    /**
     * 解密
     *
     * @param string $str 密文
     * @param mixed  $id  密钥前缀
     *
     * @return string 原文
     */
    public static function decode($str, $id = '')
    {
        $str = base64_decode($str);
        if ($pair = Cache::get("rsa.${id}.pair")) {
            $pri_key = openssl_pkey_get_private($pair[0]);
            return openssl_private_decrypt($str, $decrypted, $pri_key) ? $decrypted : false;
        }
    }

    /**
     * 加密
     *
     * @param string $str [原文]
     * @param mixed  $id  秘钥前缀
     *
     * @return string 加密后base64编码
     */
    public static function encode($str, $id = '')
    {
        $pub = openssl_pkey_get_public(Rsa::pubKey($id));
        return openssl_public_encrypt($str, $crypttext, $pub) ? base64_encode($crypttext) : false;
    }

    /**
     * 生成和保存密钥对
     *
     * @param mixed $id 前缀
     *
     * @return array [公钥和私钥对]
     */
    private static function init($id = '')
    {
        $res = openssl_pkey_new();
        openssl_pkey_export($res, $pri);
        $d    = openssl_pkey_get_details($res);
        $pair = array($pri,$d['key']);

        Cache::set("rsa.${id}.pair", $pair, Config::get('rsa.lifetime'));
        return $pair;
    }
}
