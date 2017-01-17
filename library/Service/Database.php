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

use \Exception;
use \Logger as Log;
use \PDO;

/**
 * Database 数据库操作，
 * PDO 预处理封装
 *
 * @author NewFuture
 * Database::exec($sql,$bindArray) — 执行一条 SQL 语句，并返回受影响的行数
 * Database::query($sql,$bindArray)
 * Database::errorInfo() 获取出错信息
 * Database::errorCode() 获取错误码
 * 继承自PDO
 * PDO::beginTransaction() — 启动一个事务
 * PDO::commit() — 提交一个事务
 * PDO::rollBack() — 回滚一个事务
 * PDO::errorInfo()
 * PDO::lastInsertId — 返回最后插入行的ID或序列值
 * PDO::prepare() 查询预处理
 * PDO::setAttribute — 设置属性
 */
class Database extends PDO
{
    public static $before = null;//执行前调用$before($sql,$param);
    public static $after  = null;//执行后调用$after($this);
    public static $debug  = false;//调试输出

    private $_status    = true;
    private $_errorInfo = null;

    /**
     * 查询数据库,返回数组或者null
     *
     * @param string $sql
     * @param array  $params
     * @param bool   $fetchAll 全部查询模式fetchAll，false 使用fetch
     * @param int $mode 查询模式,默认读取配置
     *
     * @return array 查询结果
     */
    public function query($sql, array $params = null, $fetchAll = true, $mode = null)
    {
        if ($before = &Database::$before) {
            //执行前调用
            assert('is_callable($before)', '[Database::$before] 应该是可执行的回调');
            call_user_func_array($before, array(&$sql, &$params, 'query'));
        }

        if ($statement = $this->execute($sql, $params)) {
            $result = $fetchAll ? $statement->fetchAll($mode) : $statement->fetch($mode);
            $statement->closeCursor();
        } else {
            return false;
        }

        if ($after = &Database::$after) {
            //执行完成调用
            assert('is_callable($after)', '[Database::$after] 应该是可执行的回调');
            call_user_func_array($after, array(&$this, &$result, 'query'));
        }
        return $result;
    }

    /**
     * 执行sql语句
     *
     * @param string $sql    sql预处理语句
     * @param array  $params 查询参数
     *
     * @return int [影响条数]
     */
    public function exec($sql, array $params = null)
    {
        assert('stripos($sql,"SElECT")!==0', '[Database::exec] select查询语句请使用query方法');
        if ($before = &Database::$before) {
            //执行前调用
            assert('is_callable($before)', '[Database::$before] 应该是可执行的回调');
            call_user_func_array($before, array(&$sql, &$params, 'exec'));
        }

        if (empty($params)) {
            $this->_status = true;
            $result        = parent::exec($sql);
            if (false === $result) {
                $this->error();
                return false;
            }
        } elseif ($statement = $this->execute($sql, $params)) {
            $result = $statement->rowCount();
            $statement->closeCursor();
        } else {
            return false;
        }

        if ($after = &Database::$after) {
            //执行完成调用
            assert('is_callable($after)', '[Database::$after] 应该是可执行的回调');
            call_user_func_array($after, array(&$this, &$result, 'exec'));
        }
        return $result;
    }

    /**
     *  按行查询
     *
     * @param string $sql    查询语句
     * @param array  $params 参数
     *
     *  @return string
     */
    public function column($sql, array $params = null)
    {
        if ($before = &Database::$before) {
            //执行前调用
            assert('is_callable($before)', '[Database::$before] 应该是可执行的回调');
            call_user_func_array($before, array(&$sql, &$params, 'column'));
        }

        if ($statement = $this->execute($sql, $params)) {
            $result = $statement->fetchColumn();
            $statement->closeCursor();
        } else {
            return false;
        }

        if ($after = &Database::$after) {
            //执行完成调用
            assert('is_callable($after)', '[Database::$after] 应该是可执行的回调');
            call_user_func_array($after, array(&$this, &$result, 'column'));
        }
        return $result;
    }

