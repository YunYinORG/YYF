<?php
namespace Service;

use \PDO;
use \Config;
use \Logger as Log;

/**
* 数据库操作，
* PDO 预处理封装
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

    private $_errorCode = null;
    private $_errorInfo = null;
    

    public function __construct($dsn, $username=null, $password=null, array $options=null)
    {
        parent::__construct($dsn, $username, $password, $options);
        parent::setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
        if (Config::getSecret('database', 'exception')) {
            parent::setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }
    }

    /**
    * @method query
    * 查询数据库,返回数组或者null
    * @param  string $sql
    * @param  array  $params
    * @param  int    $fetchmode
    * @return array 查询结果
    * @author NewFuture
    */
    public function query($sql, array $params = null, $fetchmode = \PDO::FETCH_ASSOC)
    {
        if (self::$before) {
            //执行前调用
            assert('is_callable(self::$before)', '$before 因该是可执行的回调');
            call_user_func_array(self::$before, array(&$sql, &$params, 'query'));
        }

        $result=false;
        if ($statement = $this->execute($sql, $params)) {
            $result = $statement->fetchAll($fetchmode);
            $statement->closeCursor();
        }

        if (self::$after) {
            //执行完成调用
            assert('is_callable(self::$after)', '$after 应该是可执行的回调');
            call_user_func_array(self::$after, array(&$this, &$result, 'query'));
        }
        return $result;
    }
    
    /**
    * 执行sql语句
    * @method exec
    * @param  [string] $sql     [description]
    * @param  [array] $params    [description]
    * @param  [type] $fetchmode [description]
    * @return [int]            [影响条数]
    */
    public function exec($sql, array $params = null)
    {
        assert('stripos($sql,"SElECT")!==0', '[Database::exec] select查询语句请使用query方法');
        if (self::$before) {
            //执行前调用
            assert('is_callable(self::$before)', '$before 因该是可执行的回调');
            call_user_func_array(self::$before, array(&$sql, &$params, 'exec'));
        }
        
        $result=false;
        if (empty($params)) {
            $result= parent::exec($sql);
            if (false===$result) {
                $this->error();
            }
        } elseif ($statement = $this->execute($sql, $params)) {
            $result = $statement->rowCount();
            $statement->closeCursor();
        }
        
        if (self::$after) {
            //执行完成调用
            assert('is_callable(self::$after)', '$after 应该是可执行的回调');
            call_user_func_array(self::$after, array(&$this, &$result, 'exec'));
        }
        return $result;
    }
    
    /**
    *	@method column
    *	按行查询
    *	@param  string $query
    *	@param  array  $params
    *	@return string
    */
    public function column($sql, array $params = null)
    {
        if (self::$before) {
            //执行前调用
            assert('is_callable(self::$before)', '$before 因该是可执行的回调');
            call_user_func_array(self::$before, array(&$sql, &$params, 'column'));
        }
        $result=false;
        if ($statement = $this->execute($sql, $params)) {
            $result = $statement->fetchColumn();
            $statement->closeCursor();
        }
        if (self::$after) {
            //执行完成调用
            assert('is_callable(self::$after)', '$after 应该是可执行的回调');
            call_user_func_array(self::$after, array(&$this, &$result, 'column'));
        }
        return $result;
    }
    
    
    /**
    * @method errorCode
    * @return mixed    [错误码]
    * @author NewFuture
    */
    public function errorCode()
    {
        return $this->_errorCode ?: parent::errorCode();
    }
    
    /**
    * @method errorInfo
    * @return array    [出错信息]
    * @author NewFuture
    */
    public function errorInfo()
    {
        return $this->_errorInfo ?: parent::errorInfo();
    }

    /**
    * 事务封装
    * @method transact
    * @param callable $func，事务回调函数，参数是当前Database，回调返回false或者出现异常回滚，否则提交
    * @return 回调函数的返回值(执行异常自动回滚，返回false)
    * @author NewFuture
    */
    public function transact(callable $func)
    {
        try {
            $this->beginTransaction();
            $result = $func($this);
            if (false===$result) {
                //执行失败回滚
                $this->rollBack();
            } else {
                //执行成功，提交
                $this->commit();
                return $result;
            }
        } catch (Exception $e) {
            //执行异常回滚
           Log::write('[SQL] transact exception: '.$e->getMessage(), 'WARN');
            $this->rollBack();
        }
        return false;
    }

    /**
    * 获取PDO参数绑定类型
    * @method getType
    * @return PARAM_*
    * @author NewFuture
    */
    public static function getType(&$value)
    {
        switch (gettype($value)) {
            case 'string':
            case 'double':
                return \PDO::PARAM_STR;
            case 'integer';
                return \PDO::PARAM_INT;
            case  'boolean':
                return \PDO::PARAM_BOOL;
            case 'NULL':
                return \PDO::PARAM_NULL;
            default:
                return null;
        }
    }
    
    /**
    * @method execute
    * @description 预处理方式执行sql
    * @param  string  $sql          [description]
    * @param  array   $params       [索引型数组(?),键值对数组会自动加(:$key,$value)]
    * @return [type]  [description]
    * @author NewFuture
    */
    private function execute($sql, &$params)
    {
        $statement=false;
        if (empty($params)) {
            $statement = parent::query($sql); //无参数直接执行
        } elseif ($statement = $this->prepare($sql)) {
            assert('is_array($params)', '[database]绑定参数$params应该以数组形式传入' . print_r($params, true));
        
            /*参数绑定*/
            if (is_int($key=key($params))) {
                /*索引数组*/
                foreach ($params as $key => &$value) {
                    $statement->bindValue($key+1, $value, self::getType($value));
                }
            } elseif ($key[0]===':') {
                /*关联数组已加分隔符：*/
                foreach ($params as $key => &$value) {
                    $statement->bindValue($key, $value, self::getType($value));
                }
            } else {
                /*关联型数组键值未设置冒号*/
                foreach ($params as $key => &$value) {
                    $statement->bindValue(':' . $key, $value, self::getType($value));
                }
            }
            unset($value);

            if (!$statement->execute()) {
                /*执行出错*/
                $this->_errorCode = $statement->errorCode();
                $this->_errorInfo = $statement->errorInfo();
                
                if (self::$debug) {
                    //dump 查询错误
                    $statement->debugDumpParams();
                }
                $statement->closeCursor();
                $statement = null;
                $statement = false;
            }
        }

        if (false===$statement) {
            $this->error();
        }
        return $statement;
    }
    
    /*错误处理*/
    private function error()
    {
        Log::write('[SQL] execute ERROR: '.json_encode($this->errorInfo()), 'ERROR');
    }
}
