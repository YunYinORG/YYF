<?php

namespace tests\library;

use Cache as Cache;
use Test\YafCase as TestCase;

class CacheTest extends TestCase
{
    public static function tearDownAfterClass()
    {
        Cache::flush();
    }

    //单组测试数据
    public function singleProvider()
    {
        return [
            'Number Value' => ['_test_key_n', 1234577],
            'bool Value'   => ['_test_key_bool', true, 2],
            'string_value' => ['_test_key_s', 'test_value', 0],
            'array Value'  => ['_test_key_array', ['a', 2, 'c' => 3], 60],
        ];
    }

    //多组测试数据
    public function multiProvider()
    {
        return [
            [
                ['_mkey1_s' => 'test_value', '_mkey1_i' => 10], 0,
            ],
            [
                ['_mkey3_a' => ['test_value2', 122], '_mkey3_bool' => true], 2,
            ],
            [
                ['_mkey2_s' => 'test_value2', '_mkey2bool' => true],
            ],
        ];
    }

    public function setUp()
    {
        $_SERVER['REQUEST_TIME_FLOAT'] = microtime(true);
    }

    /**
     * @dataProvider singleProvider
     */
    public function testSet($key, $value, $exp = 0)
    {
        $this->assertTrue(Cache::set($key, $value, $exp));
    }

    /**
     * @dataProvider multiProvider
     */
    public function testMSet($data, $exp = 0)
    {
        $this->assertTrue(call_user_func_array(['Cache', 'set'], func_get_args()));
    }

    /**
     * @dataProvider singleProvider
     * @depends testSet
     */
    public function testGet($key, $value, $exp = 0)
    {
        $this->assertSame($value, Cache::get($key));
    }

    /**
     * @dataProvider multiProvider
     * @depends testMSet
     */
    public function testMultiGet($data, $exp = 0)
    {
        $keys = array_keys($data);
        $this->assertSame($data, Cache::get($keys));
    }

    /**
     * 测试过期失效.
     *
     * @dataProvider singleProvider
     * @depends testGet
     * @depends testMultiGet
     */
    public function testExpire($key, $value, $exp = 0)
    {
        if ($exp > 0 && $exp < 10) {
            if ($this->app->getConfig()->get('cache.type') === 'file') {
                $_SERVER['REQUEST_TIME'] += $exp + 0.1;
                $this->assertFalse(Cache::get($key), $key);
            } else {
                static::sleep($exp);
                $this->assertFalse(Cache::get($key), $key);
            }
        } else {
            $this->assertSame($value, Cache::get($key), $key.'不应该过期');
        }
    }

    /**
     *测试过期失效.
     *
     * @dataProvider multiProvider
     * @depends testGet
     * @depends testMultiGet
     */
    public function testMultiExpire($data, $exp = 0)
    {
        $keys = array_keys($data);
        if ($exp > 0 && $exp < 10) {
            $data = array_fill_keys($keys, false);
            if ($this->app->getConfig()->get('cache.type') === 'file') {
                //文件存储
                $_SERVER['REQUEST_TIME'] += $exp + 0.1;
            } else {
                //内存存储
                static::sleep($exp);
            }
        }
        $this->assertSame($data, Cache::get($keys));
    }

    /**
     * @dataProvider singleProvider
     * @depends testGet
     */
    public function testDel($key, $value, $exp = 0)
    {
        $result = Cache::del($key);
        if ($exp == 0) {
            $this->assertNotFalse($result);
        }
        $this->assertFalse(Cache::get($key));
    }

    /**
     * @dataProvider singleProvider
     * @depends testDel
     */
    public function testFlush($key, $value, $exp = 0)
    {
        $key = uniqid('_t_cache_');
        $this->assertTrue(Cache::handler()->set($key, 'test value'));
        $this->assertNotFalse(Cache::flush());
        $this->assertFalse(Cache::get($key));
    }

    protected static function sleep($time)
    {
        // usleep($time * 1000000 + 100000);
        $end_time = $_SERVER['REQUEST_TIME_FLOAT'] + $time;
        if ($end_time > microtime(true)) {
            time_sleep_until($end_time);
        }
    }
}
