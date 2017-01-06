<?php

namespace tests\library\Database;

use PDO as PDO;
use Service\Database as Database;
use Test\YafCase as TestCase;

/**
 * @coversDefaultClass \Service\Database
 */
class DatabaseTest extends Testcase
{
    protected static $db = [];

    public static function setUpBeforeClass()
    {
        if (!isset(static::$db['mysql'])) {
            $dsn = 'mysql:host=localhost;port=3306;dbname=yyf;charset=utf8';
            static::$db['mysql'] = new Database($dsn, 'root');
            static::$db['mysql']->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
            static::$db['mysql']->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        }
        if (!isset(static::$db['sqlite'])) {
            $dsn = 'sqlite:'.APP_PATH.'/runtime/yyf.db';
            static::$db['sqlite'] = new Database($dsn);
            static::$db['sqlite']->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
            static::$db['sqlite']->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        }
    }

    public static function tearDownAfterClass()
    {
        foreach (static::$db as $k=>&$db) {
            $db = null;
            unset(static::$db[$k]);
        }
    }

    public function testConnection()
    {
        foreach (static::$db as $key => &$db) {
            $this->assertNotEmpty($db, $key.'database connected failed!');
        }
        $this->assertCount(2, static::$db);
    }

    /**
     * @depends testConnection
     * @covers ::query
     * @covers ::column
     */
    public function testQuery()
    {
        foreach (static::$db as &$db) {
            $this->queryAssert($db);
            $this->columnAssert($db);
        }
    }

    /**
     * @depends testConnection
     * @covers ::exec
     * @covers ::execute
     * @covers ::getType
     */
    public function testExec($db)
    {
        foreach (static::$db as &$db) {
            $this->execAssert($db);
        }
    }

    /**
     * @depends testQuery
     * @covers ::isOK
     * @covers ::errorInfo
     * @covers ::error
     */
    public function testError($db)
    {
        foreach (static::$db as &$db) {
            $this->errorAssert($db);
        }
    }

    /**
     * @depends testQuery
     * @covers ::isOK
     * @covers ::transact
     */
    public function testTransiction($db)
    {
        foreach (static::$db as $key => &$db) {
            $this->transactAssert($db, $key);
        }
    }

    public function queryAssert($db)
    {
        $this->assertCount(2, $db->query('SELECT * FROM user'));
        $dataset = [
            [
                'id'     => 1,
                'account'=> 'newfuture',
                'name'   => 'New Future',
            ],
        ];
        $user = $db->query('SELECT id,account,name FROM user WHERE id=?', [1]);
        $this->assertEquals($dataset, $user);
        $user = $db->query('SELECT id,account,name FROM user WHERE id=:id', [':id'=>1]);
        $this->assertEquals($dataset, $user);
        $user = $db->query('SELECT id,account,name FROM user WHERE id=:id LIMIT 1', ['id'=>1], false);
        $this->assertEquals($dataset[0], $user);
    }

    public function columnAssert($db)
    {
        $name = '测试';
        $this->assertSame($name, $db->column('SELECT name FROM user WHERE id=2'));
        $this->assertSame($name, $db->column('SELECT name FROM user WHERE id=?', [2]));
        $this->assertSame($name, $db->column('SELECT name FROM user WHERE id=:id', ['id'=>2]));
        $this->assertSame($name, $db->column('SELECT name FROM user WHERE id=:id', [':id'=>2]));
    }

    public function execAssert($db)
    {
        $data = [
            'id'        => 3,
            'account'   => 'tester',
            'name'      => 'Database Test',
            'created_at'=> date('Y-m-d h:i:s'),
        ];

        $insert = 'INSERT INTO`user`(`id`,`name`,`account`,`created_at`)VALUES(:id,:name,:account,:created_at)';
        $this->assertSame(1, $db->exec($insert, $data));
        $this->assertEquals(3, $db->column('SELECT COUNT(*) FROM user'));
        $user = $db->query('SELECT id,account,name,created_at FROM user WHERE id=?', [$data['id']], false);
        $this->assertEquals($data, $user);

        $update = 'UPDATE`user`SET`name`= ? WHERE(`id`= ?)';
        $UPDATE_NAME = 'UPDATE_TEST name';
        $this->assertSame(1, $db->exec($update, [$UPDATE_NAME, 3]));
        $data['name'] = $UPDATE_NAME;
        $user = $db->query('SELECT id,account,name,created_at FROM user WHERE id=?', [$data['id']], false);
        $this->assertEquals($data, $user);

        $delete = 'DELETE FROM`user`WHERE(`id`= :id)';
        $this->assertSame(1, $db->exec($delete, [':id'=>$data['id']]));
        $this->assertEquals(2, $db->column('SELECT COUNT(*) FROM user'));
    }

    public function errorAssert($db)
    {
        $this->assertTrue($db->isOk());
        $err = $db->errorInfo();
        $this->assertEquals(0, $err[0]);

        $db->query('SELECT xxxx FROM user WHERE id=?', [1]);
        $this->assertFalse($db->isOk());

        $db->query('SELECT id FROM user WHERE id=1');
        $this->assertTrue($db->isOK());

        $db->exec('DELETE xxxx FROM xx');
        $this->assertFalse($db->isOk());
        $err = $db->errorInfo();
        $this->assertNotEquals(0, $err[0]);

        $db->query('SELECT id FROM user WHERE id=1');
        $this->assertTrue($db->isOK());
    }

    public function transactAssert($db, $type)
    {
        if ('mysql' === $type || $db->exec('PRAGMA foreign_keys = ON')) {
            try {
                $db->beginTransaction();
                if ($db->exec('DELETE FROM`user`WHERE(`id`= ?)', [1]) === false) {
                    $db->rollBack();
                } else {
                    $db->exec('INSERT INTO`user`(`name`,`account`)VALUES(:name,:account)', ['name'=>'New Future', 'account'=>'new_future']);
                    $db->commit();
                }
            } catch (Exception $e) {
                $db->rollBack();
            }
            $this->assertFalse($db->isOk(), $type);
            $this->assertSame('newfuture', $db->column('SELECT account FROM user WHERE id=1'), $type);
        }

        $result = $db->transact(function ($d) {
            $d->exec('INSERT INTO`user`(`id`,`name`)VALUES(?,?)', [2, 'new test']);

            return $db->exec('DELETE FROM`user`WHERE(`id`= ?)', [2]);
        });
        $this->assertFalse($result, $type.'事务失败返回结果不为false');
        $this->assertFalse($db->isOk(), $type);
        $this->assertEquals(2, $db->column('SELECT COUNT(*) FROM user'), $type);
        $this->assertTrue($db->isOk(), $type);
    }
}
