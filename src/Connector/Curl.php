<?php
/**
 * class to load a file from a remote source with the curl extension
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
use FileLoader\Exception;
use FileLoader\Interfaces\ConnectorInterface;

/**
 * class to load a file from a remote source with the curl extension
 *
 * @package    Browscap
 * @author     Thomas Müller <t_mueller_stolzenhain@yahoo.de>
 * @copyright  Copyright (c) 2014 Thomas Müller
 * @version    1.0
 * @license    http://www.opensource.org/licenses/MIT MIT License
 * @link       https://github.com/mimmi20/FileLoader/
 */
class Curl implements ConnectorInterface
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
     * a file handle created by fsockopen
     *
     * @var resource
     */
    private $resource = null;

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
        return Loader::UPDATE_CURL;
    }

    /**
     * return TRUE, if this connector is able to return a file line per line
     *
     * @return bool
     */
    public function isSupportingLoadingLines()
    {
        return false;
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
     * Retrieve the data identified by the URL
     *
     * @param string $url the url of the data
     *
     * @throws \FileLoader\Exception
     * @return string|boolean the retrieved data
     */
    public function getRemoteData($url)
    {
        $this->init($url);

        $response  = curl_exec($this->resource);
        $http_code = curl_getinfo($this->resource, CURLINFO_HTTP_CODE);

        $this->close();

        // check for HTTP error
        $http_exception = $this->getHttpHelper()->getHttpErrorException($http_code);

        if ($http_exception !== null) {
            throw $http_exception;
        }

        return $response;
    }

    /**
     * initialize the connection
     *
     * @param string $url
     *
     * @throws \FileLoader\Exception
     * @return resource
     */
    private function init($url)
    {
        $this->resource = curl_init($url);

        curl_setopt($this->resource, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->resource, CURLOPT_CONNECTTIMEOUT, $this->getLoader()->getTimeout());
        curl_setopt($this->resource, CURLOPT_USERAGENT, $this->getLoader()->getUserAgent());

        // check and set proxy settings
        $proxy_host = $this->getLoader()->getOption('ProxyHost');

        if ($proxy_host !== null) {
            // check for supported protocol
            $proxy_protocol = $this->getLoader()->getOption('ProxyProtocol');

            if ($proxy_protocol !== null) {
                $allowedProtocolls = array(StreamCreator::PROXY_PROTOCOL_HTTP, StreamCreator::PROXY_PROTOCOL_HTTPS);

                if (!in_array($proxy_protocol, $allowedProtocolls)) {
                    throw new Exception(
                        'Invalid/unsupported value "' . $proxy_protocol . '" for option "ProxyProtocol".',
                        Exception::INVALID_OPTION
                    );
                }
            } else {
                $proxy_protocol = StreamCreator::PROXY_PROTOCOL_HTTP;
            }

            $proxy_port = $this->getLoader()->getOption('ProxyPort');

            // set basic proxy options
            curl_setopt($this->resource, CURLOPT_PROXY, $proxy_protocol . '://' . $proxy_host);
            if ($proxy_port !== null) {
                curl_setopt($this->resource, CURLOPT_PROXYPORT, $proxy_port);
            }

            // check auth settings
            $proxy_user = $this->getLoader()->getOption('ProxyUser');

            // set proxy auth options
            if ($proxy_user !== null) {
                $proxy_password = $this->getLoader()->getOption('ProxyPassword');

                $proxy_auth = $this->getLoader()->getOption('ProxyAuth');
                if ($proxy_auth !== null) {
                    $allowedAuth = array(StreamCreator::PROXY_AUTH_BASIC, StreamCreator::PROXY_AUTH_NTLM);

                    if (!in_array($proxy_auth, $allowedAuth)) {
                        throw new Exception(
                            'Invalid/unsupported value "' . $proxy_auth . '" for option "ProxyAuth".',
                            Exception::INVALID_OPTION
                        );
                    }
                } else {
                    $proxy_auth = StreamCreator::PROXY_AUTH_BASIC;
                }

                if ($proxy_auth === StreamCreator::PROXY_AUTH_NTLM) {
                    curl_setopt($this->resource, CURLOPT_PROXYAUTH, CURLAUTH_NTLM);
                }
                curl_setopt($this->resource, CURLOPT_PROXYUSERPWD, $proxy_user . ':' . $proxy_password);
            }
        }

        return true;
    }

    /**
     * closes an open stream
     */
    private function close()
    {
        curl_close($this->resource);
    }
}
