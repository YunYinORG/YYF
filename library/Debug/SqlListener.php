<?php
/**
 * YYF - A simple, secure, and high performance PHP RESTful Framework.
 *
 * @link https://github.com/YunYinORG/YYF/
 *
 * @license Apache2.0
 * @copyright 2015-2017 NewFuture@yunyin.org
 */

namespace Debug;

use \Debug as Debug;
use \Service\Database as Database;

/**
 * 数据库操作监控
 *
 * @author NewFuture
 */
class SqlListener
{
    protected static $sql_output;
    protected static $show_detail_in_header = false;
    protected static $_sql_id               = 0; /*记录执行次数*/
    protected static $instance;

    protected $_data = array();

    protected function __construct()
    {
    }

    public function __destruct()
    {
        if ($data = &$this->_data) {
            $this->flush('异常终止');
        }
    }

    /**
     * 监视 数据库sql查询
     *
     * @param string $type        监听方式
     * @param bool   $show_detail 是否输出
     */
    public static function init($type, $show_detail = null)
    {
        $instance           = static::$instance ?: (static::$instance = new static);
        static::$sql_output = explode(',', strtoupper($type));
        Database::$before   = array($instance, 'beforeQuery');
        Database::$after    = array($instance, 'afterQuery');
        static::showDetail($show_detail);
    }

    /**
     *是否在header中显示
     *
     * @param bool $is_enable 显示详情
     */
    public static function showDetail($is_enable)
    {
        static::$show_detail_in_header = $is_enable;
    }

    /**
     * 数据库查询监听回调
     *
     * @param string $sql   查询语句
     * @param array  $param 参数列表
     * @param string $name  调用方法名称
     *
     * @todo 提前回收
     */
    public function beforeQuery(&$sql, &$param, $name)
    {
        if ($data = &$this->_data) {
            $this->flush('异常终止');
        }
        $data = array(
            'T' => microtime(true),//time
            'Q' => $sql,//sql query
            'N' => $name,
        );
        if ($param) {
            //param
            $data['P'] = $param;
        }
        $id = &static::$_sql_id;
        Debug::log("[SQL]({$id}) {$name} called\n${sql}}\n".json_encode($param, 256));
        ++$id;
    }

    /**
     * 数据库查询监听回调
     *
     * @param pdo    $db     数据库事例
     * @param mixed  $result 查询结果
     * @param string $name   调用方法名称
     */
    public function afterQuery(&$db, &$result, $name)
    {
        $data      = &$this->_data;
        $data['T'] = (microtime(true) - $data['T']) * 1000;
        $error     = null;
        if ($db->isOk() && $name !== 'error') {
            $data['R'] = $result;
        } else {
            $error     = $result;
            $data['E'] = $result[0];
        }
        $this->flush($error);
    }

    /**
     * 输出数据
     *
     * @param null|mixed $error
     */
    protected function flush($error = null)
    {
        $id   = static::$_sql_id;
        $data = &$this->_data;
        if (in_array('LOG', static::$sql_output)) {
            $message = "\r\n";
            $message .= '  [SQL'.str_pad($id, 3, '0', STR_PAD_LEFT).'] '.$data['Q'].PHP_EOL;
            if (isset($data['P'])) {
                $message .= '  [PARAMS] '.json_encode($data['P'], 256).PHP_EOL;
            }
            if (isset($data['R'])) {
                $message .= '  [RESULT] '.json_encode($data['R'], 256).PHP_EOL;
            }
            if ($error) {
                $message .= '!![ERROR!] '.json_encode($error, 256).PHP_EOL;
                Debug::log("[SQL]({$id}) error!!!");
            }
            $message .= "  [INFORM] ${data['T']} ms (${data['N']})\n\r";
            Debug::log($message, 'SQL');
        }

        if (in_array('HEADER', static::$sql_output)) {
            unset($data['N']);
            if (!static::$show_detail_in_header && isset($data['R'])) {
                $data['R'] = count($data['R']);
            }
            Debug::header()->debugInfo("Sql-$id", $data);
        }
        $data = null;
    }
}
