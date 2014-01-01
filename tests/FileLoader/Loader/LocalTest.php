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
class LocalTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Loader\Local
     */
    private $object = null;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        parent::setUp();

        $object = new Loader();

        $this->object = new Loader\Local($object);
    }

    public function testConstruct()
    {
        $object = new Loader();
        $local  = new Loader\Local($object);

        self::assertInstanceOf('\\FileLoader\\Loader\\Local', $local);
    }

    /**
     * @expectedException \PHPUnit_Framework_Error_Warning
     */
    public function testSetLocalFileFail()
    {
        $this->object->setLocaleFile();
    }

    /**
     * @expectedException \FileLoader\Exception
     */
    public function testSetLocalFileException()
    {
        $this->object->setLocaleFile('');
    }

    public function testSetLocalFile()
    {
        $file   = 'x';
        $return = $this->object->setLocaleFile($file);
        self::assertInstanceOf('\\FileLoader\\Loader\\Local', $return);
        self::assertSame($this->object, $return);
        self::assertSame($file, $this->object->getUri());
    }

    public function testLoad()
    {
        $this->object->setLocaleFile(__DIR__ . '/../../data/test.txt');

        self::assertSame('This is a test', $this->object->load());
    }

    public function testGetMtime()
    {
        $this->object->setLocaleFile(__DIR__ . '/../../data/test.txt');

        self::assertInternalType('integer', $this->object->getMTime());
    }
}
