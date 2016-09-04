<?php
use \Logger as Logger;

/**
 * 调试工具
 */
class Debug
{

    private static $_sql_id=0; /*记录执行次数*/
    private static $_sql_data=array();

    /*sql 执行前调用*/
    public static function sqlBeforeListener(&$sql, &$param, $name)
    {
        $id=&static::$_sql_id;
        
        static::$_sql_data[$id]=array(
            'T'=>microtime(true),//time
            'S'=>$sql,//sql query
        );
        if ($param) {
            //param
            static::$_sql_data[$id]['P']=$param;
        }

        Logger::debug("[SQL]({$id}) {$name} called");
        ++$id;
    }
    
    /*sql 执行后调用*/
    public static function sqlAfterListener(&$db, &$result, $name)
    {
        $id=static::$_sql_id-1;
        $data=&static::$_sql_data[$id];
        $data['R']=$result;
        $data['T']=(microtime(true)-$data['T'])*1000;

        $message="\r\n";
        $message.='  [SQL'. str_pad($id, 3, '0', STR_PAD_LEFT).'] '.$data['S'].PHP_EOL;
        if (isset($data['P'])) {
            $message.='  [PARAMS] '.json_encode($data['P'], 256).PHP_EOL;
        }
        $message.='  [RESULT] '.json_encode($result, 256).PHP_EOL;
        if (!$db->isOk()) {
            $data['E'] = $db->errorInfo();
            $message.='!![ERROR!] '.json_encode($db->errorInfo(), 256).PHP_EOL;
            Logger::debug("[SQL]({$id}) error!!!");
        }
        $message.="  [INFORM] ${data['T']} ms ( $name ) \n\r";
        Logger::write($message, 'SQL');
    }

}
