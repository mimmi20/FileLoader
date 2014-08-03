<?php
/**
 * class to load a file from a local source
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
 * @package    Loader
 * @copyright  2012-2014 Thomas Müller
 * @author     Thomas Müller <t_mueller_stolzenhain@yahoo.de>
 * @license    http://www.opensource.org/licenses/MIT MIT License
 * @link       https://github.com/mimmi20/FileLoader/
 */

namespace FileLoader\Loader;

use FileLoader\Exception;
use FileLoader\Loader;
use FileLoader\Interfaces\LoaderInterface;
use FileLoader\Interfaces\LoadLinesInterface;

/**
 * class to load a file from a local source
 *
 * @package    Loader
 * @author     Thomas Müller <t_mueller_stolzenhain@yahoo.de>
 * @copyright  Copyright (c) 2012-2014 Thomas Müller
 * @version    1.2
 * @license    http://www.opensource.org/licenses/MIT MIT License
 * @link       https://github.com/mimmi20/FileLoader/
 */
class Local implements LoaderInterface, LoadLinesInterface
{
    /**
     * The path of the local version of the browscap.ini file from which to
     * update (to be set only if used).
     *
     * @var string
     */
    private $localFile = null;

    /**
     * a file handle created by fopen
     *
     * @var resource
     */
    private $stream = null;

    /**
     * sets the name of the local file
     *
     * @param string $filename the file name
     *
     * @throws \FileLoader\Exception
     * @return \FileLoader\Loader\Local
     */
    public function setLocalFile($filename)
    {
        if (empty($filename)) {
            throw new Exception('the filename can not be empty', Exception::LOCAL_FILE_MISSING);
        }

        $this->localFile = $filename;

        return $this;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return Loader::UPDATE_LOCAL;
    }

    /**
     * return TRUE, if this connector is able to return a file line per line
     *
     * @return bool
     */
    public function isSupportingLoadingLines()
    {
        return true;
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
        if (!is_readable($this->localFile)
            || !is_file($this->localFile)
        ) {
            throw new Exception('Local file is not readable', Exception::LOCAL_FILE_NOT_READABLE);
        }

        return file_get_contents($this->localFile);
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
            throw new Exception('Local file is not readable', Exception::LOCAL_FILE_NOT_READABLE);
        }

        return filemtime($this->localFile);
    }

    /**
     * initialize the connection
     *
     * @param string $url
     *
     * @return boolean
     */
    public function init($url)
    {
        $this->stream = fopen($url, 'rb', false);

        if (false === $this->stream) {
            return false;
        }

        return true;
    }

    /**
     * checks if the end of the stream is reached
     *
     * @return boolean
     */
    public function isValid()
    {
        return (!feof($this->stream));
    }

    /**
     * reads one line from the stream
     *
     * @return string
     */
    public function getLine()
    {
        return stream_get_line($this->stream, 1024, "\n");
    }

    /**
     * closes an open stream
     */
    public function close()
    {
        fclose($this->stream);
    }
}
