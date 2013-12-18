<?php

namespace FileLoaderTest;

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
     * @var Browscap
     */
    private $object = null;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
        parent::setUp();

        $this->object = new \FileLoader\Loader();
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    public function tearDown()
    {
        $this->object->getCache()->flush();

        unset($this->object);

        parent::tearDown();

        $this->object = null;
    }
    
    public function testConstruct()
    {
        $object = new \FileLoader\Loader();
        self::assertInstanceOf('\\FileLoader\\Loader', $object);
    }
    
    public function testConstructWithPath()
    {
        $object = new \FileLoader\Loader(sys_get_temp_dir());
        self::assertInstanceOf('\\FileLoader\\Loader', $object);
        self::assertInstanceOf('\\WurflCache\\Adapter\\File', $object->getCache());
    }
    
    /**
     * @expectedException \FileLoader\Exception
     */
    public function testConstructException()
    {
        $object = new \FileLoader\Loader(false);
    }
    
    /**
     * @expectedException \PHPUnit_Framework_Error
     */
    public function testSetLoggerFail()
    {
        $this->object->setLogger();
    }
    
    public function testSetLogger()
    {
        $return = $this->object->setLogger(new \Monolog\Logger('test'));
        self::assertInstanceOf('\\FileLoader\\Loader', $return);
        self::assertSame($this->object, $return);
    }
    
    /**
     * @expectedException \PHPUnit_Framework_Error
     */
    public function testSetCacheFail()
    {
        $this->object->setCache();
    }
    
    public function testSetCache()
    {
        $cache  = new \WurflCache\Adapter\Memory();
        $return = $this->object->setCache($cache);
        self::assertInstanceOf('\\FileLoader\\Loader', $return);
        self::assertSame($this->object, $return);
        self::assertSame($cache, $this->object->getCache());
    }
    
    /**
     * @expectedException \PHPUnit_Framework_Error_Warning
     */
    public function testSetLocalFileFail()
    {
        $this->object->setLocaleFile();
    }
    
    public function testSetLocalFile()
    {
        $return = $this->object->setLocaleFile('x');
        self::assertInstanceOf('\\FileLoader\\Loader', $return);
        self::assertSame($this->object, $return);
    }
    
    /**
     * @expectedException \PHPUnit_Framework_Error_Warning
     */
    public function testSetCacheFileFail()
    {
        $this->object->setCacheFile();
    }
    
    public function testSetCacheFile()
    {
        $return = $this->object->setCacheFile('y');
        self::assertInstanceOf('\\FileLoader\\Loader', $return);
        self::assertSame($this->object, $return);
    }
    
    /**
     * @expectedException \PHPUnit_Framework_Error_Warning
     */
    public function testSetRemoteDataUrlFail()
    {
        $this->object->setRemoteDataUrl();
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
     * @expectedException \PHPUnit_Framework_Error_Warning
     */
    public function testSetRemoteVerUrlFail()
    {
        $this->object->setRemoteVerUrl();
    }
    
    public function testSetRemoteVerUrl()
    {
        $remoteVerUrl  = 'aa';
        $return = $this->object->setRemoteVerUrl($remoteVerUrl);
        self::assertInstanceOf('\\FileLoader\\Loader', $return);
        self::assertSame($this->object, $return);
        self::assertSame($remoteVerUrl, $this->object->getRemoteVerUrl());
    }

    /**
     * tests the auto detection of the proxy settings from the envirionment
     */
    public function testProxyAutoDetection()
    {
        putenv('http_proxy=http://proxy.example.com:3128');
        putenv('https_proxy=http://proxy.example.com:3128');
        putenv('ftp_proxy=http://proxy.example.com:3128');

        $this->object->autodetectProxySettings();
        $options = $this->object->getStreamContextOptions();

        if (!isset($options['http'])) {
            $this->fail('proxy settings not detected');
        }

        self::assertEquals($options['http']['proxy'], 'tcp://proxy.example.com:3128');
        self::assertTrue($options['http']['request_fulluri']);

        self::assertEquals($options['https']['proxy'], 'tcp://proxy.example.com:3128');
        self::assertTrue($options['https']['request_fulluri']);

        self::assertEquals($options['ftp']['proxy'], 'tcp://proxy.example.com:3128');
        self::assertTrue($options['ftp']['request_fulluri']);
    }

    /**
     * tests if the proxy settings are added
     *
     * @return Browscap
     */
    public function testAddProxySettings()
    {
        $this->object->addProxySettings('proxy.example.com', 3128, 'http');
        $options = $this->object->getStreamContextOptions();

        if (!isset($options['http'])) {
            $this->fail('proxy settings not added');
        }

        self::assertEquals($options['http']['proxy'], 'tcp://proxy.example.com:3128');
        self::assertTrue($options['http']['request_fulluri']);

        return $this->object;
    }

    /**
     * tests if the proxy settings are deleted
     *
     * @param Browscap $object
     *
     * @depends testAddProxySettings
     */
    public function testClearProxySettings($object)
    {
        $clearedWrappers = $object->clearProxySettings();
        $options = $object->getStreamContextOptions();

        self::assertEmpty($options);
        self::assertEquals($clearedWrappers, array('http'));
    }

    /**
     * tests if the steam context is an resource
     *
     * @param Browscap $object
     *
     * @depends testAddProxySettings
     */
    public function testGetStreamContext($object)
    {
        $resource = $object->getStreamContext();

        self::assertTrue(is_resource($resource));
    }
}