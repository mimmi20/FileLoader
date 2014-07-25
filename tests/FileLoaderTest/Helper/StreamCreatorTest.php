<?php

namespace FileLoaderTest\Helper;

use FileLoader\Helper\StreamCreator;

/**
 * Browscap.ini parsing class with caching and update capabilities
 *
 * PHP version 5
 *
 * Copyright (c) 2006-2012 Jonathan Stoppani
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
 * @package    Browscap
 * @author     Vítor Brandão <noisebleed@noiselabs.org>
 * @copyright  Copyright (c) 2006-2012 Jonathan Stoppani
 * @version    1.0
 * @license    http://www.opensource.org/licenses/MIT MIT License
 * @link       https://github.com/GaretJax/phpbrowscap/
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
            ->will(self::returnValue(null))
        ;
        
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
            ->will(self::returnValueMap($map))
        ;
        
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
            ->will(self::returnValueMap($map))
        ;
        
        self::assertSame($this->object, $this->object->setLoader($loader));
        self::assertTrue(is_resource($this->object->getStreamContext()));
        
    }
}
