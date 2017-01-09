<?PHP
/**
 * YYF - A simple, secure, and high performance PHP RESTful Framework.
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
     * @return string
     */
    public static function pubKey()
    {
        return (Kv::get('RSA_life_time') > $_SERVER['REQUEST_TIME'] && $key = kv::get('RSA_pub_key')) ? $key : self::init();
    }

    /**
     * 解密
     *
     * @param string $str 密文
     *
     * @return string
     */
    public static function decode($str)
    {
        $str = base64_decode($str);
        if ($key = Kv::get('RSA_pri_key')) {
            $pri_key = openssl_pkey_get_private($key);
            return openssl_private_decrypt($str, $decrypted, $pri_key) ? $decrypted : false;
        }
        return false;
    }

    /**
     * 加密
     *
     * @param string $str [原文]
     *
     * @return string
     */
    public static function encode($str)
    {
        $pub = openssl_pkey_get_public(self::pubKey());
        return openssl_public_encrypt($str, $crypttext, $pub) ? base64_encode($crypttext) : false;
    }

    /**
     * 生成和保存密钥对
     *
     *
     * @param  bool $return_pri [返回公钥或者私钥]
     *
     * @return string [公钥或者私钥]
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
