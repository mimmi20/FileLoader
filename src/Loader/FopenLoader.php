<?php
/**
 * class to load a file from a remote source via fopen/file_get_contents
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
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function load()
    {
        return $this->getRemoteData($this->loader->getRemoteDataUrl());
    }

    /**
     * Gets the remote file update timestamp
     *
     * @throws \FileLoader\Exception
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function getMTime()
    {
        return $this->getRemoteData($this->loader->getRemoteVersionUrl());
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
        $context      = $this->streamHelper->getStreamContext();
        $this->stream = @fopen($url, 'rb', false, $context);

        if (false === $this->stream) {
            throw new Exception('could not initialize the connection to load the data');
        }

        $timeout = $this->loader->getTimeout();

        stream_set_timeout($this->stream, $timeout);
        stream_set_blocking($this->stream, 1);

        $meta    = stream_get_meta_data($this->stream);
        $headers = [];
        $code    = 200;

        if (isset($meta['wrapper_data']) && is_array($meta['wrapper_data'])) {
            $headers = $meta['wrapper_data'];

            foreach ($headers as $metaData) {
                if ('http/' === substr(strtolower($metaData), 0, 5)) {
                    $tmp_status_parts = explode(' ', $metaData, 3);
                    $code             = $tmp_status_parts[1];
                    break;
                }
            }
        }

        return new Response($code, $headers, $this->stream);
    }
}
