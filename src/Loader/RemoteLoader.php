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

/** @var \FileLoader\Exception */
use FileLoader\Exception;
use FileLoader\Loader;

/**
 * the loader class for requests via curl
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
abstract class RemoteLoader
{
    const PROXY_PROTOCOL_HTTP  = 'http';
    const PROXY_PROTOCOL_HTTPS = 'https';

    const PROXY_AUTH_BASIC = 'basic';
    const PROXY_AUTH_NTLM  = 'ntlm';

    /**
     * an Loader instance
     *
     * @var \FileLoader\Loader
     */
    protected $loader = null;

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
     * XXX save
     *
     * loads the ini file from a remote location
     *
     * @return string the file content
     * @throws Exception
     */
    public function load()
    {
        // Choose the right url
        $file = $this->getRemoteData($this->getUri());

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
        return $this->loader->getRemoteDataUrl();
    }

    /**
     * Gets the remote file update timestamp
     *
     * @throws Exception
     * @return int|string the remote modification timestamp
     */
    public function getMTime()
    {
        $remoteDataUrl  = $this->loader->getRemoteVerUrl();
        $remoteDatetime = $this->getRemoteData($remoteDataUrl);

        if (!$remoteDatetime) {
            throw new Exception(
                'Bad datetime format from ' . $remoteDataUrl,
                Exception::INVALID_DATETIME
            );
        }

        return $remoteDatetime;
    }

    /**
     * Retrieve the data identified by the URL
     *
     * @param string $url the url of the data
     *
     * @throws Exception
     * @return string|boolean the retrieved data
     */
    abstract protected function getRemoteData($url);

    /**
     * Gets the exception to throw if the given HTTP status code is an error code (4xx or 5xx)
     *
     * @param int $http_code
     * @return \RuntimeException|null
     */
    protected function getHttpErrorException($http_code)
    {
        $http_code = (int)$http_code;

        if ($http_code < 400) {
            return null;
        }

        switch ($http_code) {
            case 401:
                return new \RuntimeException("HTTP client error 401: Unauthorized");
            case 403:
                return new \RuntimeException("HTTP client error 403: Forbidden");
            case 404:
                // wrong browscap source url
                return new \RuntimeException("HTTP client error 404: Not Found");
            case 429:
                // rate limit has been exceeded
                return new \RuntimeException("HTTP client error 429: Too many request");
            case 500:
                return new \RuntimeException("HTTP server error 500: Internal Server Error");
            default:
                if ($http_code >= 500) {
                    return new \RuntimeException("HTTP server error $http_code");
                } else {
                    return new \RuntimeException("HTTP client error $http_code");
                }
        }

        return null;
    }

    protected function getStreamContext()
    {
        // set basic stream context configuration
        $config = array(
            'http' => array(
                'user_agent'    => $this->getUserAgent(),
                // ignore errors, handle them manually
                'ignore_errors' => true,
            )
        );

        // check and set proxy settings
        $proxy_host = $this->loader->getOption('ProxyHost');
        if ($proxy_host !== null) {
            // check for supported protocol
            $proxy_protocol = $this->loader->getOption('ProxyProtocol');
            if ($proxy_protocol !== null) {
                if (!in_array($proxy_protocol, array(self::PROXY_PROTOCOL_HTTP, self::PROXY_PROTOCOL_HTTPS))) {
                    throw new \RuntimeException("Invalid/unsupported value '$proxy_protocol' for option 'ProxyProtocol'.");
                }
            } else {
                $proxy_protocol = self::PROXY_PROTOCOL_HTTP;
            }

            // prepare port for the proxy server address
            $proxy_port = $this->loader->getOption('ProxyPort');
            if ($proxy_port !== null) {
                $proxy_port = ":" . $proxy_port;
            } else {
                $proxy_port = "";
            }

            // check auth settings
            $proxy_auth = $this->loader->getOption('ProxyAuth');
            if ($proxy_auth !== null) {
                if (!in_array($proxy_auth, array(self::PROXY_AUTH_BASIC))) {
                    throw new \RuntimeException("Invalid/unsupported value '$proxy_auth' for option 'ProxyAuth'.");
                }
            } else {
                $proxy_auth = self::PROXY_AUTH_BASIC;
            }

            // set proxy server address
            $config['http']['proxy'] = 'tcp://' . $proxy_host . $proxy_port;
            // full uri required by some proxy servers
            $config['http']['request_fulluri'] = true;

            // add authorization header if required
            $proxy_user = $this->loader->getOption('ProxyUser');
            if ($proxy_user !== null) {
                $proxy_password = $this->loader->getOption('ProxyPassword');
                if ($proxy_password === null) {
                    $proxy_password = '';
                }
                $auth = base64_encode($proxy_user . ":" . $proxy_password);
                $config['http']['header'] = "Proxy-Authorization: Basic " . $auth;
            }

            if ($proxy_protocol === self::PROXY_PROTOCOL_HTTPS) {
                // @todo Add SSL context options
                // @see  http://www.php.net/manual/en/context.ssl.php
            }
        }

        return stream_context_create($config);
    }
}
