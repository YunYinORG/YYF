<?php
namespace tests\library\Storage;

use \Storage\File as File;
use \Yaf_Application as Application;
use \PHPUnit_Framework_TestCase as TestCase;

class FileTest extends TestCase
{
    const TEST_STRING='yyf file storage test';
    protected static $env;
    protected static $dir;
    protected static $file;

    public static function setUpBeforeClass()
    {
        static::$dir=APP_PATH.DIRECTORY_SEPARATOR.'runtime'.DIRECTORY_SEPARATOR.'tests'.DIRECTORY_SEPARATOR;
        static::$file=new File(static::$dir);
        static::$env= Application::app()->environ();
    }

    /**
    * @requires OS Linux
    */
    public function testDirMode()
    {
        $mode=('dev'===static::$env)?File::$mode:0700;
        clearstatcache();
        $this->assertSame(fileperms(static::$dir)&$mode, $mode);
    }

    public function testSet()
    {
        $name=uniqid('test_set', true);
        static::$file->set($name, FileTest::TEST_STRING);
        $filename=static::$dir.$name.'.php';
        $this->assertFileExists($filename);
        clearstatcache();
        $this->assertSame(fileperms($filename)&File::$mode, File::$mode);
        $this->assertStringEqualsFile($filename, FileTest::TEST_STRING);
        return $name;
    }

    /**
    * @depends testSet
    */
    public function testGet($name)
    {
        $str=static::$file->get($name);
        $this->assertSame($str, FileTest::TEST_STRING);
        $this->assertSame(static::$file->get(uniqid('_rand_', true)), null);
    }

    /**
    * @depends testSet
    */
    public function testDelete($name)
    {
        static::$file->delete($name);
        $this->assertFileNotExists(static::$dir.$name.'.php');
        $this->assertSame(static::$file->get($name), null);
    }

    /**
    * @depends testDelete
    */
    public function testFlush()
    {
        for ($i=0; $i < 10; $i++) {
            static::$file->set('test_'.uniqid(rand(1000, 10000)), rand());
        }
        static::$file->flush();
        $this->assertCount(2, scandir(static::$dir));
    }

    public static function tearDownAfterClass()
    {
        File::cleanDir(static::$dir);
        rmdir(static::$dir);
    }
}
