<?php
/**
 * This file is part of the FileLoader package.
 *
 * Copyright (c) 2012-2017, Thomas Mueller <mimmi20@live.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types = 1);
namespace FileLoaderTest\Helper;

use FileLoader\Helper\StreamCreator;

/**
 * @author     Thomas Müller <t_mueller_stolzenhain@yahoo.de>
 * @copyright  Copyright (c) 2012-2014 Thomas Müller
 *
 * @version    1.2
 *
 * @license    http://www.opensource.org/licenses/MIT MIT License
 *
 * @link       https://github.com/mimmi20/FileLoader/
 */
class StreamCreatorTest extends \PHPUnit\Framework\TestCase
{
    public function testGetStreamContextWithoutProxy()
    {
        $loader = $this->getMockBuilder(\FileLoader\Loader::class)
            ->disableOriginalConstructor()
            ->setMethods(['getOption'])
            ->getMock();

        $loader
            ->expects(self::once())
            ->method('getOption')
            ->will(self::returnValue(null));

        $object = new StreamCreator($loader);
        self::assertTrue(is_resource($object->getStreamContext()));
    }

    public function testGetStreamContextWithProxyWithoutAuthAndUser()
    {
        $map = [
            ['ProxyHost', 'example.org'],
            ['ProxyProtocol', 'http'],
            ['ProxyPort', 80],
            ['ProxyAuth', null],
            ['ProxyUser', null],
        ];

        $loader = $this->getMockBuilder(\FileLoader\Loader::class)
            ->disableOriginalConstructor()
            ->setMethods(['getOption'])
            ->getMock();

        $loader
            ->expects(self::exactly(5))
            ->method('getOption')
            ->will(self::returnValueMap($map));

        $object = new StreamCreator($loader);
        self::assertTrue(is_resource($object->getStreamContext()));
    }

    public function testGetStreamContextWithProxyWithoutAuthUserPortAndProtocol()
    {
        $map = [
            ['ProxyHost', 'example.org'],
            ['ProxyProtocol', null],
            ['ProxyPort', null],
            ['ProxyAuth', null],
            ['ProxyUser', null],
        ];

        $loader = $this->getMockBuilder(\FileLoader\Loader::class)
            ->disableOriginalConstructor()
            ->setMethods(['getOption'])
            ->getMock();

        $loader
            ->expects(self::exactly(5))
            ->method('getOption')
            ->will(self::returnValueMap($map));

        $object = new StreamCreator($loader);
        self::assertTrue(is_resource($object->getStreamContext()));
    }

    public function testGetStreamContextWithProxyWithAuthAndUser()
    {
        $map = [
            ['ProxyHost', 'example.org'],
            ['ProxyProtocol', 'http'],
            ['ProxyPort', 80],
            ['ProxyAuth', StreamCreator::PROXY_AUTH_BASIC],
            ['ProxyUser', 'testUser'],
            ['ProxyPassword', 'testPassword'],
        ];

        $loader = $this->getMockBuilder(\FileLoader\Loader::class)
            ->disableOriginalConstructor()
            ->setMethods(['getOption'])
            ->getMock();

        $loader
            ->expects(self::exactly(6))
            ->method('getOption')
            ->will(self::returnValueMap($map));

        $object = new StreamCreator($loader);
        self::assertTrue(is_resource($object->getStreamContext()));
    }

    public function testGetStreamContextWithProxyWithAuthAndUserWithoutPassword()
    {
        $map = [
            ['ProxyHost', 'example.org'],
            ['ProxyProtocol', 'http'],
            ['ProxyPort', 80],
            ['ProxyAuth', StreamCreator::PROXY_AUTH_BASIC],
            ['ProxyUser', 'testUser'],
            ['ProxyPassword', null],
        ];

        $loader = $this->getMockBuilder(\FileLoader\Loader::class)
            ->disableOriginalConstructor()
            ->setMethods(['getOption'])
            ->getMock();

        $loader
            ->expects(self::exactly(6))
            ->method('getOption')
            ->will(self::returnValueMap($map));

        $object = new StreamCreator($loader);
        self::assertTrue(is_resource($object->getStreamContext()));
    }

    public function testGetStreamContextWithWrongProtocol()
    {
        $this->expectException('\FileLoader\Exception');
        $this->expectExceptionMessage('Invalid/unsupported value "htt" for option "ProxyProtocol".');

        $map = [
            ['ProxyHost', 'example.org'],
            ['ProxyProtocol', 'htt'],
            ['ProxyPort', 80],
            ['ProxyAuth', StreamCreator::PROXY_AUTH_BASIC],
            ['ProxyUser', 'testUser'],
            ['ProxyPassword', 'testPassword'],
        ];

        $loader = $this->getMockBuilder(\FileLoader\Loader::class)
            ->disableOriginalConstructor()
            ->setMethods(['getOption'])
            ->getMock();

        $loader
            ->expects(self::exactly(2))
            ->method('getOption')
            ->will(self::returnValueMap($map));

        $object = new StreamCreator($loader);
        $object->getStreamContext();
    }

    public function testGetStreamContextWithWrongProxyAuthMethod()
    {
        $this->expectException('\FileLoader\Exception');
        $this->expectExceptionMessage('Invalid/unsupported value "ntlm" for option "ProxyAuth".');

        $map = [
            ['ProxyHost', 'example.org'],
            ['ProxyProtocol', 'http'],
            ['ProxyPort', 80],
            ['ProxyAuth', StreamCreator::PROXY_AUTH_NTLM],
            ['ProxyUser', 'testUser'],
            ['ProxyPassword', 'testPassword'],
        ];

        $loader = $this->getMockBuilder(\FileLoader\Loader::class)
            ->disableOriginalConstructor()
            ->setMethods(['getOption'])
            ->getMock();

        $loader
            ->expects(self::exactly(4))
            ->method('getOption')
            ->will(self::returnValueMap($map));

        $object = new StreamCreator($loader);
        $object->getStreamContext();
    }
}
