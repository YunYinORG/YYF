<?php
namespace tests\library\Database;

use \Service\Database as Database;
use \Yaf_Application as Application;
use \PHPUnit_Framework_TestCase as TestCase;

class DatabaseTest extends Testcase
{

    protected static $db=array();

    public static function setUpBeforeClass()
    {
        if (!isset(static::$db['mysql'])) {
            $dsn='mysql:host=localhost;port=3306;dbname=yyf;charset=utf8';
            static::$db['mysql']=new Database($dsn, 'root');
            if (!static::$db['mysql']) {
                $this->markTestSkipped($dsn.' connect fialed');
            }
        }
    }

    public static function tearDownAfterClass()
    {
        foreach (static::$db as &$db) {
            $db=null;
        }
    }


    public function testMysqlConnection()
    {
        $db= static::$db['mysql'];
        $this->assertNotEmpty($db);
        return $db;
    }


    /**
    * @depends testMysqlConnection
    */
    public function testMysqlQuery($db)
    {
        $this->queryAssert($db);
        $this->columnAssert($db);
    }

    public function queryAssert($db)
    {
        $this->assertCount(2, $db->query('SELECT * FROM user'));
        $dataset=array(
            array(
                'id'=>'1',
                'account'=>'newfuture',
                'name'=>'New Future',
            )
        );
        $user=$db->query('SELECT id,account,name FROM user WHERE id=?', array(1));
        $this->assertSame($dataset, $user);
        $user=$db->query('SELECT id,account,name FROM user WHERE id=:id', array(':id'=>1));
        $this->assertSame($dataset, $user);
        $user=$db->query('SELECT id,account,name FROM user WHERE id=:id', array('id'=>1), false);
        $this->assertSame($dataset[0], $user);
    }

    public function columnAssert($db)
    {
        $name= '测试';
        $this->assertSame($name, $db->column('SELECT name FROM user WHERE id=2'));
        $this->assertSame($name, $db->column('SELECT name FROM user WHERE id=?', array(2)));
        $this->assertSame($name, $db->column('SELECT name FROM user WHERE id=:id', array('id'=>2)));
        $this->assertSame($name, $db->column('SELECT name FROM user WHERE id=:id', array(':id'=>2)));
    }
}
