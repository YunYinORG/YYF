<?php
namespace Storage;

use \Config as Config;

/**
 *文件存取类
 * Function list:
 * - set()
 * - get()
 * - delete()
 * - flush()
 */
class File
{
    protected static $umask    = false;  //文件权限过滤
    protected $_dir        = null;  //文件目录
    protected $_serialized = false; //是否序列化存取

    /**
     * 保存数据
     * @method set
     * @param  [type]  $name   [description]
     * @param  [type]  $value  [description]
     * @param  mixed $expire [有效时间]
     * @author NewFuture
     */
    public function set($name, $value)
    {
        if ($this->_serialized) {
            //序列化写入文件
            $expire = func_num_args() == 4 ? (func_get_arg(3) + time()) : 0;
            $cache  = array('e' => $expire, 'c' => $value);
            $value  = serialize($cache);
        }
        assert('is_scalar($value)', '保存的数据应该是基本类型');
        $filename = $this->_dir . $name . '.php';
        return file_put_contents($filename, $value);
    }

    /**
     * 读取数据
     * @method get
     * @param  [type] $name [description]
     * @return [type]       [description]
     * @author NewFuture
     */
    public function get($name)
    {
        $filename = $this->_dir . $name . '.php';
        if (is_file($filename)) {
            $content = file_get_contents($filename);
        } else {
            return null; /*不存在返回null*/
        }

        if ($this->_serialized) {
            /*反序列化的文件*/
            $cache = unserialize($content);
            return ($cache['e'] && $_SERVER['REQUEST_TIME'] > $cache['e']) ? null : $cache['c'];
        } else {
            return $content;
        }
    }

    /**
     * 删除数据
     * @method del
     * @param  [type] $name [数据名称]
     * @return [bool]       [description]
     * @author NewFuture
     */
    public function delete($name)
    {
        $filename = $this->_dir . $name . '.php';
        return is_file($filename) ? unlink($filename) : false;
    }

    /**
     * 删除全部缓存数据
     * @method flush
     * @return [type] [description]
     * @author NewFuture
     */
    public function flush()
    {
        File::cleanDir($this->_dir);
    }

    /**
     * @param  [type]  $dir [存储目录]
     * @param  [bool]        [是否序列化，用于记录缓存]
     * @author NewFuture
     */
    public function __construct($dir, $serialized = false)
    {
        if (false===File::$umask) {
            umask(intval(Config::get('umask', 0077), 8));
            File::$umask=true;
        }
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
        $this->_dir = $dir.DIRECTORY_SEPARATOR;
        $this->_serialized = $serialized;
    }

    /**
     * 清空目录
     * @param  [type]  $dir [存储目录]
     * @author NewFuture
     */
    public static function cleanDir($dir)
    {
        /*获取全部文件*/
        $files = scandir($dir);
        unset($files[0]);
        unset($files[1]);
        foreach ($files as &$f) {
            @unlink($dir . $f);
        }
        unset($files);
    }
}
