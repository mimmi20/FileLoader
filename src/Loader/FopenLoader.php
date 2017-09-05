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
use FileLoader\Psr7\Stream;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ResponseInterface;

/**
 * class to load a file from a remote source via fopen/file_get_contents
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
class FopenLoader implements LoaderInterface
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
     * @var \FileLoader\Helper\StreamCreator
     */
    private $streamHelper = null;

    /**
     * a file handle created by fopen
     *
     * @var resource
     */
    private $stream = null;

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
        $context      = $this->streamHelper->getStreamContext();
        $stream       = @fopen($url, 'rb', false, $context);

        if (false === $stream) {
            throw new Exception('could not initialize the connection to load the data');
        }

        $timeout = $this->loader->getTimeout();

        stream_set_timeout($stream, $timeout);
        stream_set_blocking($stream, true);

        $meta    = stream_get_meta_data($stream);
        $headers = [];
        $code    = 200;

        if (isset($meta['wrapper_data']) && is_array($meta['wrapper_data'])) {
            $headers = $meta['wrapper_data'];

            foreach ($headers as $metaData) {
                if ('http/' === mb_substr(mb_strtolower($metaData), 0, 5)) {
                    $tmp_status_parts = explode(' ', $metaData, 3);
                    $code             = $tmp_status_parts[1];
                    break;
                }
            }
        }

        return new Response($code, $headers, new Stream($stream));
    }
}
