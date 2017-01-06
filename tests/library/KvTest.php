<?php

namespace tests\library;

use Kv as Kv;
use Test\YafCase as TestCase;

/**
 * @coversDefaultClass \Kv
 */
class KvTest extends TestCase
{
    protected static $DATA = [
        '_test_key_s'   => 'test_value',
        '_test_key_n'   => 123,
        '_test_key_l'   => 'ss',
        '_test_key_null'=> null,
    ];

    protected static $mDATA = [
        '_test_kv2_s'=> '22test_value',
        '_test_kv2_n'=> 22123,
        '_test_kv2_l'=> '222ss',
    ];

    public static function tearDownAfterClass()
    {
        Kv::flush();
    }

    public function testSet()
    {
        foreach (self::$DATA as $key => &$value) {
            $this->assertTrue(Kv::set($key, $value));
        }
    }

    public function testMSet()
    {
        //mset
        $this->assertTrue(Kv::set(static::$mDATA));
    }

    /**
     * @depends testSet
     */
    public function testGet()
    {
        foreach (self::$DATA as $key => &$value) {
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
        $data = static::$mDATA;
        $keys = array_keys($data);
        $this->assertEquals($data, Kv::get($keys));
        //mget with
        $key = '_no.ttkv_key1_.'.rand();
        $keys[] = $key;
        $data[$key] = false;
        $key = '_no_testkv_key_'.rand();
        $keys[] = $key;
        $data[$key] = false;
        $this->assertEquals($data, Kv::get($keys));
    }

    /**
     * @depends testSet
     */
    public function testDel()
    {
        $key = uniqid('_t_kv_d');
        Kv::Handler()->set($key, 'value');
        $this->assertEquals(true, Kv::del($key));
        $this->assertFalse(Kv::get($key));

        // $key='new'.$key;
        // Kv::Handler()->set($key, 'value for delete');
        // $this->assertEquals(true, Kv::delete($key));
        // $this->assertFalse(Kv::get($key));
    }

    /**
     * @depends testDel
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
        $key = uniqid('_t_kv_');
        $this->assertEquals(true, Kv::set($key, 'test value'));
        $this->assertEquals(true, Kv::clear()->set($key.'new', 'newtest'));
        $this->assertFalse(Kv::get($key));
        $this->assertSame('newtest', Kv::get($key.'new'));
    }
}
