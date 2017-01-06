<?php

namespace tests\library;

use Db as Db;
use Orm as Orm;
use Test\YafCase as TestCase;

class DbTest extends TestCase
{
    /**
     * @dataProvider configProvider
     */
    public function testConnect($config)
    {
        $connection = Db::connect($config);
        $this->assertNotNull($connection);
        $this->assertInstanceOf('\PDO', $connection);
    }

    /**
     * @dataProvider configProvider
     */
    public function testSet($config)
    {
        $connection = Db::set('x', $config);
        $this->assertSame($connection, Db::get('x'));
        Db::set('xx', $connection);
        $this->assertSame($connection, Db::get('xx'));
    }

    public function testGet()
    {
        $connection = Db::current();
        $this->assertSame($connection, Db::get('xxxxx'));
    }

    /**
     * @dataProvider configProvider
     * @depends testGet
     */
    public function testCurrent($config)
    {
        $connection = Db::connect($config);
        Db::current($connection);
        $this->assertSame($connection, Db::current());
    }

    /**
     * @dataProvider configProvider
     * @depends testConnect
     */
    public function testQuery($config)
    {
        Db::set('_read', $config);
        $this->assertCount(2, Db::query('SELECT * FROM user'));
        $dataset = [
            [
                'id'     => '1',
                'account'=> 'newfuture',
                'name'   => 'New Future',
            ],
        ];
        $user = Db::query('SELECT id,account,name FROM user WHERE id=?', [1]);
        $this->assertEquals($dataset, $user);
        //单条查询
        $user = Db::query('SELECT id,account,name FROM user WHERE id=?', [1], false);
        $this->assertEquals($dataset[0], $user);
    }

    /**
     * @dataProvider configProvider
     * @depends testConnect
     */
    public function testColumn($config)
    {
        Db::set('_read', $config);
        $name = '测试';
        $this->assertSame($name, Db::column('SELECT name FROM user WHERE id=2'));
        $this->assertSame($name, Db::column('SELECT name FROM user WHERE id=?', [2]));
        $this->assertSame($name, Db::column('SELECT name FROM user WHERE id=:id', ['id'=>2]));
        $this->assertSame($name, Db::column('SELECT name FROM user WHERE id=:id', [':id'=>2]));
    }

    /**
     * @dataProvider configProvider
     * @depends testQuery
     */
    public function testExec($config)
    {
        Db::set('_read', $config);
        Db::set('_write', $config);

        $data = [
            'id'        => 3,
            'account'   => 'dbtester',
            'name'      => 'Db Test',
            'created_at'=> date('Y-m-d h:i:s'),
        ];
        $insert = 'INSERT INTO`user`(`id`,`name`,`account`,`created_at`)VALUES(:id,:name,:account,:created_at)';
        $this->assertSame(1, Db::exec($insert, $data));
        $this->assertEquals(3, Db::column('SELECT COUNT(*) FROM user'));
        $user = Db::query('SELECT id,account,name,created_at FROM user WHERE id=?', [$data['id']], false);
        $this->assertEquals($data, $user);

        $update = 'UPDATE`user`SET`name`= ? WHERE(`id`= ?)';
        $UPDATE_NAME = 'UPDATE_TEST DB';
        $this->assertSame(1, Db::execute($update, [$UPDATE_NAME, 3]));
        $data['name'] = $UPDATE_NAME;
        $user = Db::query('SELECT id,account,name,created_at FROM user WHERE id=?', [$data['id']], false);
        $this->assertEquals($data, $user);

        $delete = 'DELETE FROM`user`WHERE(`id`= :id)';
        $this->assertSame(1, Db::exec($delete, [':id'=>$data['id']]));
        $this->assertEquals(2, Db::column('SELECT COUNT(*) FROM user'));
    }

    /**
     * @dataProvider tableProvider
     */
    public function testTable($name, $pk = 'id', $prefix = null)
    {
        $orm = call_user_func_array(['Db', 'table'], func_get_args());
        $this->assertInstanceOf('Orm', $orm);
        $this->assertAttributeEquals($pk, '_pk', $orm);
        if (func_num_args() > 2) {
            $this->assertAttributeEquals($prefix, '_pre', $orm);
        }
    }

    /**
     *测试数据库.
     */
    public function configProvider()
    {
        return [
            'default' => ['_'],
            'test'    => ['test'],
            'mysql'   => [
                [
                    'dsn'      => 'mysql:host=localhost;port=3306;dbname=yyf;charset=utf8',
                    'username' => 'root',
                ],
            ],
            'sqlite' => [
                [
                    'dsn' => 'sqlite:'.APP_PATH.'/runtime/yyf.db',
                ],
            ],
        ];
    }

    /**
     *测试数据库.
     */
    public function tableProvider()
    {
        return [
            'only name'           => ['user'],
            'name with pk'        => ['article', 'aid'],
            'name pk with prefix' => ['user', 'uid', 'yyf_'],
        ];
    }
}
