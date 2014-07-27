<?php
/**
 * class to load a file from a remote source via fopen/file_get_contents
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
 * @author     Thomas Müller <t_mueller_stolzenhain@yahoo.de>
 * @copyright  Copyright (c) 2014 Thomas Müller
 * @version    1.0
 * @license    http://www.opensource.org/licenses/MIT MIT License
 * @link       https://github.com/mimmi20/FileLoader/
 */

namespace FileLoader\Connector;

use FileLoader\Helper\Http;
use FileLoader\Helper\StreamCreator;
use FileLoader\Loader;

/**
 * class to load a file from a remote source via fopen/file_get_contents
 *
 * @package    Browscap
 * @author     Thomas Müller <t_mueller_stolzenhain@yahoo.de>
 * @copyright  Copyright (c) 2014 Thomas Müller
 * @version    1.0
 * @license    http://www.opensource.org/licenses/MIT MIT License
 * @link       https://github.com/mimmi20/FileLoader/
 */
class FopenLoader implements ConnectorInterface
{
    /**
     * an Loader instance
     *
     * @var \FileLoader\Loader
     */
    private $loader = null;

    /**
     * a HTTP Helper instance
     *
     * @var \FileLoader\Helper\Http
     */
    private $httpHelper = null;

    /**
     * a HTTP Helper instance
     *
     * @var \FileLoader\Helper\StreamCreator
     */
    private $streamHelper = null;
    
    /**
     * a file handle created by fopen
     *
     * @var resource
     */
    private $stream = null;

    /**
     * @param \FileLoader\Loader $loader
     *
     * @return \FileLoader\Loader\RemoteLoader
     */
    public function setLoader(Loader $loader)
    {
        $this->loader = $loader;

        return $this;
    }

    /**
     * @return \FileLoader\Loader
     */
    public function getLoader()
    {
        return $this->loader;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return Loader::UPDATE_FOPEN;
    }

    /**
     * sets a http helper instance
     *
     * @param \FileLoader\Helper\Http $helper
     *
     * @return \FileLoader\Loader\RemoteLoader
     */
    public function setHttpHelper(Http $helper)
    {
        $this->httpHelper = $helper;

        return $this;
    }

    /**
     * returns a http helper instance
     *
     * @return \FileLoader\Helper\Http
     */
    public function getHttpHelper()
    {
        return $this->httpHelper;
    }

    /**
     * sets a StreamCreator helper instance
     *
     * @param \FileLoader\Helper\StreamCreator $helper
     *
     * @return \FileLoader\Loader\RemoteLoader
     */
    public function setStreamHelper(StreamCreator $helper)
    {
        $this->streamHelper = $helper;

        return $this;
    }

    /**
     * returns a StreamCreator helper instance
     *
     * @return \FileLoader\Helper\StreamCreator
     */
    public function getStreamHelper()
    {
        return $this->streamHelper;
    }

    /**
     * Retrieve the data identified by the URL
     *
     * @param string $url the url of the data
     *
     * @throws \FileLoader\Exception
     * @return string|boolean the retrieved data
     */
    public function getRemoteData($url)
    {
        if (false === $this->init($url)) {
            return false;
        }
        
        $response = '';
        while ($this->isValid()) {
            $response .= $this->getLine();
        }

        $meta = stream_get_meta_data($this->stream);

        $this->close();
        
        foreach ($meta['wrapper_data'] as $metaData) {
            if ('http/' === substr(strtolower($metaData), 0, 5)) {
                $tmp_status_parts = explode(' ', $metaData, 3);
                $http_code        = $tmp_status_parts[1];
                
                // check for HTTP error
                $http_exception = $this->getHttpHelper()->getHttpErrorException($http_code);

                if ($http_exception !== null) {
                    throw $http_exception;
                }
            }
        }

        $response = str_replace("\r\n", "\n", $response);
        $response = explode("\n\n", $response);
        array_shift($response);

        $response = implode("\n\n", $response);

        return $response;
    }

    /**
     * initialize the connection
     *
     * @param string $url the url of the data
     *
     * @return boolean
     */
    public function init($url)
    {
        $context      = $this->getStreamHelper()->getStreamContext();
        $this->stream = fopen($url, 'r', false, $context);

        if (false === $this->stream) {
            return false;
        }

        $timeout = $this->getLoader()->getTimeout();
        
        stream_set_timeout($this->stream, $timeout);
        stream_set_blocking($this->stream, 1);
        
        return true;
    }
    
    /**
     * checks if the end of the stream is reached
     *
     * @return boolean
     */
    public function isValid()
    {
        return (!feof($this->stream));
    }
    
    /**
     * reads one line from the stream
     *
     * @return string
     */
    public function getLine()
    {
        return stream_get_line($this->stream, 1024, "\n");
    }
    
    /**
     * closes an open stream
     */
    public function close()
    {
        fclose($this->stream);
    }
}
