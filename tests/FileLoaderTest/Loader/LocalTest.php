<?php
/**
 * class to load a file from a local source
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
 * @package    Loader
 * @copyright  2012-2014 Thomas Müller
 * @author     Thomas Müller <t_mueller_stolzenhain@yahoo.de>
 * @license    http://www.opensource.org/licenses/MIT MIT License
 * @link       https://github.com/mimmi20/FileLoader/
 */

namespace FileLoaderTest\Loader;

use FileLoader\Loader;

/**
 * @package    Loader
 * @author     Thomas Müller <t_mueller_stolzenhain@yahoo.de>
 * @copyright  Copyright (c) 2012-2014 Thomas Müller
 * @version    1.2
 * @license    http://www.opensource.org/licenses/MIT MIT License
 * @link       https://github.com/mimmi20/FileLoader/
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
        $object = new Loader();

        $this->object = new Loader\Local($object);
    }

    /**
     * @expectedException \FileLoader\Exception
     * @expectedExceptionMessage the filename can not be empty
     */
    public function testSetLocalFileException()
    {
        $this->object->setLocalFile('');
    }

    public function testSetLocalFile()
    {
        $file   = 'x';
        $return = $this->object->setLocalFile($file);
        self::assertInstanceOf('\FileLoader\Loader\Local', $return);
        self::assertSame($this->object, $return);
        self::assertSame($file, $this->object->getUri());
    }

    public function testLoad()
    {
        $this->object->setLocalFile(__DIR__ . '/../../data/test.txt');

        self::assertSame('This is a test', $this->object->load());
    }

    /**
     * @expectedException \FileLoader\Exception
     * @expectedExceptionMessage Local file is not readable
     */
    public function testLoadFileMissing()
    {
        $this->object->load();
    }

    public function testGetMtime()
    {
        $this->object->setLocalFile(__DIR__ . '/../../data/test.txt');

        self::assertInternalType('integer', $this->object->getMTime());
    }

    /**
     * @expectedException \FileLoader\Exception
     * @expectedExceptionMessage Local file is not readable
     */
    public function testGetMtimeFileMissing()
    {
        $this->object->getMTime();
    }

    public function testGetType()
    {
        self::assertSame(Loader::UPDATE_LOCAL, $this->object->getType());
    }

    public function testIsSupportingLoadingLines()
    {
        self::assertTrue($this->object->isSupportingLoadingLines());
    }
}
