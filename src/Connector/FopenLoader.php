<?php
/**
 * class to load a file from a remote source via fopen/file_get_contents
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

use FileLoader\Loader;
use FileLoader\Helper\Http;
use FileLoader\Helper\StreamCreator;

/**
 * class to load a file from a remote source via fopen/file_get_contents
 *
 * @package    Browscap
 * @author     Thomas Müller <t_mueller_stolzenhain@yahoo.de>
 * @copyright  Copyright (c) 2014 Thomas Müller
 * @version    1.0
 * @license    http://www.opensource.org/licenses/MIT MIT License
 * @link       https://github.com/mimmi20/FileLoader/
 */
class FopenLoader implements ConnectorInterface
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
     * a HTTP Helper instance
     *
     * @var \FileLoader\Helper\StreamCreator
     */
    private $streamHelper = null;

    /**
     * Constructor class, checks for the existence of (and loads) the cache and
     * if needed updated the definitions
     *
     * @param \FileLoader\Loader               $loader
     * @param \FileLoader\Helper\Http          $httpHelper
     * @param \FileLoader\Helper\StreamCreator $streamHelper
     */
    public function __construct(Loader $loader, Http $httpHelper, StreamCreator $streamHelper)
    {
        $this->loader       = $loader;
        $this->httpHelper   = $httpHelper;
        $this->streamHelper = $streamHelper;
    }

    /**
     * Retrieve the data identified by the URL
     *
     * @param string $url the url of the data
     *
     * @throws \RuntimeException
     * @return string|boolean the retrieved data
     */
    public function getRemoteData($url)
    {
        $context   = $this->streamHelper->getStreamContext();
        @$response = file_get_contents($url, false, $context);

        // $http_response_header is a predefined variables,
        // automatically created by PHP after the call above
        //
        // @see http://php.net/manual/en/reserved.variables.httpresponseheader.php
        if (isset($http_response_header)) {
            // extract status from first array entry, e.g. from 'HTTP/1.1 200 OK'
            if (is_array($http_response_header) && isset($http_response_header[0])) {
                $tmp_status_parts = explode(" ", $http_response_header[0], 3);
                $http_code = $tmp_status_parts[1];

                // check for HTTP error
                $http_exception = $this->httpHelper->getHttpErrorException($http_code);
                if ($http_exception !== null) {
                    throw $http_exception;
                }
            }
        }

        return $response;
    }
}
