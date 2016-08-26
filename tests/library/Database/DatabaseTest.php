<?php
namespace tests\library\Database;

use \Service\Database as Database;
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
        foreach (static::$db as $k=>&$db) {
            $db=null;
            unset(static::$db[$k]);
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
        return $db;
    }

    /**
    * @depends testMysqlQuery
    */
    public function testMysqlExec($db)
    {
        $this->execAssert($db);
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
        $user=$db->query('SELECT id,account,name FROM user WHERE id=:id LIMIT 1', array('id'=>1), false);
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

    public function execAssert($db)
    {
        $data=array(
            'id'=>3,
            'account'=>'tester',
            'name'=>'Database Test',
            'created_at'=>date('Y-m-d h:i:s'),
        );

        $insert='INSERT INTO`user`(`id`,`name`,`account`,`created_at`)VALUES(:id,:name,:account,:created_at)';
        $this->assertSame(1, $db->exec($insert, $data));
        $this->assertEquals(3, $db->column('SELECT COUNT(*) FROM user'));
        $user=$db->query('SELECT id,account,name,created_at FROM user WHERE id=?', array($data['id']), false);
        $this->assertEquals($data, $user);

        $update='UPDATE`user`SET`name`= ? WHERE(`id`= ?)';
        $UPDATE_NAME='UPDATE_TEST name';
        $this->assertSame(1, $db->exec($update, array($UPDATE_NAME, 3)));
        $data['name']=$UPDATE_NAME;
        $user=$db->query('SELECT id,account,name,created_at FROM user WHERE id=?', array($data['id']), false);
        $this->assertEquals($data, $user);

        $delete='DELETE FROM`user`WHERE(`id`= :id)';
        $this->assertSame(1, $db->exec($delete, array(':id'=>$data['id'])));
        $this->assertEquals(2, $db->column('SELECT COUNT(*) FROM user'));
    }
}
