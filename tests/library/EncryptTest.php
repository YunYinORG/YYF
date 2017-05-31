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

use \Encrypt as Encrypt;
use \Test\YafCase as TestCase;

/**
 * @coversDefaultClass \Encrypt
 */
class EncryptTest extends TestCase
{
    public function base64Provider()
    {
        return array(
          'en'   => array('YYF Encrypt','WVlGIEVuY3J5cHQ_'),
          'zh'   => array('测试字符串','5rWL6K-V5a2X56ym5Liy'),
          'char' => array('·39~！@#$%……&￥（#@*（+}{PL|>:AD>}WQE~"[]',
            'wrczOX7vvIFAIyQl4oCm4oCmJu-.pe-8iCNAKu-8iCt9e1BMfD46QUQ-fVdRRX4iW10_'),
          'empty'=> array('',''),
        );
    }

    /**
     * 安全BASE64编码测试
     *
     * @dataProvider base64Provider
     *
     * @param mixed $str    编码字符串
     * @param mixed $result 结果
     */
    public function testBase64($str, $result)
    {
        $this->assertSame($result, Encrypt::base64Encode($str));
        $this->assertSame($str, Encrypt::base64Decode($result));
    }

    public function aesProvider()
    {
        return array(
            array('YYF Encrypt','s'),
            array('测试字符串','mysecretkey'),
            'empty'=> array('',''),
            'space'=> array('   hh  ','xxx'),
            'emptykey'=> array('the key in empty',''),
        );
    }

    /**
     * AES加密解密测试
     *
     * @dataProvider aesProvider
     *
     * @param mixed $str 字符
     * @param mixed $key 密钥
     */
    public function testAES($str, $key)
    {
        $cipher = Encrypt::aesEncode($str, $key, false);
        $this->assertNotEquals(false, $cipher);
        $this->assertSame($str, Encrypt::aesDecode($cipher, $key, false), 'raw');
        $cipher = Encrypt::aesEncode($str, $key, true);
        $this->assertNotEquals(false, $cipher);
        $this->assertSame($str, Encrypt::aesDecode($cipher, $key, true), 'safe64');
    }
}
