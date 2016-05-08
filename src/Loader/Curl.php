<?php
/**
 * class to load a file from a remote source with the curl extension
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
 * class to load a file from a remote source with the curl extension
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
class Curl implements LoaderInterface
{
    /**
     * an Loader instance
     *
     * @var \FileLoader\Loader
     */
    private $loader = null;

    /**
     * a file handle created by fsockopen
     *
     * @var resource
     */
    private $resource = null;

    /**
     * @param \FileLoader\Loader $loader
     *
     * @return \FileLoader\Loader\Curl
     */
    public function __construct(Loader $loader)
    {
        $this->loader = $loader;
    }

    /**
     * loads the ini file from a remote location
     *
     * @throws \FileLoader\Exception
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function load()
    {
        // Choose the right url
        $remoteDataUri = $this->loader->getRemoteDataUrl();
        return $this->getRemoteData($remoteDataUri);
    }

    /**
     * Gets the remote file update timestamp
     *
     * @throws \FileLoader\Exception
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function getMTime()
    {
        $remoteVersionUrl = $this->loader->getRemoteVerUrl();
        return $this->getRemoteData($remoteVersionUrl);
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
        $this->init($url);

        $response   = curl_exec($this->resource);
        $httpCode   = curl_getinfo($this->resource, CURLINFO_HTTP_CODE);
        $headerSize = curl_getinfo($this->resource, CURLINFO_HEADER_SIZE);

        $this->close();

        $rawHeaders = explode("\r\n", trim(substr($response, 0, $headerSize)));
        $headers    = [];

        foreach ($rawHeaders as $rawHeader) {
            $parts  = explode(':', $rawHeader, 2);
            $header = $parts[0];

            if (isset($parts[1])) {
                $value = trim($parts[1]);
            } else {
                $value = '';
            }

            $headers[$header] = $value;
        }

        $body = substr($response, $headerSize);

        return new Response($httpCode, $headers, $body);
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
        curl_setopt($this->resource, CURLOPT_CONNECTTIMEOUT, $this->loader->getTimeout());
        curl_setopt($this->resource, CURLOPT_USERAGENT, $this->loader->getUserAgent());
        curl_setopt($this->resource, CURLOPT_HEADER, true);
        curl_setopt($this->resource, CURLINFO_HEADER_OUT, true);

        // check and set proxy settings
        $proxy_host = $this->loader->getOption('ProxyHost');

        if ($proxy_host !== null) {
            // check for supported protocol
            $proxy_protocol = $this->loader->getOption('ProxyProtocol');

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

            $proxy_port = $this->loader->getOption('ProxyPort');

            // set basic proxy options
            curl_setopt($this->resource, CURLOPT_PROXY, $proxy_protocol . '://' . $proxy_host);
            if ($proxy_port !== null) {
                curl_setopt($this->resource, CURLOPT_PROXYPORT, $proxy_port);
            }

            // check auth settings
            $proxy_user = $this->loader->getOption('ProxyUser');

            // set proxy auth options
            if ($proxy_user !== null) {
                $proxy_password = $this->loader->getOption('ProxyPassword');

                $proxy_auth = $this->loader->getOption('ProxyAuth');
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
