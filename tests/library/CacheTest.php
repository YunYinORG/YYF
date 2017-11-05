<?php
/**
 * YYF - A simple, secure, and efficient PHP RESTful Framework.
 *
 * @link https://github.com/YunYinORG/YYF/
 *
 * @license Apache2.0
 * @copyright 2015-2017 NewFuture@yunyin.org
 */

namespace tests\library;

use \Cache as Cache;
use \Test\YafCase as TestCase;

class CacheTest extends TestCase
{
    public static function tearDownAfterClass()
    {
        Cache::flush();
    }

    public function setUp()
    {
        $_SERVER['REQUEST_TIME_FLOAT'] = microtime(true);
    }

    //单组测试数据
    public function singleProvider()
    {
        return array(
            'Number Value' => array('_test_key_n',1234577),
            'bool Value'   => array('_test_key_bool',true,2),
            'string_value' => array('_test_key_s','test_value',0),
            'array Value'  => array('_test_key_array',array('a',2,'c' => 3),60),
        );
    }

    //多组测试数据
    public function multiProvider()
    {
        return array(
            array(
                array('_mkey1_s' => 'test_value','_mkey1_i' => 10),0
            ),
            array(
                array('_mkey3_a' => array('test_value2',122),'_mkey3_bool' => true),2
            ),
            array(
                array('_mkey2_s' => 'test_value2','_mkey2bool' => true),
            ),
        );
    }

    /**
     * @dataProvider singleProvider
     *
     * @param mixed $key
     * @param mixed $value
     * @param mixed $exp
     */
    public function testSet($key, $value, $exp = 0)
    {
        $this->assertTrue(Cache::set($key, $value, $exp));
    }

    /**
     * @dataProvider multiProvider
     *
     * @param mixed $data
     * @param mixed $exp
     */
    public function testMSet($data, $exp = 0)
    {
        $this->assertTrue(call_user_func_array(array('Cache', 'set'), func_get_args()));
    }

    /**
     * @dataProvider singleProvider
     * @depends testSet
     *
     * @param string $key
     * @param mixed  $value
     * @param int    $exp
     */
    public function testGet($key, $value, $exp = 0)
    {
        $this->assertSame($value, Cache::get($key));
    }

    /**
     * @dataProvider multiProvider
     * @depends testMSet
     *
     * @param array $key
     * @param mixed $value
     * @param mixed $data
     * @param mixed $exp
     */
    public function testMultiGet($data, $exp = 0)
    {
        $keys = array_keys($data);
        $this->assertSame($data, Cache::get($keys));
    }

    /**
     * 测试过期失效
     *
     * @dataProvider singleProvider
     * @depends testGet
     * @depends testMultiGet
     *
     * @param string $key
     * @param mixed  $value
     * @param int    $exp
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
     *测试过期失效
     *
     * @dataProvider multiProvider
     * @depends testGet
     * @depends testMultiGet
     *
     * @param array $key
     * @param int   $exp
     * @param mixed $data
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
     *
     * @param string $key
     * @param mixed  $value
     * @param int    $exp
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
     *
     * @param string $key
     * @param mixed  $value
     * @param int    $exp
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
