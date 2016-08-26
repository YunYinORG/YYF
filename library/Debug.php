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

    /*断言出错回调*/
    public static function assertCallback($script, $line, $code, $message = null)
    {
        header('Content-type: text/html; charset=utf-8');
        echo "\n断言错误触发：\n<br>",
        '<b><q>', $message, "</q></b><br>\n",
        "触发位置$script 第$line 行:<br>\n 判断逻辑<b><code> $code </code></b>\n<br/>",
        '(这里通常不是错误位置，是错误的调用方式或者参数引起的，请仔细检查)',
        "<br>\n<small>(tips:断言错误是在正常逻辑中不应出现的情况，生产环境关闭系统断言提高性能)</small>\n<br>";
    }
}
