<?php
/**
 * class to load a file from a remote source via fsockopen|stream_socket_client
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
 * @package    Connector
 * @copyright  2012-2014 Thomas Müller
 * @author     Thomas Müller <t_mueller_stolzenhain@yahoo.de>
 * @license    http://www.opensource.org/licenses/MIT MIT License
 * @link       https://github.com/mimmi20/FileLoader/
 */

namespace FileLoaderTest\Connector;

use FileLoader\Connector;
use FileLoader\Loader;

/**
 * @package    Connector
 * @author     Thomas Müller <t_mueller_stolzenhain@yahoo.de>
 * @copyright  Copyright (c) 2012-2014 Thomas Müller
 * @version    1.2
 * @license    http://www.opensource.org/licenses/MIT MIT License
 * @link       https://github.com/mimmi20/FileLoader/
 */
class SocketloaderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Connector\SocketLoader
     */
    private $object = null;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $this->object = new Connector\SocketLoader();
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

    public function testGetType()
    {
        self::assertSame(Loader::UPDATE_FSOCKOPEN, $this->object->getType());
    }

    public function createContext()
    {
        $config = array(
            'tcp' => array(
                'method'          => 'GET',
                'user_agent'      => 'Test-UserAgent',
                // ignore errors, handle them manually
                'ignore_errors'   => true,
                'request_fulluri' => true,
                'timeout'         => 60,
            )
        );

        return stream_context_create($config);
    }

    public function testGetRemoteData()
    {
        $this->markTestSkipped('need to be reworked');

        $loader = $this->getMock('\FileLoader\Loader', array(), array(), '', false);

        $httpHelper = $this->getMock(
            '\FileLoader\Helper\Http',
            array('getHttpErrorException'),
            array(),
            '',
            false
        );
        $httpHelper
            ->expects(self::never())
            ->method('getHttpErrorException')
            ->will(self::returnValue(null))
        ;

        $this->object
            ->setHttpHelper($httpHelper)
            ->setLoader($loader)
        ;

        $response = $this->object->getRemoteData('http://browscap.org/stream?q=PHP_BrowsCapINI');

        self::assertInternalType('boolean', $response);
    }

    public function testIsSupportingLoadingLines()
    {
        self::assertTrue($this->object->isSupportingLoadingLines());
    }
}
