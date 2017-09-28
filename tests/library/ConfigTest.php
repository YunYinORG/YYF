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

use \Config as Config;
use \Test\YafCase as TestCase;

/**
 * @coversDefaultClass \Config
 */
class ConfigTest extends TestCase
{
    protected static $bootstrap = false;

    /**
     * @covers ::get
     * 测试配置和配置文件是否一致
     */
    public function testConfigConsistency()
    {
        $env    =$this->app->environ();
        $config =parse_ini_file(APP_PATH.'/conf/app.ini', true);
        $current=$config[$env.':common'] + $config['common'];

        foreach ($current as $key => $value) {
            $this->assertSame($current[$key], Config::get($key), $key);
        }
    }

    /*检测空值*/
    public function testEmpty()
    {
        $this->assertSame(Config::get(uniqid('_te_', true)), null);
    }

    /*测试默认值*/
    public function testDefault()
    {
        $key    =uniqid('_td_', true);
        $default=array(false,null,1,true,array(1,2,4),'test');
        foreach ($default as $k => $d) {
            $this->assertSame(Config::get($k.$key, $d), $d);
        }
    }

    /**
     * @depends testConfigConsistency
     */
    public function testSecretPath()
    {
        $secret_ini=Config::get('secret_path');
        $this->assertFileExists($secret_ini, $secret_ini.' Config cannot find');
        return $secret_ini;
    }

    /**
     * @depends testSecretPath
     * @covers ::getSecret
     *
     * @param mixed $path
     */
    public function testSecret($path)
    {
        $secret=parse_ini_file($path, true);
        foreach ($secret as $name => &$key) {
            foreach ($key as $k => $v) {
                $this->assertSame(Config::getSecret($name, $k), $v, "$name.$k");
            }
        }
    }

    public function testSecretArray()
    {
        $default_db=Config::getSecret('database', 'db._');
        $this->assertNotEmpty($default_db);
        $this->assertArrayHasKey('dsn', $default_db);
    }

    /*检测sceret空值*/
    public function testSecretEmpty()
    {
        $key=uniqid('_ts_', true);
        $this->assertSame(Config::getSecret('database', $key), null);
    }
}
