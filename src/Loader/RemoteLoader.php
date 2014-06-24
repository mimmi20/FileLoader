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
abstract class RemoteLoader
{
    /**
     * an Loader instance
     *
     * @var \FileLoader\Loader
     */
    protected $loader = null;

    /**
     * Constructor class, checks for the existence of (and loads) the cache and
     * if needed updated the definitions
     *
     * @param \FileLoader\Loader $loader
     */
    public function __construct(Loader $loader)
    {
        $this->loader = $loader;
    }

    /**
     * XXX save
     *
     * loads the ini file from a remote location
     *
     * @return string the file content
     * @throws Exception
     */
    public function load()
    {
        // Choose the right url
        $file = $this->getRemoteData($this->getUri());

        if ($file !== false) {
            return $file;
        }

        throw new Exception('Cannot load the remote file');
    }

    /**
     * returns the uri, used for download
     *
     * @return string
     */
    public function getUri()
    {
        return $this->loader->getRemoteDataUrl();
    }

    /**
     * Gets the remote file update timestamp
     *
     * @throws Exception
     * @return int|string the remote modification timestamp
     */
    public function getMTime()
    {
        $remoteDataUrl  = $this->loader->getRemoteVerUrl();
        $remoteDatetime = $this->getRemoteData($remoteDataUrl);

        if (!$remoteDatetime) {
            throw new Exception(
                'Bad datetime format from ' . $remoteDataUrl,
                Exception::INVALID_DATETIME
            );
        }

        return $remoteDatetime;
    }

    /**
     * Retrieve the data identified by the URL
     *
     * @param string $url the url of the data
     *
     * @throws Exception
     * @return string|boolean the retrieved data
     */
    abstract protected function getRemoteData($url);
}
