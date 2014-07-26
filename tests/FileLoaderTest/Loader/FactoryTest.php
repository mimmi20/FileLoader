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
class FactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Loader
     */
    private $object = null;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        parent::setUp();

        $this->object = new Loader();
    }

    public function testBuildLocaleFile()
    {
        $result = Loader\Factory::build($this->object, null, 'xxx');

        self::assertInstanceOf('\\FileLoader\\Loader\\Local', $result);
    }

    /**
     * @expectedException \FileLoader\Exception
     */
    public function testBuildLocaleFileException()
    {
        Loader\Factory::build($this->object, null, '');
    }

    public function testBuildSocketLoader()
    {
        $result = Loader\Factory::build($this->object);

        self::assertInstanceOf('\FileLoader\Loader\RemoteLoader', $result);
    }

    public function testBuildForcedLocaleFile()
    {
        $result = Loader\Factory::build($this->object, Loader::UPDATE_LOCAL, 'xxx');

        self::assertInstanceOf('\FileLoader\Loader\Local', $result);
    }

    /**
     * @expectedException \FileLoader\Exception
     */
    public function testBuildForcedLocaleFileException()
    {
        Loader\Factory::build($this->object, Loader::UPDATE_LOCAL, '');
    }

    public function testBuildForcedSocketLoader()
    {
        $result = Loader\Factory::build($this->object, Loader::UPDATE_FSOCKOPEN);

        self::assertInstanceOf('\FileLoader\Loader\RemoteLoader', $result);
		self::assertInstanceOf('\FileLoader\Connector\SocketLoader', $result->getConnector());
    }

    public function testBuildForcedFopenLoader()
    {
        $result = Loader\Factory::build($this->object, Loader::UPDATE_FOPEN);

        self::assertInstanceOf('\FileLoader\Loader\RemoteLoader', $result);
		self::assertInstanceOf('\FileLoader\Connector\FopenLoader', $result->getConnector());
    }

    public function testBuildForcedCurlLoader()
    {
        $result = Loader\Factory::build($this->object, Loader::UPDATE_CURL);

        self::assertInstanceOf('\FileLoader\Loader\RemoteLoader', $result);
		self::assertInstanceOf('\FileLoader\Connector\Curl', $result->getConnector());
    }

    public function testBuildForcedCustomLoader()
    {
        $connector = $this->getMock('\FileLoader\Connector\FopenLoader', array(), array(), '', false);
		$result    = Loader\Factory::build($this->object, $connector);

        self::assertInstanceOf('\FileLoader\Loader\RemoteLoader', $result);
		self::assertInstanceOf('\FileLoader\Connector\FopenLoader', $result->getConnector());
    }

    /**
     * @expectedException \FileLoader\Exception
     * @expectedExceptionMessage no valid connector found
     * @expectedExceptionCode 0
     */
    public function testBuildForcedInvalid()
    {
        Loader\Factory::build($this->object, 1);
    }
}
