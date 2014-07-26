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
use FileLoader\Helper\StreamCreator;
use FileLoader\Loader;

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
class SocketLoader implements ConnectorInterface
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
     * @var \FileLoader\Helper\StreamCreator
     */
    private $streamHelper = null;

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
        $errno  = 0;
        $errstr = '';

        list($remoteUrl, $fullRemoteUrl, $context, $timeout) = $this->init($url);

        $stream = stream_socket_client(
            $fullRemoteUrl,
            $errno,
            $errstr,
            $timeout,
            STREAM_CLIENT_CONNECT,
            $context
        );

        if (false === $stream) {
            return false;
        }

        stream_set_timeout($stream, $timeout);
        stream_set_blocking($stream, 1);

        if (isset($remoteUrl['query'])) {
            $remoteUrl['path'] .= '?' . $remoteUrl['query'];
        }

        $out = sprintf(
            Loader::REQUEST_HEADERS,
            $remoteUrl['path'],
            $remoteUrl['host'],
            $this->getLoader()->getUserAgent()
        );

        fwrite($stream, $out);

        $response = stream_get_line($stream, 1024, "\n");
        $response = $this->getFile($response, $stream);

        fclose($stream);

        return $response;
    }

    /**
     * @param string   $response
     * @param resource $stream
     *
     * @return string|null
     */
    private function getFile($response, $stream)
    {
        $file = null;

        if (strpos($response, '200 OK') !== false) {
            $file = '';
            while (!feof($stream)) {
                $file .= stream_get_line($stream, 1024, "\n");
            }

            $file = str_replace("\r\n", "\n", $file);
            $file = explode("\n\n", $file);
            array_shift($file);

            $file = implode("\n\n", $file);
        }

        return $file;
    }

    /**
     * initialize the connection
     *
     * @param string $url the url of the data
     *
     * @return array
     */
    private function init($url)
    {
        $remoteUrl = parse_url($url);

        $port          = $this->getPort($remoteUrl);
        $fullRemoteUrl = $remoteUrl['scheme'] . '://' . $remoteUrl['host'] . ':' . $port;

        $context = $this->getStreamHelper()->getStreamContext();
        $timeout = $this->getLoader()->getTimeout();

        return array($remoteUrl, $fullRemoteUrl, $context, $timeout);
    }

    /**
     * @param array $remoteUrl
     *
     * @return integer
     */
    private function getPort(array $remoteUrl)
    {
        if (isset($remoteUrl['port'])) {
            return (int) $remoteUrl['port'];
        }

        if (isset($remoteUrl['scheme']) && $remoteUrl['scheme'] === 'https') {
            return 443;
        }

        return 80;
    }
}
