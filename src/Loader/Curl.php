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
class Curl extends RemoteLoader
{
    /**
     * Retrieve the data identified by the URL
     *
     * @param string $url the url of the data
     *
     * @throws \RuntimeException
     * @return string|boolean the retrieved data
     */
    protected function getRemoteData($url)
    {
        $ressource = curl_init($url);

        curl_setopt($ressource, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ressource, CURLOPT_CONNECTTIMEOUT, $this->loader->getTimeout());
        curl_setopt($ressource, CURLOPT_USERAGENT, $this->loader->getUserAgent());

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

            $proxy_port = $this->loader->getOption('ProxyPort');

            // set basic proxy options
            curl_setopt($ressource, CURLOPT_PROXY, $proxy_protocol . "://" . $proxy_host);
            if ($proxy_port !== null) {
                curl_setopt($ressource, CURLOPT_PROXYPORT, $proxy_port);
            }

            // check auth settings
            $proxy_user = $this->loader->getOption('ProxyUser');

            // set proxy auth options
            if ($proxy_user !== null) {
                $proxy_password = $this->loader->getOption('ProxyPassword');

                $proxy_auth = $this->loader->getOption('ProxyAuth');
                if ($proxy_auth !== null) {
                    if (!in_array($proxy_auth, array(self::PROXY_AUTH_BASIC, self::PROXY_AUTH_NTLM))) {
                        throw new \RuntimeException("Invalid/unsupported value '$proxy_auth' for option 'ProxyAuth'.");
                    }
                } else {
                    $proxy_auth = self::PROXY_AUTH_BASIC;
                }

                $proxy_auth = $this->loader->getOption('ProxyAuth');
                if ($proxy_auth !== null) {
                    if (!in_array($proxy_auth, array(self::PROXY_AUTH_BASIC, self::PROXY_AUTH_NTLM))) {
                        throw new \RuntimeException("Invalid/unsupported value '$proxy_auth' for option 'ProxyAuth'.");
                    }
                } else {
                    $proxy_auth = self::PROXY_AUTH_BASIC;
                }

                if ($proxy_auth === self::PROXY_AUTH_NTLM) {
                    curl_setopt($ressource, CURLOPT_PROXYAUTH, CURLAUTH_NTLM);
                }
                curl_setopt($ressource, CURLOPT_PROXYUSERPWD, $proxy_user . ":" . $proxy_password);
            }
        }

        $response  = curl_exec($ressource);
        $http_code = curl_getinfo($ressource, CURLINFO_HTTP_CODE);

        curl_close($ressource);

        // check for HTTP error
        $http_exception = $this->getHttpErrorException($http_code);
        if ($http_exception !== null) {
            throw $http_exception;
        }

        return $response;
    }
}
