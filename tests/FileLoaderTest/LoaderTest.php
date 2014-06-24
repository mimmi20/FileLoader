<?php

namespace FileLoaderTest;

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
class LoaderTest extends \PHPUnit_Framework_TestCase
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

    public function testConstruct()
    {
        $object = new Loader();
        self::assertInstanceOf('\\FileLoader\\Loader', $object);
    }

    public function testConstructWithPath()
    {
        $object = new Loader(sys_get_temp_dir());
        self::assertInstanceOf('\\FileLoader\\Loader', $object);
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
        $return = $this->object->setLocaleFile('x');
        self::assertInstanceOf('\\FileLoader\\Loader', $return);
        self::assertSame($this->object, $return);
    }

    /**
     * @expectedException \FileLoader\Exception
     */
    public function testSetRemoteDataUrlExceptiom()
    {
        $this->object->setRemoteDataUrl('');
    }

    public function testSetRemoteDataUrl()
    {
        $remoteDataUrl  = 'aa';
        $return = $this->object->setRemoteDataUrl($remoteDataUrl);
        self::assertInstanceOf('\\FileLoader\\Loader', $return);
        self::assertSame($this->object, $return);
        self::assertSame($remoteDataUrl, $this->object->getRemoteDataUrl());
    }

    /**
     * @expectedException \FileLoader\Exception
     */
    public function testSetRemoteVerUrlException()
    {
        $this->object->setRemoteVerUrl('');
    }

    public function testSetRemoteVerUrl()
    {
        $remoteVerUrl  = 'aa';
        $return = $this->object->setRemoteVerUrl($remoteVerUrl);
        self::assertInstanceOf('\\FileLoader\\Loader', $return);
        self::assertSame($this->object, $return);
        self::assertSame($remoteVerUrl, $this->object->getRemoteVerUrl());
    }

    public function testSetTimeout()
    {
        $timeout = 900;
        $return  = $this->object->setTimeout($timeout);
        self::assertInstanceOf('\\FileLoader\\Loader', $return);
        self::assertSame($this->object, $return);
        self::assertSame($timeout, $this->object->getTimeout());
    }

    public function testSetTimeoutNeedInteger()
    {
        $return  = $this->object->setTimeout('abc');
        self::assertInstanceOf('\\FileLoader\\Loader', $return);
        self::assertSame($this->object, $return);
        self::assertSame(0, $this->object->getTimeout());
    }

    public function testSetMode()
    {
        $return = $this->object->setMode();
        self::assertInstanceOf('\\FileLoader\\Loader', $return);
        self::assertSame($this->object, $return);

        $return = $this->object->setMode(Loader::UPDATE_FOPEN);
        self::assertInstanceOf('\\FileLoader\\Loader', $return);
        self::assertSame($this->object, $return);
    }

    /**
     * tests if the proxy settings are added
     *
     * @return Loader
     */
    public function testAddProxySettings()
    {
        $this->object->addProxySettings('proxy.example.com', 3128, 'http');
        $options = $this->object->getStreamContextOptions();

        if (!isset($options['http'])) {
            $this->fail('proxy settings not added');
        }

        self::assertSame($options['http']['proxy'], 'tcp://proxy.example.com:3128');
        self::assertTrue($options['http']['request_fulluri']);

        return $this->object;
    }

    /**
     * tests if the proxy settings are added
     *
     * @param Loader $object
     *
     * @return Loader
     *
     * @depends testAddProxySettings
     */
    public function testAddProxySettingsWithUserPassword(Loader $object)
    {
        $username = 'testname';
        $password = 'testPassword';

        $object->addProxySettings('proxy.example.com', 3128, 'http', $username, $password);
        $options = $object->getStreamContextOptions();

        if (!isset($options['http'])) {
            $this->fail('proxy settings not added');
        }

        self::assertSame($options['http']['proxy'], 'tcp://proxy.example.com:3128');
        self::assertSame(
            $options['http']['header'],
            'Proxy-Authorization: Basic ' . base64_encode($username . ':' . $password)
        );
        self::assertTrue($options['http']['request_fulluri']);

        return $object;
    }

    /**
     * tests if the proxy settings are deleted
     *
     * @param Loader $object
     *
     * @depends testAddProxySettingsWithUserPassword
     */
    public function testClearProxySettings(Loader $object)
    {
        $clearedWrappers = $object->clearProxySettings();
        $options = $object->getStreamContextOptions();

        self::assertEmpty($options);
        self::assertSame($clearedWrappers, array('http'));
    }

    /**
     * tests if the steam context is an resource
     *
     * @param Loader $object
     *
     * @depends testAddProxySettings
     */
    public function testGetStreamContext(Loader $object)
    {
        $resource = $object->getStreamContext();

        self::assertTrue(is_resource($resource));

        $resource = $object->getStreamContext(true);

        self::assertTrue(is_resource($resource));
    }

    public function testGetUserAgent()
    {
        $userAgent = $this->object->getUserAgent();
        self::assertSame('File Loader/1.2.0', $userAgent);
    }

    public function testLoad()
    {
        $this->object
            ->setMode(Loader::UPDATE_LOCAL)
            ->setLocaleFile(__DIR__ . '/../data/test.txt')
        ;

        self::assertSame('This is a test', $this->object->load());
    }

    public function testGetMtime()
    {
        $this->object
            ->setMode(Loader::UPDATE_LOCAL)
            ->setLocaleFile(__DIR__ . '/../data/test.txt')
        ;

        self::assertInternalType('integer', $this->object->getMTime());
    }
}
