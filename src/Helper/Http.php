<?php
/**
 * a helper class to handle http errors
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

namespace FileLoader\Helper;

use FileLoader\Loader;
use FileLoader\Exception;

/**
 * a helper class to handle http errors
 *
 * @package    Browscap
 * @author     Thomas Müller <t_mueller_stolzenhain@yahoo.de>
 * @copyright  Copyright (c) 2014 Thomas Müller
 * @version    1.0
 * @license    http://www.opensource.org/licenses/MIT MIT License
 * @link       https://github.com/mimmi20/FileLoader/
 */
class Http
{
    /**
     * Gets the exception to throw if the given HTTP status code is an error code (4xx or 5xx)
     *
     * @param int $http_code
     *
     * @return \FileLoader\Exception|null
     */
    public function getHttpErrorException($http_code)
    {
        $http_code = (int) $http_code;

        if ($http_code < 400) {
            return null;
        }

        $httpCodes = array(
            401 => "HTTP client error 401: Unauthorized",
            403 => "HTTP client error 403: Forbidden",
            404 => "HTTP client error 404: Not Found",
            429 => "HTTP client error 429: Too many request",
            500 => "HTTP server error 500: Internal Server Error",
        );

        if (isset($httpCodes[$http_code])) {
            return new Exception($httpCodes[$http_code], $http_code);
        } elseif ($http_code >= 500) {
            return new Exception("HTTP server error $http_code", $http_code);
        }

        return new Exception("HTTP client error $http_code", $http_code);
    }
}
