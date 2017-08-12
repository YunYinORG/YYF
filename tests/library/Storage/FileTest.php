<?php
namespace tests\library\Storage;

use \Storage\File as File;
use \Test\YafCase as TestCase;

/**
 * @coversDefaultClass \Storage\File
 */
class FileTest extends TestCase
{
    const PRE='<?php //';
    const TEST_STRING='yyf file storage test';
    protected static $env;
    protected static $dir;
    protected static $file;

    public static function setUpBeforeClass()
    {
        static::$dir=APP_PATH.DIRECTORY_SEPARATOR.'runtime'.DIRECTORY_SEPARATOR.'tests'.DIRECTORY_SEPARATOR;
        static::$file=new File(static::$dir);
        static::$env= static::app()->environ();
    }

    /**
    * @requires OS Linux
    * @covers ::__construct
    */
    public function testDirMode()
    {
        $mode=$this->assertFileMode(static::$dir, 0777);
    }

    public function testSet()
    {
        $name=uniqid('test_set', true);
        static::$file->set($name, FileTest::TEST_STRING);
        $filename=static::$dir.'.'.$name.'.php';
        $this->assertFileExists($filename);
        $this->assertFileMode($filename);
        $this->assertStringEqualsFile($filename,FileTest::PRE.FileTest::TEST_STRING);
        return $name;
    }

    /**
    * @depends testSet
    */
    public function testGet($name)
    {
        $str=static::$file->get($name);
        $this->assertSame($str, FileTest::TEST_STRING);
        $this->assertFalse(static::$file->get(uniqid('_rand_', true)));
    }

    /**
    * @depends testSet
    */
    public function testDelete($name)
    {
        static::$file->delete($name);
        $this->assertFileNotExists(static::$dir.'.'.$name.'.php');
        $this->assertFalse(static::$file->get($name));
    }

    /**
    * @depends testDelete
    * @covers ::cleanDir
    * @covers ::delete
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
