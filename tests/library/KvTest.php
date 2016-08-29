<?php
namespace tests\library;

use \Kv as Kv;
use \Test\YafCase as TestCase;

/**
 * @coversDefaultClass \Kv
 */
class KvTest extends TestCase
{
    protected static $DATA=array(
        '_test_key_s'=>'test_value',
        '_test_key_n'=>123,
        '_test_key_l'=>'ss'
    );

    protected static $mDATA=array(
        '_test_kv2_s'=>'22test_value',
        '_test_kv2_n'=>22123,
        '_test_kv2_l'=>'222ss'
    );

    public static function setUpBeforeClass()
    {
        Kv::flush();
    }

    public static function tearDownAfterClass()
    {
        Kv::flush();
    }

    public function testSet()
    {
        foreach (KvTest::$DATA as $key => &$value) {
            $this->assertEquals(true, Kv::set($key, $value));
        }
    }

    public function testMset()
    {
        //mset
        $this->assertGreaterThan(0, Kv::set(static::$mDATA));
    }

    /**
    * @depends testSet
    */
    public function testGet()
    {
        foreach (KvTest::$DATA as $key => &$value) {
            $this->assertEquals($value, Kv::get($key), $key.'不一致');
        }
        $this->assertFalse(Kv::get(uniqid('_t_kv_')));
        $this->assertSame('default', Kv::get(uniqid('_t_kv_'), 'default'));
    }

    /**
    * @depends testSet
    */
    public function testMget()
    {
        //mget
        $data=static::$mDATA;
        $keys=array_keys($data);
        $data=array_values($data);
        $this->assertEquals($data, Kv::get($keys));
        //mget with
        $keys[0]='_no.ttkv_key1_.'.rand();
        $data[0]=false;
        $keys[]='_no_testkv_key_'.rand();
        $data[]=false;
        $this->assertEquals($data, Kv::get($keys));
    }

    /**
    * @depends testSet
    */
    public function testDelete()
    {
        $key=uniqid('_t_kv_d');
        Kv::Handler()->set($key, 'value');
        $this->assertEquals(true, Kv::del($key));
        $this->assertFalse(Kv::get($key));

        $key='new'.$key;
        Kv::Handler()->set($key, 'value for delete');
        $this->assertEquals(true, Kv::delete($key));
        $this->assertFalse(Kv::get($key));
    }

    /**
    * @depends testDelete
    */
    public function testFlush()
    {
        $this->assertNotFalse(Kv::flush());
    }

    /**
    * @depends testFlush
    */
    public function testClear()
    {
        $key=uniqid('_t_kv_');
        $this->assertEquals(true, Kv::set($key, 'test value'));
        $this->assertEquals(true, Kv::clear()->set($key.'new', 'newtest'));
        $this->assertFalse(Kv::get($key));
        $this->assertSame('newtest', Kv::get($key.'new'));
    }
}
