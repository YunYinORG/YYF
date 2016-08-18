<?php
namespace tests;

class EnvTest extends \PHPUnit_Framework_TestCase
{
    public function testExt()
    {
       $this->assertTrue(extension_loaded('yaf'),'yaf extension not loaded');
    }
    
    /*conf is exist?*/
    public function testConfFile()
    {
      $conf=APP_PATH . '/conf/app.ini';
      $this->assertFileExists($conf,$conf.' can not find');
    }
}

