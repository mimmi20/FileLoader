<?php
/**
 * class to load a file from a remote source with the curl extension
 *
 * Copyright (c) 2012-2014 Thomas Müller
 *
 * Permission is hereby granted, free of charge, to any person obtaining a
 * copy of this software and associated documentation files (the "Software"),
 * to deal in the Software without restriction, including without limitation
 * the rights to use, copy, modify, merge, publish, distribute, sublicense,
 * and/or sell copies of the Software, and to permit persons to whom the
 * Software is furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included
 * in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS
 * OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 *
 * @category   FileLoader
 *
 * @copyright  2012-2014 Thomas Müller
 * @author     Thomas Müller <t_mueller_stolzenhain@yahoo.de>
 * @license    http://www.opensource.org/licenses/MIT MIT License
 *
 * @link       https://github.com/mimmi20/FileLoader/
 */

namespace FileLoaderTest\Connector;

use FileLoader\Connector;
use FileLoader\Helper\StreamCreator;
use FileLoader\Loader;

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
class CurlTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Connector\Curl
     */
    private $object = null;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        if (!extension_loaded('curl')) {
            self::markTestSkipped('PHP must have cURL support.');
        }

        $this->object = new Connector\Curl();
    }

    public function testSetGetLoader()
    {
        $loader = $this->getMock('\FileLoader\Loader', [], [], '', false);

        self::assertSame($this->object, $this->object->setLoader($loader));
        self::assertSame($loader, $this->object->getLoader());
    }

    public function testSetGetHttpHelper()
    {
        $helper = $this->getMock('\FileLoader\Helper\Http', [], [], '', false);

        self::assertSame($this->object, $this->object->setHttpHelper($helper));
        self::assertSame($helper, $this->object->getHttpHelper());
    }

    public function testGetType()
    {
        self::assertSame(Loader::UPDATE_CURL, $this->object->getType());
    }

    public function testGetRemoteData()
    {
        $this->markTestSkipped('need to be reworked');

        $loader     = $this->getMock('\FileLoader\Loader', [], [], '', false);
        $httpHelper = $this->getMock(
            '\FileLoader\Helper\Http',
            ['getHttpErrorException'],
            [],
            '',
            false
        );
        $httpHelper
            ->expects(self::once())
            ->method('getHttpErrorException')
            ->will(self::returnValue(null));

        $this->object
            ->setHttpHelper($httpHelper)
            ->setLoader($loader);

        $response = $this->object->getRemoteData('http://example.org/test.ini');

        self::assertInternalType('string', $response);
    }

    public function testIsSupportingLoadingLines()
    {
        self::assertFalse($this->object->isSupportingLoadingLines());
    }

    public function testGetStreamContextWithProxyWithoutAuthAndUser()
    {
        $this->markTestSkipped('need to be reworked');

        $map = [
            ['ProxyHost', 'example.org'],
            ['ProxyProtocol', 'http'],
            ['ProxyPort', 80],
            ['ProxyAuth', null],
            ['ProxyUser', null],
        ];

        $loader = $this->getMock('\FileLoader\Loader', ['getOption'], [], '', false);
        $loader
            ->expects(self::exactly(4))
            ->method('getOption')
            ->will(self::returnValueMap($map));

        $httpHelper = $this->getMock(
            '\FileLoader\Helper\Http',
            ['getHttpErrorException'],
            [],
            '',
            false
        );
        $httpHelper
            ->expects(self::once())
            ->method('getHttpErrorException')
            ->will(self::returnValue(null));

        $this->object
            ->setHttpHelper($httpHelper)
            ->setLoader($loader);

        $response = $this->object->getRemoteData('http://example.org/test.ini');

        self::assertInternalType('string', $response);
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

        $loader = $this->getMock('\FileLoader\Loader', ['getOption'], [], '', false);
        $loader
            ->expects(self::exactly(4))
            ->method('getOption')
            ->will(self::returnValueMap($map));

        $httpHelper = $this->getMock(
            '\FileLoader\Helper\Http',
            ['getHttpErrorException'],
            [],
            '',
            false
        );
        $httpHelper
            ->expects(self::once())
            ->method('getHttpErrorException')
            ->will(self::returnValue(null));

        $this->object
            ->setHttpHelper($httpHelper)
            ->setLoader($loader);

        self::assertFalse($this->object->getRemoteData('http://example.org/test.ini'));
    }

    public function testGetStreamContextWithProxyWithAuthAndUser()
    {
        $this->markTestSkipped('need to be reworked');

        $map = [
            ['ProxyHost', 'example.org'],
            ['ProxyProtocol', 'http'],
            ['ProxyPort', 80],
            ['ProxyAuth', StreamCreator::PROXY_AUTH_BASIC],
            ['ProxyUser', 'testUser'],
            ['ProxyPassword', 'testPassword'],
        ];

        $loader = $this->getMock('\FileLoader\Loader', ['getOption'], [], '', false);
        $loader
            ->expects(self::exactly(6))
            ->method('getOption')
            ->will(self::returnValueMap($map));

        $httpHelper = $this->getMock(
            '\FileLoader\Helper\Http',
            ['getHttpErrorException'],
            [],
            '',
            false
        );
        $httpHelper
            ->expects(self::once())
            ->method('getHttpErrorException')
            ->will(self::returnValue(null));

        $this->object
            ->setHttpHelper($httpHelper)
            ->setLoader($loader);

        $response = $this->object->getRemoteData('http://example.org/test.ini');

        self::assertInternalType('string', $response);
    }

    public function testGetStreamContextWithProxyWithAuthAndUserWithoutPassword()
    {
        $this->markTestSkipped('need to be reworked');

        $map = [
            ['ProxyHost', 'example.org'],
            ['ProxyProtocol', 'http'],
            ['ProxyPort', 80],
            ['ProxyAuth', StreamCreator::PROXY_AUTH_BASIC],
            ['ProxyUser', 'testUser'],
            ['ProxyPassword', null],
        ];

        $loader = $this->getMock('\FileLoader\Loader', ['getOption'], [], '', false);
        $loader
            ->expects(self::exactly(6))
            ->method('getOption')
            ->will(self::returnValueMap($map));

        $httpHelper = $this->getMock(
            '\FileLoader\Helper\Http',
            ['getHttpErrorException'],
            [],
            '',
            false
        );
        $httpHelper
            ->expects(self::once())
            ->method('getHttpErrorException')
            ->will(self::returnValue(null));

        $this->object
            ->setHttpHelper($httpHelper)
            ->setLoader($loader);

        $response = $this->object->getRemoteData('http://example.org/test.ini');

        self::assertInternalType('string', $response);
    }

    /**
     * @expectedException \FileLoader\Exception
     * @expectedExceptionMessage Invalid/unsupported value "htt" for option "ProxyProtocol".
     */
    public function testGetStreamContextWithWrongProtocol()
    {
        $this->markTestSkipped('need to be reworked');

        $map = [
            ['ProxyHost', 'example.org'],
            ['ProxyProtocol', 'htt'],
            ['ProxyPort', 80],
            ['ProxyAuth', StreamCreator::PROXY_AUTH_BASIC],
            ['ProxyUser', 'testUser'],
            ['ProxyPassword', 'testPassword'],
        ];

        $loader = $this->getMock('\FileLoader\Loader', ['getOption'], [], '', false);
        $loader
            ->expects(self::exactly(2))
            ->method('getOption')
            ->will(self::returnValueMap($map));

        $httpHelper = $this->getMock(
            '\FileLoader\Helper\Http',
            ['getHttpErrorException'],
            [],
            '',
            false
        );
        $httpHelper
            ->expects(self::never())
            ->method('getHttpErrorException')
            ->will(self::returnValue(null));

        $this->object
            ->setHttpHelper($httpHelper)
            ->setLoader($loader);

        $response = $this->object->getRemoteData('http://example.org/test.ini');

        self::assertInternalType('string', $response);
    }

    public function testGetStreamContextWithWrongProxyAuthMethod()
    {
        $this->markTestSkipped('need to be reworked');

        $map = [
            ['ProxyHost', 'example.org'],
            ['ProxyProtocol', 'http'],
            ['ProxyPort', 80],
            ['ProxyAuth', StreamCreator::PROXY_AUTH_NTLM],
            ['ProxyUser', 'testUser'],
            ['ProxyPassword', 'testPassword'],
        ];

        $loader = $this->getMock('\FileLoader\Loader', ['getOption'], [], '', false);
        $loader
            ->expects(self::exactly(6))
            ->method('getOption')
            ->will(self::returnValueMap($map));

        $httpHelper = $this->getMock(
            '\FileLoader\Helper\Http',
            ['getHttpErrorException'],
            [],
            '',
            false
        );
        $httpHelper
            ->expects(self::once())
            ->method('getHttpErrorException')
            ->will(self::returnValue(null));

        $this->object
            ->setHttpHelper($httpHelper)
            ->setLoader($loader);

        $response = $this->object->getRemoteData('http://example.org/test.ini');

        self::assertInternalType('string', $response);
    }
}
