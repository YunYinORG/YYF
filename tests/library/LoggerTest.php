<?php
namespace tests\library;

use \Logger as Logger;
use \Yaf_Application as Application;
use \PHPUnit_Framework_TestCase as TestCase;

/**
 * @coversDefaultClass \Logger
 */
class LoggerTest extends TestCase
{
    protected $message;
    protected $env;
    protected $type;
    protected $allow;
    protected $level=array('EMERGENCY','ALERT','CRITICAL','ERROR','WARN','NOTICE','INFO','DEBUG','SQL','TRACER');

    public function __construct()
    {
        parent::__construct();
        $this->env=Application::app()->environ();
        $conf=Application::app()->getConfig()->log;
        $this->type=$conf->type;
        $this->allow=explode(',', strtoupper($conf->allow));
        if ('system'===$conf->type) {
            //拦截系统日志
            ini_set('error_log', static::getLogFile());
        }
    }

    public static function getLogFile($key=null)
    {
        if ($key) {
            return Application::app()->getConfig()->runtime.'log/'. date('y-m-d-').strtoupper($key).'.log';
        }
        return APP_PATH.'/runtime/test_error_log.txt';
    }

    public function setUp()
    {
        $m=&$this->message;
        $m=array();
        Logger::$listener=function (&$level, &$msg) use (&$m) {
            $m[]=array($level=>$msg);
        };
    }

    public function tearDown()
    {
        Logger::clear();
        if (is_file($file=static::getLogFile())) {
            unlink($file);
        }
    }

    public function popAssert($level, $msg)
    {
        $message = array_pop($this->message);
        return  $this->assertSame($message, array(strtoupper($level)=>$msg));
    }
    
    public function testListener()
    {
        $level='NOTICE';
        $msg="test message";
        Logger::write($msg, $level);
        $this->popAssert($level, $msg);
        $level='ssssa';
        $msg="message";
        Logger::write($msg, $level);
        $this->popAssert($level, $msg);
    }

    /**
    * @covers ::write
    */
    public function testWrite()
    {
        Logger::clear();
        $level = $this->level;
        $level[] = uniqid('test');
        $log1='test logger lower';
        $log2='isaverylongloggerstringfortesting';
        if ('file'===$this->type) {
            //文件存储形式
            foreach ($level as $l) {
                Logger::write($log1, strtolower($l));
                Logger::write($log2, $l);
                $file=static::getLogFile($l);
                if (in_array($l, $this->allow)) {
                    $pre=date('[d-M-Y H:i:s e] (').getenv('REQUEST_URI').') ' ;
                    $message=$pre.$log1.PHP_EOL.$pre.$log2.PHP_EOL;
                    $this->assertStringEqualsFile($file, $message);
                    static::assertMode($file);
                } else {
                    $this->assertFileNotExists($file);
                }
            }
        } elseif ('system'===$this->type) {
            //系统日志
            $message='';
            foreach ($level as $l) {
                Logger::write($log1, strtolower($l));
                Logger::write($log2, $l);
                if (in_array($l, $this->allow)) {
                    $date=date('[d-M-Y H:i:s e] ');
                    $message.=$date."$l: $log1".PHP_EOL.$date."$l: $log2".PHP_EOL;
                }
            }
            $file=static::getLogFile();
            $this->assertStringEqualsFile($file, $message);
        }
    }
     
    /**
    * @depends testListener
    */
    public function testLog()
    {
        $message='just test Message';
        $templete='{key}-{test}';
        $context=array('key'=>'somevalue','test'=>'tstring','t'=>'sss');
        $templete_string='somevalue-tstring';
        $json=array('test','key','value');
        foreach ($this->level as $l) {
            Logger::log($l, $message);
            Logger::log(strtolower($l), $templete, $context);
            Logger::log($l, $json);
            $this->popAssert($l, json_encode($json, 256));
            $this->popAssert($l, $templete_string);
            $this->popAssert($l, $message);
        }
    }

    /**
    * @depends testLog
    */
    public function testInterface()
    {
        $message='test log message string';

        Logger::emergency($message);
        $this->popAssert('EMERGENCY', $message);
        Logger::alert($message);
        $this->popAssert('ALERT', $message);
        Logger::critical($message);
        $this->popAssert('CRITICAL', $message);

        Logger::error($message);
        $this->popAssert('ERROR', $message);
        Logger::warning($message);
        $this->popAssert('WARN', $message);
        Logger::warn($message);
        $this->popAssert('WARN', $message);

        Logger::NOTICE($message);
        $this->popAssert('NOTICE', $message);
        Logger::info($message);
        $this->popAssert('INFO', $message);
        Logger::DEBUG($message);
        $this->popAssert('DEBUG', $message);
    }

    /**
    * @depends testWrite
    */
    public function testClear()
    {
        if ('file'===$this->type) {
            $dir=Application::app()->getConfig()->runtime.'log/';
            Logger::write('test', "ERROR");
            Logger::write('test', 'ALERT');
            $this->assertGreaterThan(2, count(scandir($dir)));
            Logger::clear();
            $this->assertCount(2, scandir($dir));
        }
    }

    /**
    * @requires OS Linux
    * @covers ::getFile
    */
    public function testDirMode()
    {
        if ('file'===$this->type) {
            $dir=APP_PATH.DIRECTORY_SEPARATOR.'runtime'.DIRECTORY_SEPARATOR.'log'.DIRECTORY_SEPARATOR;
            static::assertMode($dir, 0777);
        }
    }


    /**
    * 检查文件权限，$base 基础值，文件0666 目录0777
    * @requires OS Linux
    */
    public function assertMode($path, $base=0666)
    {
        $umask = Application::app()->getConfig()->umask;
        if (null===$umask) {
            $mode=0700&$base;
        } else {
            $mode= intval($umask, 8)&$base^$base;
        }
        clearstatcache();
        $this->assertSame(fileperms($path)&$mode, $mode, $path.'文件权限与预设不符(file permission not the same)');
    }
}
