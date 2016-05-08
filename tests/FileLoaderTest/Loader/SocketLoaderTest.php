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
 *
 * @copyright  2012-2014 Thomas Müller
 * @author     Thomas Müller <t_mueller_stolzenhain@yahoo.de>
 * @license    http://www.opensource.org/licenses/MIT MIT License
 *
 * @link       https://github.com/mimmi20/FileLoader/
 */

namespace FileLoaderTest\Loader;

use FileLoader\Loader\SocketLoader;

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
class SocketLoaderTest extends \PHPUnit_Framework_TestCase
{
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
        $loader = $this->getMock('\FileLoader\Loader', array('getRemoteDataUrl', 'getRemoteVerUrl'), array(), '', false);
        $loader
            ->expects(self::once())
            ->method('getRemoteDataUrl')
            ->will(self::returnValue('http://browscap.org/stream?q=Lite_PHP_BrowsCapINI'))
        ;
        $loader
            ->expects(self::never())
            ->method('getRemoteVerUrl')
            ->will(self::returnValue('http://browscap.org/version'))
        ;

        $streamHelper = $this->getMock('\FileLoader\Helper\StreamCreator', array('getStreamContext'), array(), '', false);
        $streamHelper
            ->expects(self::never())
            ->method('getStreamContext')
            ->will(self::returnValue($this->createContext()))
        ;

        $object = new SocketLoader($loader, $streamHelper);

        //url: http://browscap.org/stream?q=Lite_PHP_BrowsCapINI
        $result = $object->load();

        self::assertInstanceOf('\Psr\Http\Message\ResponseInterface', $result);
        self::assertSame(200, $result->getStatusCode());
        self::assertSame('OK', $result->getReasonPhrase());

        $body = $result->getBody();

        self::assertInstanceOf('\GuzzleHttp\Psr7\Stream', $body);

        $content = $body->getContents();

        self::assertInternalType('string', $content);
    }

    public function testGetMtime()
    {
        $loader = $this->getMock('\FileLoader\Loader', array('getRemoteDataUrl', 'getRemoteVerUrl'), array(), '', false);
        $loader
            ->expects(self::never())
            ->method('getRemoteDataUrl')
            ->will(self::returnValue('http://browscap.org/stream?q=Lite_PHP_BrowsCapINI'))
        ;
        $loader
            ->expects(self::once())
            ->method('getRemoteVerUrl')
            ->will(self::returnValue('http://browscap.org/version'))
        ;

        $streamHelper = $this->getMock('\FileLoader\Helper\StreamCreator', array('getStreamContext'), array(), '', false);
        $streamHelper
            ->expects(self::never())
            ->method('getStreamContext')
            ->will(self::returnValue($this->createContext()))
        ;

        $object = new SocketLoader($loader, $streamHelper);

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
        self::assertSame('Thu, 21 Apr 2016 09:16:00 +0000', $content);
    }
}
