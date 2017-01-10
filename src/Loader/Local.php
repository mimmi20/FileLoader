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
 *
 * @copyright  2012-2014 Thomas Müller
 * @author     Thomas Müller <t_mueller_stolzenhain@yahoo.de>
 * @license    http://www.opensource.org/licenses/MIT MIT License
 *
 * @link       https://github.com/mimmi20/FileLoader/
 */

namespace FileLoader\Loader;

use FileLoader\Exception;
use FileLoader\Interfaces\LoaderInterface;
use FileLoader\Psr7\Stream;
use GuzzleHttp\Psr7\Response;

/**
 * class to load a file from a local source
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
class Local implements LoaderInterface
{
    /**
     * The path of the local version of the browscap.ini file from which to
     * update (to be set only if used).
     *
     * @var string
     */
    private $file = null;

    /**
     * sets the name of the local file
     *
     * @param string $filename the file name
     *
     * @throws \FileLoader\Exception
     * @return \FileLoader\Loader\Local
     */
    public function __construct($filename)
    {
        if (empty($filename)) {
            throw new Exception('the filename can not be empty', Exception::LOCAL_FILE_MISSING);
        }

        $this->file = $filename;
    }

    /**
     * loads the ini file from a remote or local location and stores it into
     * the cache dir, parses the ini file
     *
     * @throws \FileLoader\Exception
     * @return \GuzzleHttp\Psr7\Response
     */
    public function load()
    {
        if (false === strpos($this->file, '://')) {
            if (!is_file($this->file)) {
                throw new Exception('The given local file [' . $this->file . '] is not a file', Exception::LOCAL_FILE_NOT_READABLE);
            }

            if (!is_readable($this->file)) {
                throw new Exception('The given local file [' . $this->file . '] is not readable', Exception::LOCAL_FILE_NOT_READABLE);
            }
        }

        $stream = fopen($this->file, 'rb', false);

        if (false === $stream) {
            throw new Exception('could not read content from the given local file  [' . $this->file . ']', Exception::LOCAL_FILE_NOT_READABLE);
        }

        return new Response(200, [], new Stream($stream));
    }

    /**
     * Gets the local ini file update timestamp
     *
     * @throws \FileLoader\Exception
     * @return \GuzzleHttp\Psr7\Response
     */
    public function getMTime()
    {
        if (!is_file($this->file)) {
            throw new Exception('The given Local file is not a file', Exception::LOCAL_FILE_NOT_READABLE);
        }

        if (!is_readable($this->file)) {
            throw new Exception('The given Local file is not readable', Exception::LOCAL_FILE_NOT_READABLE);
        }

        return new Response(200, [], date('r', filemtime($this->file)));
    }
}
