<?php
/**
 * class to load a file from a remote source via fsockopen|stream_socket_client
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
 *
 * @copyright  2012-2014 Thomas Müller
 * @author     Thomas Müller <t_mueller_stolzenhain@yahoo.de>
 * @license    http://www.opensource.org/licenses/MIT MIT License
 *
 * @link       https://github.com/mimmi20/FileLoader/
 */

namespace FileLoader\Loader;

use FileLoader\Exception;
use FileLoader\Helper\StreamCreator;
use FileLoader\Interfaces\LoaderInterface;
use FileLoader\Loader;
use GuzzleHttp\Psr7\Response;

/**
 * class to load a file from a remote source via fsockopen|stream_socket_client
 *
 * @author     Thomas Müller <t_mueller_stolzenhain@yahoo.de>
 * @copyright  Copyright (c) 2012-2014 Thomas Müller
 *
 * @version    1.2
 *
 * @license    http://www.opensource.org/licenses/MIT MIT License
 *
 * @link       https://github.com/mimmi20/FileLoader/
 */
class SocketLoader implements LoaderInterface
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
     * @param \FileLoader\Loader               $loader
     * @param \FileLoader\Helper\StreamCreator $streamHelper
     */
    public function __construct(Loader $loader, StreamCreator $streamHelper)
    {
        $this->loader       = $loader;
        $this->streamHelper = $streamHelper;
    }

    /**
     * loads the ini file from a remote location
     *
     * @throws \FileLoader\Exception
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function load()
    {
        return $this->getRemoteData($this->loader->getRemoteDataUrl());
    }

    /**
     * Gets the remote file update timestamp
     *
     * @throws \FileLoader\Exception
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function getMTime()
    {
        return $this->getRemoteData($this->loader->getRemoteVersionUrl());
    }

    /**
     * Retrieve the data identified by the URL
     *
     * @param string $url the url of the data
     *
     * @throws \FileLoader\Exception
     * @return \Psr\Http\Message\ResponseInterface
     */
    private function getRemoteData($url)
    {
        $errno  = 0;
        $errstr = '';

        ini_set('user_agent', $this->loader->getUserAgent());

        $this->urlParts = parse_url($url);
        $timeout        = $this->loader->getTimeout();

        $this->stream = @fsockopen(
            $this->urlParts['host'],
            $this->getPort(),
            $errno,
            $errstr,
            $timeout
        );

        if (false === $this->stream) {
            throw new Exception('could not initialize the socket to load the data');
        }

        stream_set_timeout($this->stream, $timeout);
        stream_set_blocking($this->stream, 1);

        if (isset($this->urlParts['query'])) {
            $this->urlParts['path'] .= '?' . $this->urlParts['query'];
        }

        $out = sprintf(
            Loader::REQUEST_HEADERS,
            $this->urlParts['path'],
            $this->urlParts['host'],
            $this->loader->getUserAgent()
        );

        fwrite($this->stream, $out);

        $response = '';
        while ($this->isValid()) {
            $response .= $this->getLine() . "\n";
        }

        $this->close();

        $response   = str_replace("\r\n", "\n", $response);
        $response   = explode("\n\n", $response);
        $rawHeaders = explode("\n", trim(array_shift($response)));
        $response   = trim(implode("\n\n", $response));
        $headers    = [];
        $code       = 200;

        foreach ($rawHeaders as $rawHeader) {
            $parts  = explode(':', $rawHeader, 2);
            $header = $parts[0];

            if ('http/' === substr(strtolower($header), 0, 5)) {
                $tmp_status_parts = explode(' ', $header, 3);
                $code             = $tmp_status_parts[1];
            }

            if (isset($parts[1])) {
                $value = trim($parts[1]);
            } else {
                $value = '';
            }

            $headers[$header] = $value;
        }

        return new Response($code, $headers, $response);
    }

    /**
     * @return int
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
     * @return bool
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
        return stream_get_line($this->stream, 8192, "\n");
    }

    /**
     * closes an open stream
     */
    public function close()
    {
        fclose($this->stream);
    }
}
