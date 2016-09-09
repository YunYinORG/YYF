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
            'array Value'  => array('_test_key_array',array('a',2,'c'=>3),60),
        );
    }

    //多组测试数据
    public function multiProvider()
    {
        return array(
            array(
                array('_mkey1_s'=>'test_value','_mkey1_i'=>10),0
            ),
            array(
                array('_mkey2_s'=>'test_value2','_mkey2bool'=>true),
            ),
            array(
                array('_mkey3_a'=>array('test_value2',122),'_mkey3_bool'=>true),2
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
                sleep($exp);
                $this->assertFalse(Cache::get($key), $key);
            }
        }
    }

    /**
    * @dataProvider multiProvider
    */
    public function testMSet($data, $exp=0)
    {
        $this->assertTrue(call_user_func_array(array('Cache', 'set'), func_get_args()));
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
            foreach ($data as &$value) {
                $value=false;
            }
            if ($this->app->getConfig()->get('cache.type') === 'file') {
                //文件存储
                $_SERVER['REQUEST_TIME']  += $exp + 0.1;
                $this->assertSame($data, Cache::get($keys));
            } elseif ($exp < 10) {
                //内存存储
                sleep($exp);
                $this->assertSame($data, Cache::get($keys));
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
