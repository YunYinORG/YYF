<?php
namespace Debug;

use \Debug\Header as Header;
use \Debug as Debug;
use \Service\Database as Database;

class Listener
{
    protected static $sql_output;
    protected static $sql_reslult_in_header = false;
    protected static $_sql_id = 0; /*记录执行次数*/
    protected static $_sql_data = array();

     /**
    * 监视 数据库sql查询
    */
    public static function listenSQL($type)
    {
        static::$sql_output = explode(',', strtoupper($type));
        Database::$before = array(__CLASS__, 'sqlBeforeListener');
        Database::$after  = array(__CLASS__, 'sqlAfterListener');
    }

    public static function showSqlResultInHeader($is_enable)
    {
        static::$sql_reslult_in_header=$is_enable;
    }

    /**
    * 数据库查询监听回调
    * @param string $sql 查询语句
    * @param array $param 参数列表
    * @param string $name 调用方法名称
    */
    public static function sqlBeforeListener(&$sql, &$param, $name)
    {
        $id=&static::$_sql_id;
        
        static::$_sql_data=array(
            'T'=>microtime(true),//time
            'Q'=>$sql,//sql query
        );
        if ($param) {
            //param
            static::$_sql_data[$id]['P']=$param;
        }
        Debug::log("[SQL]({$id}) {$name} called");
        ++$id;
    }
    
    /**
    * 数据库查询监听回调
    * @param pdo $db 数据库事例
    * @param mixed $result 查询结果
    * @param string $name 调用方法名称
    */
    public static function sqlAfterListener(&$db, &$result, $name)
    {
        $id=static::$_sql_id-1;
        $data=&static::$_sql_data;
        $data['T']=(microtime(true)-$data['T'])*1000;

        if (in_array('LOG', static::$sql_output)) {
            $message="\r\n";
            $message.='  [SQL'. str_pad($id, 3, '0', STR_PAD_LEFT).'] '.$data['Q'].PHP_EOL;
            if (isset($data['P'])) {
                $message.='  [PARAMS] '.json_encode($data['P'], 256).PHP_EOL;
            }
            $message.='  [RESULT] '.json_encode($result, 256).PHP_EOL;

            if (!$db->isOk()) {
                $message.='!![ERROR!] '.json_encode($db->errorInfo(), 256).PHP_EOL;
                Debug::log("[SQL]({$id}) error!!!");
            }
            $message.="  [INFORM] ${data['T']} ms ( $name ) \n\r";
            Debug::log($message, 'SQL');
        }

        if (in_array('HEADER', static::$sql_output)) {
            if ($db->isOk()) {
                $data['R'] = static::$sql_reslult_in_header ? $result : count($result);
            } else {
                $data['R'] = $result;
                $data['E'] = $db->errorInfo();
            }
            Header::instance()->debugInfo("Sql-$id", $data);
        }
    }
}
