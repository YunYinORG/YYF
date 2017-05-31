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
    public function emailProvider()
    {
        return array(
            array('yyf@yunyin.org'),
            array('t@t.cn'),
            array('1@i.org'),
            array('gmailtest@gmail.com'),
            array('yyf@longlong.yunyin.org'),
            array('testtestetetetet@163.com'),
        );
    }

    /**
     * 安全BASE64编码测试
     *
     * @dataProvider emailProvider
     *
     * @param mixed $email
     */
    public function testEmail($email)
    {
        $encrypted = Encrypt::encryptEmail($email);
        $this->assertSame($email, Encrypt::decryptEmail($encrypted), $encrypted);
    }

    public function phoneProvider()
    {
        return array(
            array('13888888888','salt',1),
            array('+8617012345679','%*(UWdx([]x',rand()),
            array('13612345678',23445456),
            array('12345','ss'),
            array('123456','ss'),
            array('1234567','salt'),
            array('12345678','salt'),
            array('12345679','salt'),
            array('123456790','salt'),
        );
    }

    /**
     * 安全BASE64编码测试
     *
     * @dataProvider phoneProvider
     *
     * @param mixed $phone
     * @param mixed $salt
     * @param mixed $offset
     */
    public function testPhone($phone, $salt, $offset = false)
    {
        $encrypted = Encrypt::encryptPhone($phone, $salt, $offset);
        $decrypted = Encrypt::decryptPhone($encrypted, $salt, $offset);
        $this->assertSame($phone, $decrypted, $encrypted);
    }
}
