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
    private $loader;

    /**
     * a HTTP Helper instance
     *
     * @var \FileLoader\Helper\StreamCreator
     */
    private $streamHelper;

    /**
     * a file handle created by fsockopen
     *
     * @var resource
     */
    private $stream;

    /**
     * holds the parts of the target url
     *
     * @var array
     */
    private $urlParts = [];

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
     *
     * @return \Psr\Http\Message\ResponseInterface
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
     * @return \Psr\Http\Message\ResponseInterface
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
     * @return \Psr\Http\Message\ResponseInterface
     */
    private function getRemoteData(string $url): ResponseInterface
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
        stream_set_blocking($this->stream, true);

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

            if ('http/' === mb_substr(mb_strtolower($header), 0, 5)) {
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
    private function getPort(): int
    {
        if (isset($this->urlParts['port'])) {
            return (int) $this->urlParts['port'];
        }

        if (isset($this->urlParts['scheme']) && 'https' === $this->urlParts['scheme']) {
            return 443;
        }

        return 80;
    }

    /**
     * checks if the end of the stream is reached
     *
     * @return bool
     */
    private function isValid(): bool
    {
        return !feof($this->stream);
    }

    /**
     * reads one line from the stream
     *
     * @return string
     */
    private function getLine(): string
    {
        $result = stream_get_line($this->stream, 65535, "\n");

        if (false === $result) {
            return '';
        }

        return $result;
    }

    /**
     * closes an open stream
     *
     * @return void
     */
    private function close(): void
    {
        fclose($this->stream);
    }
}
