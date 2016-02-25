<?php
/**
 * a helper class to create a file stream
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
class StreamCreatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \FileLoader\Helper\StreamCreator
     */
    private $object = null;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $this->object = new StreamCreator();
    }

    public function testSetLoader()
    {
        $loader = $this->getMock('\FileLoader\Loader', array(), array(), '', false);

        self::assertSame($this->object, $this->object->setLoader($loader));
    }

    public function testGetStreamContextWithoutProxy()
    {
        $loader = $this->getMock('\FileLoader\Loader', array('getOption'), array(), '', false);
        $loader
            ->expects(self::once())
            ->method('getOption')
            ->will(self::returnValue(null));

        self::assertSame($this->object, $this->object->setLoader($loader));
        self::assertTrue(is_resource($this->object->getStreamContext()));
    }

    public function testGetStreamContextWithProxyWithoutAuthAndUser()
    {
        $map = array(
            array('ProxyHost', 'example.org'),
            array('ProxyProtocol', 'http'),
            array('ProxyPort', 80),
            array('ProxyAuth', null),
            array('ProxyUser', null),
        );

        $loader = $this->getMock('\FileLoader\Loader', array('getOption'), array(), '', false);
        $loader
            ->expects(self::exactly(5))
            ->method('getOption')
            ->will(self::returnValueMap($map));

        self::assertSame($this->object, $this->object->setLoader($loader));
        self::assertTrue(is_resource($this->object->getStreamContext()));
    }

    public function testGetStreamContextWithProxyWithoutAuthUserPortAndProtocol()
    {
        $map = array(
            array('ProxyHost', 'example.org'),
            array('ProxyProtocol', null),
            array('ProxyPort', null),
            array('ProxyAuth', null),
            array('ProxyUser', null),
        );

        $loader = $this->getMock('\FileLoader\Loader', array('getOption'), array(), '', false);
        $loader
            ->expects(self::exactly(5))
            ->method('getOption')
            ->will(self::returnValueMap($map));

        self::assertSame($this->object, $this->object->setLoader($loader));
        self::assertTrue(is_resource($this->object->getStreamContext()));
    }

    public function testGetStreamContextWithProxyWithAuthAndUser()
    {
        $map = array(
            array('ProxyHost', 'example.org'),
            array('ProxyProtocol', 'http'),
            array('ProxyPort', 80),
            array('ProxyAuth', StreamCreator::PROXY_AUTH_BASIC),
            array('ProxyUser', 'testUser'),
            array('ProxyPassword', 'testPassword'),
        );

        $loader = $this->getMock('\FileLoader\Loader', array('getOption'), array(), '', false);
        $loader
            ->expects(self::exactly(6))
            ->method('getOption')
            ->will(self::returnValueMap($map));

        self::assertSame($this->object, $this->object->setLoader($loader));
        self::assertTrue(is_resource($this->object->getStreamContext()));
    }

    public function testGetStreamContextWithProxyWithAuthAndUserWithoutPassword()
    {
        $map = array(
            array('ProxyHost', 'example.org'),
            array('ProxyProtocol', 'http'),
            array('ProxyPort', 80),
            array('ProxyAuth', StreamCreator::PROXY_AUTH_BASIC),
            array('ProxyUser', 'testUser'),
            array('ProxyPassword', null),
        );

        $loader = $this->getMock('\FileLoader\Loader', array('getOption'), array(), '', false);
        $loader
            ->expects(self::exactly(6))
            ->method('getOption')
            ->will(self::returnValueMap($map));

        self::assertSame($this->object, $this->object->setLoader($loader));
        self::assertTrue(is_resource($this->object->getStreamContext()));
    }

    /**
     * @expectedException \FileLoader\Exception
     * @expectedExceptionMessage Invalid/unsupported value "htt" for option "ProxyProtocol".
     */
    public function testGetStreamContextWithWrongProtocol()
    {
        $map = array(
            array('ProxyHost', 'example.org'),
            array('ProxyProtocol', 'htt'),
            array('ProxyPort', 80),
            array('ProxyAuth', StreamCreator::PROXY_AUTH_BASIC),
            array('ProxyUser', 'testUser'),
            array('ProxyPassword', 'testPassword'),
        );

        $loader = $this->getMock('\FileLoader\Loader', array('getOption'), array(), '', false);
        $loader
            ->expects(self::exactly(2))
            ->method('getOption')
            ->will(self::returnValueMap($map));

        $this->object->setLoader($loader);
        $this->object->getStreamContext();
    }

    /**
     * @expectedException \FileLoader\Exception
     * @expectedExceptionMessage Invalid/unsupported value "ntlm" for option "ProxyAuth".
     */
    public function testGetStreamContextWithWrongProxyAuthMethod()
    {
        $map = array(
            array('ProxyHost', 'example.org'),
            array('ProxyProtocol', 'http'),
            array('ProxyPort', 80),
            array('ProxyAuth', StreamCreator::PROXY_AUTH_NTLM),
            array('ProxyUser', 'testUser'),
            array('ProxyPassword', 'testPassword'),
        );

        $loader = $this->getMock('\FileLoader\Loader', array('getOption'), array(), '', false);
        $loader
            ->expects(self::exactly(4))
            ->method('getOption')
            ->will(self::returnValueMap($map));

        $this->object->setLoader($loader);
        $this->object->getStreamContext();
    }
}
