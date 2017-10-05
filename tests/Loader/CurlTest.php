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

use FileLoader\Loader\Curl;

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
class CurlTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     *
     * @return void
     */
    protected function setUp(): void
    {
        if (!extension_loaded('curl')) {
            self::markTestSkipped('PHP must have cURL support.');
        }
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
            ->will(self::returnValue('http://example.org/'));
        $loader
            ->expects(self::never())
            ->method('getRemoteVersionUrl')
            ->will(self::returnValue('http://browscap.org/version'));

        $object = new Curl($loader);

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

        $object = new Curl($loader);

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
