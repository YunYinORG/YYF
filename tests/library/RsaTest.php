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

use \Rsa as Rsa;
use \Test\YafCase as TestCase;

/**
 * @coversDefaultClass \Encrypt
 */
class RsaTest extends TestCase
{
    public static function setUpBeforeClass()
    {
        \Cache::flush();
    }

    public function strPairProvider()
    {
        return array(
            array('str'=>'abcdefgh'),
            array('str'=> '哈哈哈哈'),
            array('str'=> 1234567),
            array('str'=> ' 427038942yhias '),
            array('str'=> 'a long string z9m=U@Aj~y7X#d6cIMx^k_SfT$51<E,B`osC{O"0n!WH.pb/}Q""Jw]a4RYrhgtGD-\ui(&N*ev;Kq>:Z)2|[l%3VF+PL8?'),
            array('str'=> 'abce','pre'=>1),
            array('str'=> 'sss','pre'=>'somekey'),
            array('str'=> 'ddd','pre'=>1),
        );
    }

    /**
     * 加密解密测试
     *
     * @dataProvider strPairProvider
     *
     * @param string $str
     * @param string $prefix
     */
    public function testRSA($str, $prefix = '')
    {
        $pe=Rsa::encrypt($str, $prefix); //加密
        $this->assertNotEquals(false, $pe, 'encode:'.$prefix);
        $this->assertNotEquals($pe, $str);
        $data = Rsa::decrypt($pe, $prefix);//解密
        $this->assertNotEquals(false, $data, 'decode:'.$prefix);
        $this->assertEquals($str, $data, 'encode decode :'.$prefix);
    }

    public function testUniq()
    {
        $this->assertNotEquals(Rsa::pubKey(), Rsa::pubKey(1));
        $this->assertNotEquals(Rsa::pubKey('somekey'), Rsa::pubKey(1));
    }
}
