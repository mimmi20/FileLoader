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

use FileLoader\Loader\Local;

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
class LocalTest extends \PHPUnit\Framework\TestCase
{
    public function testSetLocalFileException(): void
    {
        $this->expectException('\FileLoader\Exception');
        $this->expectExceptionMessage('the filename can not be empty');

        new Local('');
    }

    public function testLoad(): void
    {
        $object = new Local(__DIR__ . '/../data/test.txt');

        $result = $object->load();

        self::assertInstanceOf('\Psr\Http\Message\ResponseInterface', $result);
        self::assertSame(200, $result->getStatusCode());
        self::assertSame('OK', $result->getReasonPhrase());

        $body = $result->getBody();

        self::assertInstanceOf('\FileLoader\Psr7\Stream', $body);

        $content = $body->getContents();

        self::assertInternalType('string', $content);
    }

    public function testGetMtime(): void
    {
        $object = new Local(__DIR__ . '/../data/test.txt');

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
