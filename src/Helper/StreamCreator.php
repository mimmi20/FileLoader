<?php
namespace FileLoader\Helper;

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
use FileLoader\Helper\Http;

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
class StreamCreator
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
    private $loader = null;
    
    /**
     * sets a loader instance
     *
     * @param \FileLoader\Loader $loader
     *
     * @return \FileLoader\Helper\StreamCreator
     */
    public function setLoader(Loader $loader)
    {
        $this->loader = $loader;
        
        return $this;
    }
    
    public function getStreamContext()
    {
        // set basic stream context configuration
        $config = array(
            'http' => array(
                'user_agent'    => $this->loader->getUserAgent(),
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
