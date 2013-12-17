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
use FileLoader\Loader;

/** @var \FileLoader\Exception */
use FileLoader\Exception;

/**
 * the loader class for requests via fsockopen
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
class SocketLoader extends RemoteLoader
{
    /**
     * Retrieve the data identified by the URL
     *
     * @param string $url the url of the data
     * @throws Exception
     * @return string|boolean the retrieved data
     */
    protected function getRemoteData($url)
    {
        $remoteUrl = parse_url($url);
        $errno     = 0;
        $errstr    = '';
        
        $remoteHandler = fsockopen($remoteUrl['host'], 80, $errno, $errstr, $this->loader->getTimeout());

        if (!$remoteHandler) {
            return false;
        }

        stream_set_timeout($remoteHandler, $this->loader->getTimeout());

        if (isset($remoteUrl['query'])) {
            $remoteUrl['path'] .= '?' . $remoteUrl['query'];
        }

        $out = sprintf(
            self::REQUEST_HEADERS,
            $remoteUrl['path'],
            $remoteUrl['host'],
            $this->loader->getUserAgent()()
        );

        fwrite($remoteHandler, $out);

        $response = fgets($remoteHandler);
        if (strpos($response, '200 OK') !== false) {
            $file = '';
            while (!feof($remoteHandler)) {
                $file .= fgets($remoteHandler);
            }

            $file = str_replace("\r\n", "\n", $file);
            $file = explode("\n\n", $file);
            array_shift($file);

            $file = implode("\n\n", $file);
        }

        fclose($remoteHandler);

        if ($file !== false) {
            return $file;
        }

        return false;
    }
}
