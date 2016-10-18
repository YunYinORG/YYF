<?php
namespace tests\library;

use \Logger as Logger;
use \Test\YafCase as TestCase;

/**
 * @coversDefaultClass \Logger
 */
class LoggerTest extends TestCase
{
    protected static $LEVEL = array('EMERGENCY','ALERT','CRITICAL','ERROR','WARN','NOTICE','INFO','DEBUG','SQL','TRACER');
    protected static $bootstrap = false;
    
    protected $message;

    protected static $env;
    protected static $type;
    protected static $allow;
    
    public static function setUpBeforeClass()
    {
        static::$env = static::app()->environ();

        $conf = static::app()->getConfig()->log;
        static::$allow = explode(',', strtoupper($conf->allow));
        static::$type = $conf->type;
        if ('system' === $conf->type) {
            //拦截系统日志
            ini_set('error_log', static::getLogFile());
        }
    }


    public static function tearDownAfterClass()
    {
        clearstatcache();
        Logger::clear();
        if (is_file($file = static::getLogFile())) {
            unlink($file);
        }
    }

    public function setUp()
    {
        $m = &$this->message;
        $m = array();
        Logger::$listener = function (&$level, &$msg) use (&$m) {
            $m[] = array($level => $msg);
        };
        Logger::clear();
        if (is_file($file = static::getLogFile())) {
            unlink($file);
        }
    }

    public function tearDown()
    {

        Logger::clear();
    }


    public function testListener()
    {
        $level = 'NOTICE';
        $msg = 'test message';
        Logger::write($msg, $level);
        $this->assertPop($level, $msg);

        $level = 'ssssa';
        $msg = 'message';
        Logger::write($msg, $level);
        $this->assertPop($level, $msg);
    }


    public function testWrite()
    {
        $level = static::$LEVEL;
        $level[] = uniqid('test');
        $log1 = 'test logger lower';
        $log2 = 'isaverylongloggerstringfortesting';
        if ('file' === static::$type) {
            //文件存储形式
            foreach ($level as $l) {
                $file = static::getLogFile($l);
                if (in_array($l, static::$allow)) {
                    $this->assertNotFalse(Logger::write($l.$log1, strtolower($l)));
                    $this->assertNotFalse(Logger::write($l.$log2, $l));
                    $pre = date('[d-M-Y H:i:s e] (').getenv('REQUEST_URI').') ' ;
                    $message = $pre.$l.$log1.PHP_EOL.$pre.$l.$log2.PHP_EOL;
                    $this->assertStringEqualsFile($file, $message);
                    $this->assertFileMode($file);
                } else {
                    $this->assertNotTrue(Logger::write($log1, strtolower($l)));
                    $this->assertNotTrue(Logger::write($log2, $l));
                    $this->assertFileNotExists($file);
                }
            }
        } elseif ('system' === static::$type) {
            //系统日志
            $message = '';
            foreach ($level as $l) {
                $r1 = Logger::write($l.$log1, strtolower($l));
                $r2 = Logger::write($l.$log2, $l);
                if (in_array($l, static::$allow)) {
                    $date = date('[d-M-Y H:i:s e] ');
                    $message .= $date."$l: ${l}${log1}".PHP_EOL.$date."$l: ${l}${log2}".PHP_EOL;
                    $this->assertNotFalse($r1);
                    $this->assertNotFalse($r2);
                } else {
                    $this->assertNotTrue($r1);
                    $this->assertNotTrue($r2);
                }
            }
            $file = static::getLogFile();
            $this->assertStringEqualsFile($file, $message);
        }
    }

    /**
     * @depends testListener
     */
    public function testLog()
    {
        $message = 'just test Message';
        $templete = '{key}-{test}';
        $context = array('key' => 'somevalue','test' => 'tstring','t' => 'sss');
        $templete_string = 'somevalue-tstring';
        $json = array('test','key','value');
        foreach (static::$LEVEL as $l) {
            Logger::log($l, $message);
            Logger::log(strtolower($l), $templete, $context);
            Logger::log($l, $json);
            $this->assertPop($l, json_encode($json, 256));
            $this->assertPop($l, $templete_string);
            $this->assertPop($l, $message);
        }
    }

    /**
     * @depends testLog
     */
    public function testInterface()
    {
        $message = 'test log message string';

        Logger::emergency($message);
        $this->assertPop('EMERGENCY', $message);
        Logger::alert($message);
        $this->assertPop('ALERT', $message);
        Logger::critical($message);
        $this->assertPop('CRITICAL', $message);

        Logger::error($message);
        $this->assertPop('ERROR', $message);
        Logger::warning($message);
        $this->assertPop('WARN', $message);
        Logger::warn($message);
        $this->assertPop('WARN', $message);

        Logger::notice($message);
        $this->assertPop('NOTICE', $message);
        Logger::info($message);
        $this->assertPop('INFO', $message);
        Logger::debug($message);
        $this->assertPop('DEBUG', $message);
    }

    /**
     * @depends testWrite
     */
    public function testClear()
    {
        if ('file' === static::$type) {
            $dir = $this->app->getConfig()->runtime.'log/';
            Logger::write('test', 'ERROR');
            Logger::write('test', 'ALERT');
            $this->assertGreaterThan(2, count(scandir($dir)));
            Logger::clear();
            $this->assertCount(2, scandir($dir));
        } else {
            $this->markTestSkipped(static::$type.' log [无需测试]');
        }
    }

    /**
     * @requires OS Linux
     * @covers ::getFile
     */
    public function testDirMode()
    {
        if ('file' === static::$type) {
            $dir = APP_PATH.DIRECTORY_SEPARATOR.'runtime'.DIRECTORY_SEPARATOR.'log'.DIRECTORY_SEPARATOR;
            $this->assertFileMode($dir, 0777);
        } else {
            $this->markTestSkipped(static::$type.' log [无需检查权限]');
        }
    }

    protected static function getLogFile($key = null)
    {
        if ($key) {
            return static::app()->getConfig()->runtime.'log/'. date('y-m-d-').strtoupper($key).'.log';
        }
        return APP_PATH.'/runtime/logger_test_error_log.txt';
    }
    
    protected function assertPop($level, $msg)
    {
        $message = array_pop($this->message);
        return  $this->assertSame($message, array(strtoupper($level) => $msg));
    }
}
