<?php
namespace FileLoader\Loader;

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
 * @author     Jonathan Stoppani <jonathan@stoppani.name>
 * @author     Vítor Brandão <noisebleed@noiselabs.org>
 * @author     Mikołaj Misiurewicz <quentin389+phpb@gmail.com>
 * @copyright  Copyright (c) 2006-2012 Jonathan Stoppani
 * @version    1.0
 * @license    http://www.opensource.org/licenses/MIT MIT License
 * @link       https://github.com/mimmi20/FileLoader/
 */

/** the main loader class */
use FileLoader\Loader;

/** @var \FileLoader\Exception */
use FileLoader\Exception;

/**
 * the loader class for requests via fsockopen
 *
 * @package    Browscap
 * @author     Jonathan Stoppani <jonathan@stoppani.name>
 * @author     Vítor Brandão <noisebleed@noiselabs.org>
 * @author     Mikołaj Misiurewicz <quentin389+phpb@gmail.com>
 * @copyright  Copyright (c) 2006-2012 Jonathan Stoppani
 * @version    1.0
 * @license    http://www.opensource.org/licenses/MIT MIT License
 * @link       https://github.com/mimmi20/FileLoader/
 */
class SocketLoader
{
    /**
     * an logger instance
     *
     * @var \Psr\Log\LoggerInterface
     */
    private $logger = null;

    /**
     * an logger instance
     *
     * @var Loader
     */
    private $loader = null;

    /**
     * Constructor class, checks for the existence of (and loads) the cache and
     * if needed updated the definitions
     *
     * @param \FileLoader\Loader $loader
     */
    public function __construct(Loader $loader)
    {
        $this->loader = $loader;
    }

    /**
     * sets the logger
     *
     * @param \Psr\Log\LoggerInterface $logger
     *
     * @return \FileLoader\Loader\SocketLoader
     */
    public function setLogger(\Psr\Log\LoggerInterface $logger)
    {
        $this->logger = $logger;

        return $this;
    }

    /**
     * XXX save
     *
     * loads the file from a remote or local location
     *
     * @return string the file content
     */
    public function load()
    {
        // Choose the right url
        $file = $this->getRemoteData($this->getUri());


        if ($file !== false) {
            return $file;
        }

        throw new Exception('Cannot open the local file');
    }
    
    /**
     * returns the uri, used for download
     *
     * @return string
     */
    public function getUri()
    {
        return $this->loader->getRemoteDataUrl();
    }

    /**
     * Gets the remote ini file update timestamp
     *
     * @throws Exception
     * @return int the remote modification timestamp
     */
    private function getMTime()
    {
        $remoteDataUrl = $this->loader->getRemoteVerUrl();

        $remote_datetime = $this->getRemoteData($remoteDataUrl);
        $remote_tmstp    = strtotime($remote_datetime);

        if (!$remote_tmstp) {
            throw new Exception(
                'Bad datetime format from ' . $remoteDataUrl,
                Exception::INVALID_DATETIME
            );
        }

        return $remote_tmstp;
    }

    /**
     * Retrieve the data identified by the URL
     *
     * @param string $url the url of the data
     * @throws Exception
     * @return string|boolean the retrieved data
     */
    private function getRemoteData($url)
    {
        $remote_url     = parse_url($url);
        $remote_handler = fsockopen($remote_url['host'], 80, $errno, $errstr, $this->loader->getTimeout());

        if (!$remote_handler) {
            return false;
        }

        stream_set_timeout($remote_handler, $this->loader->getTimeout());

        if (isset($remote_url['query'])) {
            $remote_url['path'] .= '?' . $remote_url['query'];
        }

        $out = sprintf(
            self::REQUEST_HEADERS,
            $remote_url['path'],
            $remote_url['host'],
            $this->loader->getUserAgent()()
        );

        fwrite($remote_handler, $out);

        $response = fgets($remote_handler);
        if (strpos($response, '200 OK') !== false) {
            $file = '';
            while (!feof($remote_handler)) {
                $file .= fgets($remote_handler);
            }

            $file = str_replace("\r\n", "\n", $file);
            $file = explode("\n\n", $file);
            array_shift($file);

            $file = implode("\n\n", $file);
        }

        fclose($remote_handler);

        if ($file !== false) {
            return $file;
        }

        return false;
    }
}