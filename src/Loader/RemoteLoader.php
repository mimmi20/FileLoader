<?php
/**
 * class to load a file from a remote source
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

namespace FileLoader\Loader;

use FileLoader\Interfaces\ConnectorInterface;
use FileLoader\Interfaces\LoaderInterface;
use FileLoader\Interfaces\LoadLinesInterface;
use FileLoader\Exception;
use FileLoader\Helper\Http;
use FileLoader\Helper\StreamCreator;
use FileLoader\Loader;

/**
 * class to load a file from a remote source
 *
 * @package    Browscap
 * @author     Thomas Müller <t_mueller_stolzenhain@yahoo.de>
 * @copyright  Copyright (c) 2014 Thomas Müller
 * @version    1.0
 * @license    http://www.opensource.org/licenses/MIT MIT License
 * @link       https://github.com/mimmi20/FileLoader/
 */
class RemoteLoader implements LoaderInterface, LoadLinesInterface
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
     * @var \FileLoader\Interfaces\ConnectorInterface
     */
    private $connector = null;

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
     * @return \FileLoader\Helper\Http
     */
    public function getStreamHelper()
    {
        return $this->streamHelper;
    }

    /**
     * @param \FileLoader\Interfaces\ConnectorInterface $connector
     *
     * @return \FileLoader\Loader\RemoteLoader
     */
    public function setConnector(ConnectorInterface $connector)
    {
        $this->connector = $connector;

        return $this;
    }

    /**
     * @return \FileLoader\Interfaces\ConnectorInterface|\FileLoader\Interfaces\LoadLinesInterface
     */
    public function getConnector()
    {
        return $this->connector;
    }

    /**
     * XXX save
     *
     * loads the ini file from a remote location
     *
     * @return string the file content
     * @throws \FileLoader\Exception
     */
    public function load()
    {
        // Choose the right url
        $remoteDataUri = $this->getLoader()->getRemoteDataUrl();
        $file          = $this->getConnector()->getRemoteData($remoteDataUri);

        if ($file !== false) {
            return $file;
        }

        throw new Exception('Cannot load the remote file');
    }

    /**
     * returns the uri, used for download
     *
     * @return string
     */
    public function getUri()
    {
        return $this->getLoader()->getRemoteDataUrl();
    }

    /**
     * Gets the remote file update timestamp
     *
     * @throws \FileLoader\Exception
     * @return integer the remote modification timestamp
     */
    public function getMTime()
    {
        $remoteVersionUrl = $this->getLoader()->getRemoteVerUrl();
        $remoteDatetime   = $this->getConnector()->getRemoteData($remoteVersionUrl);

        if (!$remoteDatetime) {
            throw new Exception('Bad datetime format from ' . $remoteVersionUrl, Exception::INVALID_DATETIME);
        }

        return (int) $remoteDatetime;
    }

    /**
     * return TRUE, if this connector is able to return a file line per line
     *
     * @return bool
     */
    public function isSupportingLoadingLines()
    {
        return $this->getConnector()->isSupportingLoadingLines();
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
        return $this->getConnector()->init($url);
    }

    /**
     * checks if the end of the stream is reached
     *
     * @return boolean
     */
    public function isValid()
    {
        return $this->getConnector()->isValid();
    }

    /**
     * reads one line from the stream
     *
     * @return string
     */
    public function getLine()
    {
        return $this->getConnector()->getLine();
    }

    /**
     * closes an open stream
     */
    public function close()
    {
        $this->getConnector()->close();
    }
}