    /**
     * @return bool 上次查询状态
     */
    public function isOk()
    {
        return $this->_status;
    }

    /**
     * @return array 上次出错信息
     */
    public function errorInfo()
    {
        return $this->_errorInfo ?: parent::errorInfo();
    }

    /**
     * 事务封装
     *
     * @param callable $func，事务回调函数，参数是当前Database，
     *      回调返回false或者出现异常回滚，否则提交
     * @param bool $err_exp 错误抛出异常默认会自动设置并切换回来
     *
     * @return bool or null 回调函数的返回值(执行异常自动回滚，返回false)
     */
    public function transact($func, $err_exp = true)
    {
        assert('is_callable($func)');
        if (true === $err_exp && parent::getAttribute(PDO::ATTR_ERRMODE) !== PDO::ERRMODE_EXCEPTION) {
            $errmode = parent::getAttribute(PDO::ATTR_ERRMODE);
            parent::setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }
        try {
            $this->beginTransaction();
            $result = $func($this);
            if (false === $result) {
                //执行失败回滚
                $this->_status = false;
                $this->rollBack();
            } else {
                //执行成功，提交
                $this->commit();
            }
        } catch (Exception $e) {
            //执行异常回滚
            $this->rollBack();
            $this->_status = false;
            $result        = false;
            Log::write('[SQL] transact exception: '.$e->getMessage(), 'WARN');
        }
        isset($errmode) && parent::setAttribute(PDO::ATTR_ERRMODE, $errmode);
        return $result;
    }

    /**
     * 获取PDO参数绑定类型
     *
     * @return int PARAM_*
     */
    public static function getType(&$value)
    {
        switch (gettype($value)) {
            case 'string':
            case 'double':
                return \PDO::PARAM_STR;
            case 'integer':
                return \PDO::PARAM_INT;
            case 'boolean':
                return \PDO::PARAM_BOOL;
            case 'NULL':
                return \PDO::PARAM_NULL;
            default:
                return null;
        }
    }

    /**
     * 预处理方式执行sql
     *
     * @param string $sql    [description]
     * @param array  $params [索引型数组(?),键值对数组会自动加(:$key,$value)]
     *
     * @return PDOStatement
     */
    private function execute($sql, &$params)
    {
        $this->_status = true;
        $statement     = false;
        if (empty($params)) {
            $statement = parent::query($sql); //无参数直接执行
        } elseif ($statement = $this->prepare($sql)) {
            assert('is_array($params)', '[database]绑定参数$params应该以数组形式传入'.print_r($params, true));

            /*参数绑定*/
            if (is_int($key = key($params))) {
                /*索引数组*/
                foreach ($params as $key => &$value) {
                    $statement->bindValue($key + 1, $value, Database::getType($value));
                }
            } elseif ($key[0] === ':') {
                /*关联数组已加分隔符：*/
                foreach ($params as $key => &$value) {
                    $statement->bindValue($key, $value, Database::getType($value));
                }
            } else {
                /*关联型数组键值未设置冒号*/
                foreach ($params as $key => &$value) {
                    $statement->bindValue(':'.$key, $value, Database::getType($value));
                }
            }
            unset($value);

            if (!$statement->execute()) {
                /*执行出错*/
                $this->_errorInfo = $statement->errorInfo();
                if (Database::$debug) {
                    //dump 查询错误
                    $statement->debugDumpParams();
                }
                $statement->closeCursor();
                $statement = null;
                $statement = false;
            }
        }

        if (false === $statement) {
            $this->error();
        }
        return $statement;
    }

    /**
     * 错误处理
     */
    private function error()
    {
        $this->_status = false;
        if ($after = &Database::$after) {
            //执行完成调用
            assert('is_callable($after)', '[Database::$after] 应该是可执行的回调');
            call_user_func_array($after, array(&$this, &$this->_errorInfo, 'error'));
        }
        Log::write('[SQL] execute ERROR: '.json_encode($this->errorInfo()), 'ERROR');
    }
}
