<?php
/**
 * YYF - A simple, secure, and high performance PHP RESTful Framework.
 *
 * @link https://github.com/YunYinORG/YYF/
 *
 * @license Apache2.0
 * @copyright 2015-2017 NewFuture@yunyin.org
 */

namespace Service;

use \Logger as Log;

/**
 * 上传文件管理
 * 封装七牛API
 *
 * @author NewFuture
 */
class Qiniu
{
    const QINIU_RS         = 'http://rs.qbox.me';

    /**
     * 参数配置
     *
     * @var array
     */
    public static $_config = null;

    /**
     * 获取文件下载链接
     *
     * @param string $domain 域名
     * @param string $name   [文件名]
     * @param string $param  [附加参数]
     *
     * @return string url
     */
    public static function download($domain, $name, $param = array())
    {
        $url   = $domain.$name.'?'.http_build_query($param);
        $token = self::sign($url);
        return $url.'&token='.$token;
    }

    /**
     * 重命名【移动】
     *
     * @param string $from 原文件名
     * @param string $to   新文件名
     */
    public static function move($from, $to)
    {
        // $bucket = $this->_config['bucket'];
        $op = '/move/'.self::qiniuEncode($from).'/'.self::qiniuEncode($to);
        return self::opration($op);
    }

    /**
     * 复制文件
     *
     * @param string $from   源文件
     * @param string $saveas 目标文件名
     *
     * @return bool 操作结果
     */
    public static function copy($from, $saveas)
    {
        // $bucket = $this->_config['bucket'];
        $op = '/copy/'.self::qiniuEncode($from).'/'.self::qiniuEncode($saveas);
        return self::opration($op);
    }

    /**
     * 获取上传token
     *
     * @param mixed $bucket  存储位置
     * @param mixed $key     文件名
     * @param mixed $max     文件上限
     * @param int   $timeout 过期时间
     *
     * @return string
     */
    public static function getToken($bucket, $key, $max = 10485760, $timeout = 600)
    {
        $setting = array(
            'scope'      => $bucket,
            'saveKey'    => $key,
            'deadline'   => $timeout + $_SERVER['REQUEST_TIME'],
            'fsizeLimit' => intval($max),
        );
        $setting = self::qiniuEncode(json_encode($setting));
        return self::sign($setting).':'.$setting;
    }

    /**
     * 删除
     *
     * @param string $uri [完整文件名]
     *
     * @return bool [description]
     */
    public static function delete($uri)
    {
        $file = self::qiniuEncode($uri);
        return self::opration('/delete/'.$file);
    }

    /**
     * 判断文件是否存在
     *
     * @param string $uri
     *
     * @return bool 是否存在
     */
    public static function has($uri)
    {
        $op = '/stat/'.self::qiniuEncode($uri);
        return self::opration($op);
    }

    /**
     * 转pdf
     *
     * @param mixed $bucket 存储位置
     * @param mixed $key    文件名
     * @param mixed $saveas 保存文件名
     *
     * @return bool 操作结果
     */
    public static function toPdf($bucket, $key, $saveas)
    {
        $API  = 'http://api.qiniu.com';
        $op   = '/pfop/';
        $data = 'bucket='.$bucket.'&key='.$key.'&fops=yifangyun_preview|saveas/'.self::qiniuEncode($saveas);
        return self::opration($op, $data, $API);
    }

    /**
     * 七牛操作
     *
     * @param string     $op   [操作命令]
     * @param null|array $data
     * @param string     $host 主机
     *
     * @return bool [操作结果]
     */
    private static function opration($op, $data = null, $host = self::QINIU_RS)
    {
        $token  = self::sign(is_string($data) ? $op."\n".$data : $op."\n");
        $url    = $host.$op;
        $header = array('Authorization: QBox '.$token);

        if ($ch = curl_init($url)) {
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
            if ($data) {
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            }
            curl_setopt($ch, CURLOPT_HEADER, 1);
            $response = curl_exec($ch);
            $status   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($status == 200) {
                return true;
            }
            // elseif (\Config::get('debug'))
            // {
            // 	/*操作出错*/
            // 	\PC::debug($response, '七牛请求出错');
            // }
        }
        Log::write('[QINIU]七牛错误'.$url.':'.($response ?: '请求失败'), 'ERROR');
        return false;
    }

    /**
     * 获取url签名
     *
     * @param string $url url
     *
     * @return string 签名字符串
     */
    private static function sign($url)
    {
        $config = self::$_config ?: (self::$_config = \Config::getSecret('qiniu'));
        $sign   = hash_hmac('sha1', $url, $config['secretkey'], true);
        $ak     = $config['accesskey'];
        return $ak.':'.self::qiniuEncode($sign);
    }

    /**
     * 七牛安全编码
     *
     * @param string $str
     */
    private static function qiniuEncode($str)
    {
        return strtr(base64_encode($str), array('+' => '-', '/' => '_'));
    }
}
