<?php
/**
 * YYF - A simple, secure, and high performance PHP RESTful Framework.
 *
 * @link https://github.com/YunYinORG/YYF/
 *
 * @license Apache2.0
 * @copyright 2015-2017 NewFuture@yunyin.org
 */

/**
 * 数据格式验证
 *
 * @author NewFuture
 *
 * @todo 函数化
 */
class Validate
{
    /**
     * 验证邮箱格式
     *
     * @param string &$email    邮箱
     * @param bool   $ignore_mx 是否验证MX记录(需要联网)
     *
     * @return bool 验证结果
     *
     * @todo 邮箱存在性验证 https://github.com/zytzagoo/smtp-validate-email
     */
    public static function email($email, $ignore_mx = false)
    {
        return preg_match(Config::get('regex.email'), $email)
        && ($ignore_mx || checkdnsrr(substr(strrchr($email, '@'), 1)));
    }

    /**
     * 验证手机格式
     *
     * @param string $phone
     */
    public static function phone($phone)
    {
        return preg_match(Config::get('regex.phone'), $phone);
    }

    /**
     * 验证账号格式
     *
     * @param string $account
     */
    public static function account($account)
    {
        return preg_match(Config::get('regex.account'), $account);
    }

    /**
     * 验证姓名格式
     *
     * @param string $name
     */
    public static function name($name)
    {
        return preg_match(Config::get('regex.name'), $name);
    }

    /**
     * 验证字符串是否仅由字母，_，数字线组成
     *
     * @param string $str
     */
    public static function char_num($str)
    {
        return ctype_alnum(strtr($str, '_', 'A'));
    }

    /**
     * 验证字符串是否仅由字母，_，数字线组成
     *
     * @param string $str
     */
    public static function fileName($str)
    {
        return ctype_alnum(strtr($str, '_.', 'AA'));
    }

    /**
     * md5后的字符串
     *
     * @param string $md5pwd
     */
    public static function isMD5($md5pwd)
    {
        return (strlen($md5pwd) == 32) && ctype_alnum($md5pwd);
    }

    /**
     * 验证字符串是否安全含有不安全字符
     *
     * @todo 过于简单暴力

     * @param string $str
     *
     * @return bool
     */
    public static function safeChar($str)
    {
        return strpbrk($str, '<>&#\\%') === false;
    }
}
