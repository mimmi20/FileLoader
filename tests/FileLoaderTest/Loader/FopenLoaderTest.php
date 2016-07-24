<?php
/**
 * class to load a file from a remote source via fopen/file_get_contents
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

namespace FileLoaderTest\Loader;

use FileLoader\Loader\FopenLoader;

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
class FopenLoaderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        if (!ini_get('allow_url_fopen')) {
            self::markTestSkipped('"allow_url_fopen" has to be activated in the php.ini');
        }
    }

    private function createContext()
    {
        $config = array(
            'http' => array(
                'method'          => 'GET',
                'user_agent'      => 'Test-UserAgent',
                // ignore errors, handle them manually
                'ignore_errors'   => true,
                'request_fulluri' => true,
                'timeout'         => 60,
            ),
        );
        return stream_context_create($config);
    }

    public function testLoad()
    {
        $loader = $this->getMockBuilder(\FileLoader\Loader::class)
            ->disableOriginalConstructor()
            ->setMethods(['getRemoteDataUrl', 'getRemoteVersionUrl'])
            ->getMock();

        $loader
            ->expects(self::once())
            ->method('getRemoteDataUrl')
            ->will(self::returnValue('http://browscap.org/stream?q=Lite_PHP_BrowsCapINI'))
        ;
        $loader
            ->expects(self::never())
            ->method('getRemoteVersionUrl')
            ->will(self::returnValue('http://browscap.org/version'))
        ;

        $streamHelper = $this->getMockBuilder(\FileLoader\Helper\StreamCreator::class)
            ->disableOriginalConstructor()
            ->setMethods(['getStreamContext'])
            ->getMock();

        $streamHelper
            ->expects(self::once())
            ->method('getStreamContext')
            ->will(self::returnValue($this->createContext()))
        ;

        $object = new FopenLoader($loader, $streamHelper);

        //url: http://browscap.org/stream?q=Lite_PHP_BrowsCapINI
        $result = $object->load();

        self::assertInstanceOf('\Psr\Http\Message\ResponseInterface', $result);
        self::assertSame(200, $result->getStatusCode());
        self::assertSame('OK', $result->getReasonPhrase());

        $body = $result->getBody();

        self::assertInstanceOf('\GuzzleHttp\Psr7\Stream', $body);

        $content = $body->getContents();

        self::assertInternalType('string', $content);
        //self::assertSame('string', $content);
    }

    public function testGetMtime()
    {
        $loader = $this->getMockBuilder(\FileLoader\Loader::class)
            ->disableOriginalConstructor()
            ->setMethods(['getRemoteDataUrl', 'getRemoteVersionUrl'])
            ->getMock();

        $loader
            ->expects(self::never())
            ->method('getRemoteDataUrl')
            ->will(self::returnValue('http://browscap.org/stream?q=Lite_PHP_BrowsCapINI'))
        ;
        $loader
            ->expects(self::once())
            ->method('getRemoteVersionUrl')
            ->will(self::returnValue('http://browscap.org/version'))
        ;

        $streamHelper = $this->getMockBuilder(\FileLoader\Helper\StreamCreator::class)
            ->disableOriginalConstructor()
            ->setMethods(['getStreamContext'])
            ->getMock();

        $streamHelper
            ->expects(self::once())
            ->method('getStreamContext')
            ->will(self::returnValue($this->createContext()))
        ;

        $object = new FopenLoader($loader, $streamHelper);

        //url: http://browscap.org/version
        $result = $object->getMTime();

        self::assertInstanceOf('\Psr\Http\Message\ResponseInterface', $result);
        self::assertSame(200, $result->getStatusCode());
        self::assertSame('OK', $result->getReasonPhrase());
        self::assertCount(11, $result->getHeaders());

        $body = $result->getBody();

        self::assertInstanceOf('\GuzzleHttp\Psr7\Stream', $body);

        $content = $body->getContents();

        self::assertInternalType('string', $content);
        self::assertSame('Mon, 20 Jun 2016 09:16:03 +0000', $content);
    }
}
