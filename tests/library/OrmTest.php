<?php

namespace tests\library;

use Orm as Orm;
use Test\YafCase as TestCase;

class OrmTest extends TestCase
{
    const USER_AMOUNT = 2;
    protected $User;

    public function setUp()
    {
        $this->User = new Orm('user');
    }

    public function tearDown()
    {
        unset($this->User);
    }

    protected function User()
    {
        return $this->User->clear();
    }

    public function testSelect()
    {
        $u = $this->User->select('*');
        $this->assertCount(2, $u);
        $u = $this->User->limit(1)->select('id,name');
        $this->assertCount(1, $u);
        $this->assertCount(2, $u[0]);
        $this->assertArrayHasKey('id', $u[0]);
        $this->assertArrayHasKey('name', $u[0]);
    }

    public function testFind()
    {
        $u = $this->User->find(1);
        $this->assertArrayHasKey('id', $u->get());
        $this->assertEquals(1, $u['id']);

        $u = $this->User->field('id,name')->find(2);
        $this->assertEquals(2, $u->id);
        $u = $u->get();
        $this->assertCount(static::USER_AMOUNT, $u);
        $this->assertArrayHasKey('id', $u);
        $this->assertArrayHasKey('name', $u);
    }

    public function testGet()
    {
        $this->User->where('id', 1);
        $this->assertEquals(1, $this->User->get('id'));
        $this->assertEquals(1, $this->User->id);
        $this->assertSame('newfuture', $this->User->get('account'));
    }

    //插入测试数据
    public function InsertProvider()
    {
        return [
                [[
                    'id'      => 3,
                    'account' => 'tester',
                    'name'    => 'test '.substr(__CLASS__, -8),
                ]],
                [[
                    'id'      => 4,
                    'account' => 'new_tester',
                    'name'    => 'New Tester 4',
                ]],
            ];
    }

    /**
     * @dataProvider InsertProvider
     */
    public function testInsert($data)
    {
        $id = $this->User()->insert($data);
        $this->assertEquals($data['id'], $id);

        return $id;
    }

    /**
     * @dataProvider InsertProvider
     * @depends testInsert
     */
    public function testDelete($data)
    {
        if ($data['id'] < 4) {
            $result = $this->User->find($data['id']);
            $this->assertEquals($data['id'], $result->id, 'find the id');
            $this->assertEquals(1, $result->delete(), 'delete by data');
        } else {
            $result = $this->User->delete($data['id']);
            $this->assertEquals(1, $result, 'delete by id');
        }
    }

    /**
     * @dataProvider InsertProvider
     */
    public function testAdd($data)
    {
        foreach ($data as $key => $value) {
            $this->User->set($key, $value);
        }

        $this->assertInstanceOf('Orm', $this->User->add());

        $this->assertEquals(1, $this->User->delete($data['id']));
    }

    public function testInsertAll()
    {
        $data = $this->InsertProvider();
        $data = array_column($data, 0);
        $this->assertEquals(count($data), $this->User->insertAll($data));

        foreach ($data as $u) {
            $this->assertEquals(1,
             $this->User->where($u)->delete()
            );
        }
    }

   //跟新测试数据
    public function updateProvider()
    {
        return [
                [
                    1,
                    [
                        'account' => 'tester_update',
                    ],
                ],
                [
                    2,
                    [
                        'name' => 'update Tester',
                    ],
                ],
            ];
    }

    /**
     * @dataProvider updateProvider
     */
    public function testUpdate($con, array $data)
    {
        if (!is_array($con)) {
            $con = ['id' => $con];
        }
        $old_data = $this->User->where($con)->find()->get();
        $this->assertEquals(1, $this->User()->where($con)->update($data));
        foreach ($data as $key => $value) {
            $this->assertSame($value, $this->User()->where($con)->get($key));
        }
        $this->assertEquals(1, $this->User()->where($con)->update($old_data));
    }

    /**
     * @dataProvider updateProvider
     */
    public function testSave($id, array $data)
    {
        $old_data = $this->User->find($id)->get();
        $this->assertInstanceOf('Orm', $this->User()->set($data)->save($id));
        $user = $this->User()->where('id', $id);
        foreach ($data as $key => $value) {
            $this->assertSame($value, $user->get($key));
        }
        $this->assertEquals(1, $user->update($old_data));
    }

    /**
     * @dataProvider updateProvider
     */
    public function testPut($id, array $data)
    {
        $User = $this->User->find($id);
        foreach ($data as $key => $value) {
            $old_value = $User->get($key);
            $this->assertSame(1, $User->put($key, $value));
            $ORM = new Orm('user');
            $this->assertEquals($value, $ORM->where('id', $id)->get($key));
            $this->assertSame(1, $User->put($key, $old_value));
        }
    }

    public function testIncrement()
    {
        $id = 1;
        $User = $this->User->field('id')->find($id);
        $status = $User->get('status');
        $this->assertEquals(1, $User->increment('status'));
        $this->assertEquals($status + 1, $this->User()->where('id', $id)->get('status'));

        return $id;
    }

    /**
     * @depends testIncrement
     */
    public function testDecrement($id)
    {
        $User = $this->User->where('id', $id);
        $status = $User->get('status');
        $this->assertEquals(1, $User->decrement('status'), "id:$id");
        $this->assertEquals($status - 1, $User->get('status'), "id:$id");
    }

    public function testCount()
    {
        $this->assertEquals(static::USER_AMOUNT, $this->User->count());
    }

    public function testSum()
    {
        $this->assertEquals(1, $this->User->sum('status'));
    }

    public function testOrder()
    {
        $user = $this->User->order('id')->select('id');
        $this->assertEquals(1, $user[0]['id']);

        $user = $this->User->order('id', 'DESC')->select('id');
        $this->assertEquals(2, $user[0]['id']);
    }

    public function testLimit()
    {
        $user = $this->User->limit(2)->select('id');
        $this->assertCount(2, $user);
        $user = $this->User->limit(1, 1)->select('id');
        $this->assertCount(1, $user);
        $this->assertEquals(2, $user[0]['id']);
    }

    public function testPage()
    {
        $user = $this->User->Page(1, 1)->select('id');
        $this->assertCount(1, $user);
        $this->assertEquals(1, $user[0]['id']);
        $user = $this->User->limit(2, 1)->select('id');
        $this->assertCount(1, $user);
        $this->assertEquals(2, $user[0]['id']);
    }
}
