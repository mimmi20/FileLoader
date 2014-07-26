<?php

namespace FileLoaderTest\Connector;

use FileLoader\Connector;

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
class FopenLoaderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Connector\FopenLoader
     */
    private $object = null;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        if (!ini_get('allow_url_fopen')) {
            $this->markTestSkipped('"allow_url_fopen" has to be activated in the php.ini');
        }

        $this->object = new Connector\FopenLoader();
    }

    public function createContext()
    {
        $config = array(
            'http' => array(
                'user_agent'    => 'Test-UserAgent',
                // ignore errors, handle them manually
                'ignore_errors' => true,
            )
        );

        return stream_context_create($config);
    }

    public function testSetGetLoader()
    {
        $loader = $this->getMock('\FileLoader\Loader', array(), array(), '', false);

        self::assertSame($this->object, $this->object->setLoader($loader));
        self::assertSame($loader, $this->object->getLoader());
    }

    public function testSetGetHttpHelper()
    {
        $helper = $this->getMock('\FileLoader\Helper\Http', array(), array(), '', false);

        self::assertSame($this->object, $this->object->setHttpHelper($helper));
        self::assertSame($helper, $this->object->getHttpHelper());
    }

    public function testSetGetStreamHelper()
    {
        $helper = $this->getMock('\FileLoader\Helper\StreamCreator', array(), array(), '', false);

        self::assertSame($this->object, $this->object->setStreamHelper($helper));
        self::assertSame($helper, $this->object->getStreamHelper());
    }

    public function testGetRemoteData()
    {
        $this->markTestSkipped('need to be reworked');

        $loader      = $this->getMock('\FileLoader\Loader', array(), array(), '', false);
        $steamHelper = $this->getMock(
            '\FileLoader\Helper\StreamCreator',
            array('getStreamContext'),
            array(),
            '',
            false
        );
        $steamHelper
            ->expects(self::once())
            ->method('getStreamContext')
            ->will(self::returnCallback(array($this, 'createContext')))
        ;

        $fopenloader = new Connector\FopenLoader($loader);
        $fopenloader->setStreamHelper($steamHelper);

        $response = $fopenloader->getRemoteData('http://example.org/test.ini');

        self::assertInternalType('string', $response);
    }
}
