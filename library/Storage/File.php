<?php
/**
 * YYF - A simple, secure, and high performance PHP RESTful Framework.
 *
 * @see https://github.com/YunYinORG/YYF/
 *
 * @license Apache2.0
 * @copyright 2015-2017 NewFuture@yunyin.org
 */

namespace Storage;

use Config as Config;

/**
 * File 文件存取类.
 *
 * @author NewFuture
 * Function list:
 * - set()
 * - get()
 * - delete()
 * - flush()
 */
class File
{
    protected static $umask = false;  //文件权限过滤
    protected $_dir = null;  //文件目录
    protected $_serialized = false; //是否序列化存取

    /**
     * @param [type] $dir [存储目录]
     * @param  [bool]        [是否序列化，用于记录缓存]
     */
    public function __construct($dir, $serialized = false)
    {
        if (false === self::$umask) {
            umask(intval(Config::get('umask', 0077), 8));
            self::$umask = true;
        }
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        $this->_dir = $dir.DIRECTORY_SEPARATOR;
        $this->_serialized = $serialized;
    }

    /**
     * 保存数据.
     *
     * @method set
     *
     * @param [type] $name   [description]
     * @param [type] $value  [description]
     * @param mixed  $expire [有效时间]
     */
    public function set($name, $value, $expire = 0)
    {
        if ($this->_serialized) {
            //序列化写入文件
            $expire = $expire > 0 ? $_SERVER['REQUEST_TIME'] + $expire : 0;
            $value = serialize([$value, $expire]);
        }
        assert('is_scalar($value)||is_null($value)', '保存的数据应该是基本类型');
        $filename = $this->_dir.$name.'.php';

        return file_put_contents($filename, '<?php //'.$value) > 0;
    }

    /**
     * 批量保存数据.
     *
     * @method mset
     *
     * @param array $data   [数据(键值对)]
     * @param int   $expire [有效时间]
     */
    public function mset(array $data, $expire = 0)
    {
        $dir = $this->_dir;
        $result = true;
        if ($this->_serialized) {
            //序列化写入文件
            $expire = $expire > 0 ? $_SERVER['REQUEST_TIME'] + $expire : 0;
            foreach ($data as $key => &$value) {
                $result = $result && file_put_contents($dir.$key.'.php', '<?php //'.serialize([$value, $expire]));
            }
        } else {
            foreach ($data as $key => &$value) {
                $result = $result && file_put_contents($dir.$key.'.php', '<?php //'.$value);
            }
        }

        return $result;
    }

    /**
     * 读取数据.
     *
     * @method get
     *
     * @param [type] $name [description]
     *
     * @return [type] [description]
     */
    public function get($name)
    {
        $filename = $this->_dir.$name.'.php';
        if (is_file($filename)) {
            $content = substr(file_get_contents($filename), 8);
        } else {
            return false; /*不存在返回null*/
        }

        if ($this->_serialized) {
            /*反序列化的文件*/
            $content = unserialize($content);
            if ($content[1] && $_SERVER['REQUEST_TIME'] > $content[1]) {
                @unlink($filename);

                return false;
            }

            return $content[0];
        }

        return $content;
    }

    /**
     * 批量读取数据.
     *
     * @method mget
     *
     * @param array $data [数据(键值对)]
     */
    public function mget(array $data)
    {
        $result = [];
        foreach ($data as &$k) {
            $result[$k] = $this->get($k);
        }
        unset($k);

        return $result;
    }

    /**
     * 删除数据.
     *
     * @method delete
     *
     * @param [type] $name [数据名称]
     *
     * @return [bool] [description]
     */
    public function delete($name)
    {
        $filename = $this->_dir.$name.'.php';

        return is_file($filename) ? unlink($filename) : false;
    }

    /**
     * 删除全部缓存数据.
     *
     * @method flush
     *
     * @return [type] [description]
     */
    public function flush()
    {
        return self::cleanDir($this->_dir);
    }

    /**
     * 清空目录.
     *
     * @param [type] $dir [存储目录]
     */
    public static function cleanDir($dir)
    {
        /*获取全部文件*/
        if (!is_dir($dir)) {
            return true;
        }
        $files = scandir($dir);
        unset($files[0], $files[1]);

        $result = 0;
        foreach ($files as &$f) {
            $result += @unlink($dir.$f);
        }
        unset($files);

        return $result;
    }
}
