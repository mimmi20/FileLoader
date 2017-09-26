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
namespace FileLoader\Loader;

use FileLoader\Exception;
use FileLoader\Helper\StreamCreator;
use FileLoader\Interfaces\LoaderInterface;
use FileLoader\Loader;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ResponseInterface;

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
    private $loader;

    /**
     * a file handle created by fsockopen
     *
     * @var resource
     */
    private $resource;

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
     *
     * @return \GuzzleHttp\Psr7\Response
     */
    public function load(): ResponseInterface
    {
        return $this->getRemoteData($this->loader->getRemoteDataUrl());
    }

    /**
     * Gets the remote file update timestamp
     *
     * @throws \FileLoader\Exception
     *
     * @return \GuzzleHttp\Psr7\Response
     */
    public function getMTime(): ResponseInterface
    {
        return $this->getRemoteData($this->loader->getRemoteVersionUrl());
    }

    /**
     * Retrieve the data identified by the URL
     *
     * @param string $url the url of the data
     *
     * @throws \FileLoader\Exception
     *
     * @return \GuzzleHttp\Psr7\Response
     */
    private function getRemoteData($url): ResponseInterface
    {
        $this->init($url);
        $version = '1.1';

        $response   = curl_exec($this->resource);
        $httpCode   = curl_getinfo($this->resource, CURLINFO_HTTP_CODE);
        $headerSize = curl_getinfo($this->resource, CURLINFO_HEADER_SIZE);

        $this->close();

        $rawHeaders = explode("\r\n", trim(mb_substr($response, 0, $headerSize)));
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

        $body = mb_substr($response, $headerSize);

        return new Response($httpCode, $headers, $body, $version);
    }

    /**
     * initialize the connection
     *
     * @param string $url
     *
     * @throws \FileLoader\Exception
     */
    private function init($url): void
    {
        $this->resource = curl_init($url);

        curl_setopt($this->resource, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->resource, CURLOPT_CONNECTTIMEOUT, $this->loader->getTimeout());
        curl_setopt($this->resource, CURLOPT_USERAGENT, $this->loader->getUserAgent());
        curl_setopt($this->resource, CURLOPT_HEADER, true);
        curl_setopt($this->resource, CURLINFO_HEADER_OUT, true);

        // check and set proxy settings
        $proxy_host = $this->loader->getOption('ProxyHost');

        if (null !== $proxy_host) {
            // check for supported protocol
            $proxy_protocol = $this->loader->getOption('ProxyProtocol');

            if (null !== $proxy_protocol) {
                $allowedProtocolls = [StreamCreator::PROXY_PROTOCOL_HTTP, StreamCreator::PROXY_PROTOCOL_HTTPS];

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
            if (null !== $proxy_port) {
                curl_setopt($this->resource, CURLOPT_PROXYPORT, $proxy_port);
            }

            // check auth settings
            $proxy_user = $this->loader->getOption('ProxyUser');

            // set proxy auth options
            if (null !== $proxy_user) {
                $proxy_password = $this->loader->getOption('ProxyPassword');

                $proxy_auth = $this->loader->getOption('ProxyAuth');
                if (null !== $proxy_auth) {
                    $allowedAuth = [StreamCreator::PROXY_AUTH_BASIC, StreamCreator::PROXY_AUTH_NTLM];

                    if (!in_array($proxy_auth, $allowedAuth)) {
                        throw new Exception(
                            'Invalid/unsupported value "' . $proxy_auth . '" for option "ProxyAuth".',
                            Exception::INVALID_OPTION
                        );
                    }
                } else {
                    $proxy_auth = StreamCreator::PROXY_AUTH_BASIC;
                }

                if (StreamCreator::PROXY_AUTH_NTLM === $proxy_auth) {
                    curl_setopt($this->resource, CURLOPT_PROXYAUTH, CURLAUTH_NTLM);
                }
                curl_setopt($this->resource, CURLOPT_PROXYUSERPWD, $proxy_user . ':' . $proxy_password);
            }
        }
    }

    /**
     * closes an open stream
     */
    private function close(): void
    {
        curl_close($this->resource);
    }
}
