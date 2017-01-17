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
 * Random 随机生成器
 *
 * @author NewFuture
 *
 * @todo 函数化
 */
class Random
{
    /**
     * 生成指定长度的随机数字
     *
     * @param int $n [description]
     */
    public static function number($n = 4)
    {
        if ($n < 9 && $n >= 2) {
            return str_pad(mt_rand(1, pow(10, $n)), '0', STR_PAD_LEFT);
        }

        $str = str_repeat('1234567890', $n / 2);
        return substr(str_shuffle($str), 0, $n);
    }

    /*数字和字母组合的随机字符串*/
    public static function word($n = 8)
    {
        return $n < 1 ? '' : substr(str_shuffle(str_repeat('abcdefghijklmnopqrstuvwxyz0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ', ($n + 3) / 4)), 0, $n);
    }

    /*只有字符*/
    public static function char($n = 10)
    {
        return $n < 1 ? '' : substr(str_shuffle(str_repeat('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', ($n + 3) / 4)), 0, $n);
    }

    /**
     * 验证码生成
     * 会过滤掉0O1lL等不易辨识字符
     *
     * @param int $n 个数
     *
     * @return string
     */
    public static function code($n = 6)
    {
        return $n < 1 ? '' : substr(str_shuffle(str_repeat('abcdefghijkmnpqrstuvwxyz23456789ABCDEFGHJKMNPQRSTUVWXYZ', 3)), 0, $n);
    }
}
