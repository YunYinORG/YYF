<?php
namespace Storage;

use \Logger as Log;

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
        $dir = $this->_dir;
        /*获取全部文件*/
        $files = scandir($dir);
        unset($files[0]);
        unset($files[1]);
        foreach ($files as $f) {
            @unlink($dir . $f);
        }
    }

    /**
     * @param  [type]  $dir [存储目录]
     * @param  [bool]        [是否序列化，用于记录缓存]
     * @author NewFuture
     */
    public function __construct($dir, $serialized = false)
    {
        $dir .= DIRECTORY_SEPARATOR;
        if (is_dir($dir)||mkdir($dir, 0700, true)) {
            $this->_dir = $dir;
        } else {
            $msg='无法创建目录:'.$dir;
            Log::write($msg, 'EEROR');
            throw new \Exception($msg);
        }
        $this->_serialized = $serialized;
    }

    // /**
    //  * 循环创建目录
    //  * @method mkdir
    //  * @param  [type]  $dir [目录名]
    //  * @param  integer $mod [创建模式]
    //  * @return [bool]       [是否创建成功]
    //  * @author NewFuture
    //  */
    // public static function mkdir($dir, $mod = 0755)
    // {
    // 	return is_dir($dir) || (self::mkdir(dirname($dir), $mod) && mkdir($dir, $mod));
    // }
}
