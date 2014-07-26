<?php

namespace FileLoaderTest\Loader;

use FileLoader\Loader;

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
class RemoteLoaderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Loader\RemoteLoader
     */
    private $object = null;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $this->object = new Loader\RemoteLoader();
    }

    public function testSetGetLoader()
    {
        $loader = $this->getMock('\FileLoader\Loader', array(), array(), '', false);

        self::assertSame($this->object, $this->object->setLoader($loader));
        self::assertSame($loader, $this->object->getLoader());
    }

    public function testSetGetHttpHelper()
    {
        $helper = $this->getMock('\FileLoader\Helper\Http', array(), array(), '', false);

        self::assertSame($this->object, $this->object->setHttpHelper($helper));
        self::assertSame($helper, $this->object->getHttpHelper());
    }

    public function testSetGetStreamHelper()
    {
        $helper = $this->getMock('\FileLoader\Helper\StreamCreator', array(), array(), '', false);

        self::assertSame($this->object, $this->object->setStreamHelper($helper));
        self::assertSame($helper, $this->object->getStreamHelper());
    }

    public function testSetGetConnector()
    {
        $connector = $this->getMock('\FileLoader\Connector\Curl', array(), array(), '', false);

        self::assertSame($this->object, $this->object->setConnector($connector));
        self::assertSame($connector, $this->object->getConnector());
    }

    public function testLoad()
    {
        $loader = $this->getMock('\FileLoader\Loader', array(), array(), '', false);
        $this->object->setLoader($loader);

        $connector = $this->getMock('\FileLoader\Connector\Curl', array('getRemoteData'), array(), '', false);
        $connector
            ->expects(self::once())
            ->method('getRemoteData')
            ->will(self::returnValue('This is a test'))
        ;

        $this->object->setConnector($connector);

        self::assertSame('This is a test', $this->object->load());
    }

    /**
     * @expectedException \FileLoader\Exception
     * @expectedExceptionMessage Cannot load the remote file
     */
    public function testLoadFail()
    {
        $loader = $this->getMock('\FileLoader\Loader', array(), array(), '', false);
        $this->object->setLoader($loader);

        $connector = $this->getMock('\FileLoader\Connector\Curl', array('getRemoteData'), array(), '', false);
        $connector
            ->expects(self::once())
            ->method('getRemoteData')
            ->will(self::returnValue(false))
        ;

        $this->object->setConnector($connector);

        $this->object->load();
    }

    public function testGetMtime()
    {
        $loader = $this->getMock('\FileLoader\Loader', array(), array(), '', false);
        $this->object->setLoader($loader);

        $connector = $this->getMock('\FileLoader\Connector\Curl', array('getRemoteData'), array(), '', false);
        $connector
            ->expects(self::once())
            ->method('getRemoteData')
            ->will(self::returnValue(time()))
        ;

        $this->object->setConnector($connector);

        self::assertInternalType('integer', $this->object->getMTime());
    }

    /**
     * @expectedException \FileLoader\Exception
     * @expectedExceptionMessage Bad datetime format from http://example.org/version
     */
    public function testGetMtimeFail()
    {
        $loader = $this->getMock('\FileLoader\Loader', array('getRemoteVerUrl'), array(), '', false);
        $loader
            ->expects(self::once())
            ->method('getRemoteVerUrl')
            ->will(self::returnValue('http://example.org/version'))
        ;

        $this->object->setLoader($loader);

        $connector = $this->getMock('\FileLoader\Connector\Curl', array('getRemoteData'), array(), '', false);
        $connector
            ->expects(self::once())
            ->method('getRemoteData')
            ->will(self::returnValue(false))
        ;

        $this->object->setConnector($connector);

        $this->object->getMTime();
    }
}
