<?php
/**
 * class to load a file from a remote source via fsockopen|stream_socket_client
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

use FileLoader\Exception;
use FileLoader\Helper\Http;
use FileLoader\Helper\StreamCreator;
use FileLoader\Loader;
use FileLoader\Interfaces\ConnectorInterface;
use FileLoader\Interfaces\LoadLinesInterface;

/**
 * class to load a file from a remote source via fsockopen|stream_socket_client
 *
 * @package    Browscap
 * @author     Thomas Müller <t_mueller_stolzenhain@yahoo.de>
 * @copyright  Copyright (c) 2014 Thomas Müller
 * @version    1.0
 * @license    http://www.opensource.org/licenses/MIT MIT License
 * @link       https://github.com/mimmi20/FileLoader/
 */
class SocketLoader implements ConnectorInterface, LoadLinesInterface
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
     * a file handle created by fsockopen
     *
     * @var resource
     */
    private $stream = null;

    /**
     * holds the parts of the target url
     *
     * @var array
     */
    private $urlParts = array();

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
        return Loader::UPDATE_FSOCKOPEN;
    }

    /**
     * return TRUE, if this connector is able to return a file line per line
     *
     * @return bool
     */
    public function isSupportingLoadingLines()
    {
        return true;
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
     * @throws Exception
     * @return string|boolean the retrieved data
     */
    public function getRemoteData($url)
    {
        if (false === $this->init($url)) {
            return false;
        }

        if (isset($this->urlParts['query'])) {
            $this->urlParts['path'] .= '?' . $this->urlParts['query'];
        }

        $out = sprintf(
            Loader::REQUEST_HEADERS,
            $this->urlParts['path'],
            $this->urlParts['host'],
            $this->getLoader()->getUserAgent()
        );

        fwrite($this->stream, $out);

        $meta = stream_get_meta_data($this->stream);

        if (isset($meta['wrapper_data']) && is_array($meta['wrapper_data'])) {
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
        }

        $response = '';
        while ($this->isValid()) {
            $response .= $this->getLine() . "\n";
        }

        $this->close();

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
        $errno  = 0;
        $errstr = '';

        ini_set('user_agent', $this->getLoader()->getUserAgent());

        $this->urlParts = parse_url($url);
        $timeout        = $this->getLoader()->getTimeout();

        $this->stream = @fsockopen(
            $this->urlParts['host'],
            $this->getPort(),
            $errno,
            $errstr,
            $timeout
        );

        if (false === $this->stream) {
            return false;
        }

        stream_set_timeout($this->stream, $timeout);
        stream_set_blocking($this->stream, 1);

        return true;
    }

    /**
     * @return integer
     */
    private function getPort()
    {
        if (isset($this->urlParts['port'])) {
            return (int) $this->urlParts['port'];
        }

        if (isset($this->urlParts['scheme']) && $this->urlParts['scheme'] === 'https') {
            return 443;
        }

        return 80;
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
