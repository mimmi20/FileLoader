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

/** @var \FileLoader\Loader the main loader class */

/** @var \FileLoader\Exception */
use FileLoader\Exception;
use FileLoader\Loader;
use Psr\Log\LoggerInterface;

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
class Local
{
    /**
     * The path of the local version of the browscap.ini file from which to
     * update (to be set only if used).
     *
     * @var string
     */
    private $localFile = null;

    /**
     * an logger instance
     *
     * @var LoggerInterface
     */
    private $logger = null;

    /**
     * sets the logger
     *
     * @param LoggerInterface $logger
     *
     * @return \FileLoader\Loader\Local
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;

        return $this;
    }

    /**
     * sets the name of the local file
     *
     * @param string $filename the file name
     *
     * @throws \FileLoader\Exception
     * @return \FileLoader\Loader\Local
     */
    public function setLocaleFile($filename)
    {
        if (empty($filename)) {
            throw new Exception(
                'the filename can not be empty',
                Exception::LOCAL_FILE_MISSING
            );
        }

        $this->localFile = $filename;

        return $this;
    }

    /**
     * XXX save
     *
     * loads the ini file from a remote or local location and stores it into
     * the cache dir, parses the ini file
     *
     * @throws \FileLoader\Exception
     * @return string the content of the local ini file
     */
    public function load()
    {
        // Get file content
        $file = file_get_contents($this->localFile);

        if ($file !== false) {
            return $file;
        }

        throw new Exception('Cannot open the local file');
    }

    /**
     * returns the uri, used for download
     *
     * @return string
     */
    public function getUri()
    {
        return $this->localFile;
    }

    /**
     * Gets the local ini file update timestamp
     *
     * @throws Exception
     * @return int the local modification timestamp
     */
    public function getMTime()
    {
        if (!is_readable($this->localFile) || !is_file($this->localFile)) {
            throw new Exception(
                'Local file is not readable',
                Exception::LOCAL_FILE_NOT_READABLE
            );
        }

        return filemtime($this->localFile);
    }
}
