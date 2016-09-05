<?php
namespace tests\library;

use \Cache as Cache;
use \Test\YafCase as TestCase;

class CacheTest extends TestCase
{
    public static function tearDownAfterClass()
    {
        Cache::flush();
    }
    
    //单组测试数据
    public function singleProvider()
    {
        return array(
            'string_value' => array('_test_key_s','test_value',0),
            'Number Value' => array('_test_key_n',1234577),
            'bool Value'   => array('_test_key_bool',true,1),
            'array Value'  => array('_test_key_array',['a',2,'c'=>3],2),
        );
    }

    //多组测试数据
    public function multiProvider()
    {
        return array(
            array(
                array('_mkey1_s'=>'test_value','_mkey1_i'=>10),null
            ),
            array(
                array('_mkey2_s'=>'test_value2','_mkey2bool'=>true),
            ),
            array(
                array('_mkey3_a'=>array('test_value2',122),'_mkey2_bool'=>true),1
            ),
        );
    }

    /**
    * @dataProvider singleProvider
    */
    public function testSet($key, $value, $exp=0)
    {
        $this->assertTrue(Cache::set($key, $value, $exp));
    }

    /**
    * @dataProvider singleProvider
    * @depends testSet
    */
    public function testGet($key, $value, $exp=0)
    {
        $this->assertSame($value, Cache::get($key), $key.'不一致');
        
        //测试过期失效
        if ($exp > 0) {
            if ($this->app->getConfig()->get('cache.type') === 'file') {
                $_SERVER['REQUEST_TIME'] += $exp + 0.1;
                $this->assertFalse(Cache::get($key), $key);
            } elseif ($exp < 10) {
                sleep($exp+0.1);
                $this->assertFalse(Cache::get($key), $key);
            }
        }
    }

    /**
    * @dataProvider multiProvider
    * @depends testGet
    */
    public function testMSet($data, $exp=0)
    {
        $this->assertTrue(Cache::set($data, $exp));
    }


    /**
    * @dataProvider multiProvider
    * @depends testMSet
    */
    public function testMGet($data, $exp=0)
    {
        $keys=array_keys($data);
        $this->assertSame($data, Cache::get($keys));
        
        //测试过期失效
        if ($exp > 0) {
            if ($this->app->getConfig()->get('cache.type') === 'file') {
                $_SERVER['REQUEST_TIME']  += $exp + 0.1;
                foreach ($data as &$value) {
                    $value=false;
                }
                $this->assertSame($data, Cache::get($keys));
            } elseif ($exp < 10) {
                sleep($exp + 0.1);
                $this->assertFalse(Cache::get($keys));
            }
        }
    }

    /**
    * @dataProvider singleProvider
    * @depends testGet
    */
    public function testDel($key, $value, $exp=0)
    {
        $result=Cache::del($key);
        if ($exp == 0) {
            $this->assertEquals(true, $result);
        }
        $this->assertFalse(Cache::get($key));
    }


    /**
    * @dataProvider singleProvider
    * @depends testDel
    */
    public function testFlush($key, $value, $exp=0)
    {
        $key=uniqid('_t_cache_');
        $this->assertEquals(true, Cache::handler()->set($key, 'test value'));
        $this->assertNotFalse(Cache::flush());
        $this->assertFalse(Cache::get($key));
    }
}


  // public function testMset()
    // {
    //     //mset
    //     $this->assertGreaterThan(0, Cache::set(static::$mDATA));
    // }

    // /**
    // * @depends testSet
    // */
    // public function testMget()
    // {
    //     //mget
    //     $data=static::$mDATA;
    //     $keys=array_keys($data);
    //     $data=array_values($data);
    //     $this->assertEquals($data, Cache::get($keys));
    //     //mget with
    //     $keys[0]='_no.ttkv_key1_.'.rand();
    //     $data[0]=false;
    //     $keys[]='_no_testkv_key_'.rand();
    //     $data[]=false;
    //     $this->assertEquals($data, Cache::get($keys));
    // }
