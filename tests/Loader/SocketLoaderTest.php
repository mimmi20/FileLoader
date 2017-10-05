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
class SocketLoaderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @return resource
     */
    private function createContext()
    {
        $config = [
            'http' => [
                'method'          => 'GET',
                'user_agent'      => 'Test-UserAgent',
                // ignore errors, handle them manually
                'ignore_errors'   => true,
                'request_fulluri' => true,
                'timeout'         => 60,
            ],
        ];

        return stream_context_create($config);
    }

    /**
     * @return void
     */
    public function testLoad(): void
    {
        $loader = $this->getMockBuilder(\FileLoader\Loader::class)
            ->disableOriginalConstructor()
            ->setMethods(['getRemoteDataUrl', 'getRemoteVersionUrl'])
            ->getMock();

        $loader
            ->expects(self::once())
            ->method('getRemoteDataUrl')
            ->will(self::returnValue('http://browscap.org/stream?q=Lite_PHP_BrowsCapINI'));
        $loader
            ->expects(self::never())
            ->method('getRemoteVersionUrl')
            ->will(self::returnValue('http://browscap.org/version'));

        $streamHelper = $this->getMockBuilder(\FileLoader\Helper\StreamCreator::class)
            ->disableOriginalConstructor()
            ->setMethods(['getStreamContext'])
            ->getMock();

        $streamHelper
            ->expects(self::never())
            ->method('getStreamContext')
            ->will(self::returnValue($this->createContext()));

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

    /**
     * @return void
     */
    public function testGetMtime(): void
    {
        $loader = $this->getMockBuilder(\FileLoader\Loader::class)
            ->disableOriginalConstructor()
            ->setMethods(['getRemoteDataUrl', 'getRemoteVersionUrl'])
            ->getMock();

        $loader
            ->expects(self::never())
            ->method('getRemoteDataUrl')
            ->will(self::returnValue('http://browscap.org/stream?q=Lite_PHP_BrowsCapINI'));
        $loader
            ->expects(self::once())
            ->method('getRemoteVersionUrl')
            ->will(self::returnValue('http://browscap.org/version'));

        $streamHelper = $this->getMockBuilder(\FileLoader\Helper\StreamCreator::class)
            ->disableOriginalConstructor()
            ->setMethods(['getStreamContext'])
            ->getMock();

        $streamHelper
            ->expects(self::never())
            ->method('getStreamContext')
            ->will(self::returnValue($this->createContext()));

        $object = new SocketLoader($loader, $streamHelper);

        //url: http://browscap.org/version
        $result = $object->getMTime();

        self::assertInstanceOf('\Psr\Http\Message\ResponseInterface', $result);
        self::assertSame(200, $result->getStatusCode());
        self::assertSame('OK', $result->getReasonPhrase());

        $body = $result->getBody();

        self::assertInstanceOf('\GuzzleHttp\Psr7\Stream', $body);

        $content = $body->getContents();

        self::assertInternalType('string', $content);
    }
}
