<?php
/**
 * This file is part of the FileLoader package.
 *
 * Copyright (c) 2012-2017, Thomas Mueller <mimmi20@live.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types = 1);
namespace FileLoader\Helper;

use FileLoader\Exception;
use FileLoader\Loader;

class StreamCreator
{
    public const PROXY_PROTOCOL_HTTP  = 'http';
    public const PROXY_PROTOCOL_HTTPS = 'https';

    public const PROXY_AUTH_BASIC = 'basic';
    public const PROXY_AUTH_NTLM  = 'ntlm';

    /**
     * an Loader instance
     *
     * @var \FileLoader\Loader
     */
    private $loader;

    /**
     * sets a loader instance
     *
     * @param \FileLoader\Loader $loader
     *
     * @return \FileLoader\Helper\StreamCreator
     */
    public function __construct(Loader $loader)
    {
        $this->loader = $loader;
    }

    /**
     * @throws \FileLoader\Exception
     *
     * @return resource
     */
    public function getStreamContext()
    {
        // set basic stream context configuration
        $config = [
            'tcp' => [
                'method'          => 'GET',
                'user_agent'      => $this->loader->getUserAgent(),
                // ignore errors, handle them manually
                'ignore_errors'   => true,
                'request_fulluri' => true,
                'timeout'         => $this->loader->getTimeout(),
            ],
        ];

        // check and set proxy settings
        $proxy_host = $this->loader->getOption('ProxyHost');
        if (null !== $proxy_host) {
            // check for supported protocol
            $proxy_protocol = $this->loader->getOption('ProxyProtocol');
            if (null !== $proxy_protocol) {
                if (!in_array($proxy_protocol, [self::PROXY_PROTOCOL_HTTP, self::PROXY_PROTOCOL_HTTPS])) {
                    throw new Exception(
                        'Invalid/unsupported value "' . $proxy_protocol . '" for option "ProxyProtocol".',
                        Exception::INVALID_OPTION
                    );
                }
            } else {
                $proxy_protocol = self::PROXY_PROTOCOL_HTTP;
            }

            // prepare port for the proxy server address
            $proxy_port = $this->loader->getOption('ProxyPort');
            if (null !== $proxy_port) {
                $proxy_port = ':' . $proxy_port;
            } else {
                $proxy_port = '';
            }

            // check auth settings
            $proxy_auth = $this->loader->getOption('ProxyAuth');
            if (null !== $proxy_auth) {
                if (!in_array($proxy_auth, [self::PROXY_AUTH_BASIC])) {
                    throw new Exception(
                        'Invalid/unsupported value "' . $proxy_auth . '" for option "ProxyAuth".',
                        Exception::INVALID_OPTION
                    );
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
            if (null !== $proxy_user) {
                $proxy_password = $this->loader->getOption('ProxyPassword');
                if (null === $proxy_password) {
                    $proxy_password = '';
                }
                $auth                     = base64_encode($proxy_user . ':' . $proxy_password);
                $config['http']['header'] = 'Proxy-Authorization: Basic ' . $auth;
            }

            if (self::PROXY_PROTOCOL_HTTPS === $proxy_protocol) {
                // @todo Add SSL context options
                // @see  http://www.php.net/manual/en/context.ssl.php
            }
        }

        return stream_context_create($config);
    }
}
