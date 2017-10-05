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
namespace FileLoaderTest;

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
class LoaderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Loader
     */
    private $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     *
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->object = new Loader();
    }

    /**
     * @return void
     */
    public function testSetInvalidOption(): void
    {
        $this->expectException('\FileLoader\Exception');
        $this->expectExceptionMessage('Invalid option key "InvalidOption".');

        $object = new Loader();
        $object->setOption('InvalidOption', 'test');
    }

    /**
     * @return void
     */
    public function testGetInvalidOption(): void
    {
        $this->expectException('\FileLoader\Exception');
        $this->expectExceptionMessage('Invalid option key "InvalidOption".');

        $object = new Loader();
        $object->getOption('InvalidOption');
    }

    /**
     * @return void
     */
    public function testSetGetOption(): void
    {
        $object = new Loader();
        $object->setOption('ProxyProtocol', 'http');
        self::assertSame('http', $object->getOption('ProxyProtocol'));
    }

    /**
     * @return void
     */
    public function testConstructWithValidOption(): void
    {
        $options = ['ProxyProtocol' => 'http'];
        $object  = new Loader($options);
        self::assertSame('http', $object->getOption('ProxyProtocol'));
    }

    /**
     * @return void
     */
    public function testSetLocalFileException(): void
    {
        $this->expectException('\FileLoader\Exception');
        $this->expectExceptionMessage('the filename can not be empty');

        $this->object->setLocalFile('');
    }

    /**
     * @return void
     */
    public function testSetLocalFile(): void
    {
        $this->object->setLocalFile('x');

        $property = new \ReflectionProperty($this->object, 'localFile');
        $property->setAccessible(true);

        self::assertSame('x', $property->getValue($this->object));
    }

    /**
     * @return void
     */
    public function testSetRemoteDataUrlExceptiom(): void
    {
        $this->expectException('\FileLoader\Exception');
        $this->expectExceptionMessage('the parameter $remoteDataUrl can not be empty');

        $this->object->setRemoteDataUrl('');
    }

    /**
     * @return void
     */
    public function testSetRemoteDataUrl(): void
    {
        $remoteDataUrl = 'aa';
        $this->object->setRemoteDataUrl($remoteDataUrl);
        self::assertSame($remoteDataUrl, $this->object->getRemoteDataUrl());
    }

    /**
     * @return void
     */
    public function testSetRemoteVerUrlException(): void
    {
        $this->expectException('\FileLoader\Exception');
        $this->expectExceptionMessage('the parameter $remoteVerUrl can not be empty');

        $this->object->setRemoteVersionUrl('');
    }

    /**
     * @return void
     */
    public function testSetRemoteVerUrl(): void
    {
        $remoteVerUrl = 'aa';
        $this->object->setRemoteVersionUrl($remoteVerUrl);
        self::assertSame($remoteVerUrl, $this->object->getRemoteVersionUrl());
    }

    /**
     * @return void
     */
    public function testSetTimeout(): void
    {
        $timeout = 900;
        $this->object->setTimeout($timeout);
        self::assertSame($timeout, $this->object->getTimeout());
    }

    /**
     * @return void
     */
    public function testGetUserAgent(): void
    {
        $userAgent = $this->object->getUserAgent();
        self::assertSame('FileLoader/3.0.0', $userAgent);
    }

    /**
     * @return void
     */
    public function testLoad(): void
    {
        $this->object->setLocalFile(__DIR__ . '/data/test.txt');

        $result = $this->object->load();

        self::assertInstanceOf('\Psr\Http\Message\ResponseInterface', $result);
    }

    /**
     * @return void
     */
    public function testGetMtime(): void
    {
        $this->object->setLocalFile(__DIR__ . '/data/test.txt');

        $result = $this->object->getMTime();

        self::assertInstanceOf('\Psr\Http\Message\ResponseInterface', $result);
    }
}
