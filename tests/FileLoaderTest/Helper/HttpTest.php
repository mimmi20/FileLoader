<?php
/**
 * a helper class to handle http errors
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
 * @package    Helper
 * @copyright  2012-2014 Thomas Müller
 * @author     Thomas Müller <t_mueller_stolzenhain@yahoo.de>
 * @license    http://www.opensource.org/licenses/MIT MIT License
 * @link       https://github.com/mimmi20/FileLoader/
 */

namespace FileLoaderTest\Helper;

use FileLoader\Helper\Http;

/**
 * @package    Helper
 * @author     Thomas Müller <t_mueller_stolzenhain@yahoo.de>
 * @copyright  Copyright (c) 2012-2014 Thomas Müller
 * @version    1.2
 * @license    http://www.opensource.org/licenses/MIT MIT License
 * @link       https://github.com/mimmi20/FileLoader/
 */
class HttpTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \FileLoader\Helper\Http
     */
    private $object = null;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        $this->object = new Http();
    }

    public function testGetHttpErrorException200()
    {
        self::assertNull($this->object->getHttpErrorException(200));
    }

    public function testGetHttpErrorException404()
    {
        $exception = $this->object->getHttpErrorException(404);

        self::assertInstanceOf('\FileLoader\Exception', $exception);
        self::assertSame(404, $exception->getCode());
        self::assertSame("HTTP client error 404: Not Found", $exception->getMessage());
    }

    public function testGetHttpErrorException501()
    {
        $exception = $this->object->getHttpErrorException(501);

        self::assertInstanceOf('\FileLoader\Exception', $exception);
        self::assertSame(501, $exception->getCode());
        self::assertSame("HTTP server error 501", $exception->getMessage());
    }

    public function testGetHttpErrorException400()
    {
        $exception = $this->object->getHttpErrorException(400);

        self::assertInstanceOf('\FileLoader\Exception', $exception);
        self::assertSame(400, $exception->getCode());
        self::assertSame("HTTP client error 400", $exception->getMessage());
    }
}
