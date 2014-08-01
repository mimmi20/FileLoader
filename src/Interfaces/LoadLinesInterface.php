<?php
/**
 * the interface for all supported connectors
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
 * @package    Interfaces
 * @copyright  2012-2014 Thomas Müller
 * @author     Thomas Müller <t_mueller_stolzenhain@yahoo.de>
 * @license    http://www.opensource.org/licenses/MIT MIT License
 * @link       https://github.com/mimmi20/FileLoader/
 */

namespace FileLoader\Interfaces;

use FileLoader\Loader;

/**
 * the interface for all supported connectors
 *
 * @package    Interfaces
 * @author     Thomas Müller <t_mueller_stolzenhain@yahoo.de>
 * @copyright  Copyright (c) 2012-2014 Thomas Müller
 * @version    1.2
 * @license    http://www.opensource.org/licenses/MIT MIT License
 * @link       https://github.com/mimmi20/FileLoader/
 */
interface LoadLinesInterface
{
    /**
     * return TRUE, if this connector is able to return a file line per line
     *
     * @return bool
     */
    public function isSupportingLoadingLines();

    /**
     * initialize the connection
     *
     * @param string $url the url of the data
     *
     * @return boolean
     */
    public function init($url);

    /**
     * checks if the end of the stream is reached
     *
     * @return boolean
     */
    public function isValid();

    /**
     * reads one line from the stream
     *
     * @return string
     */
    public function getLine();

    /**
     * closes an open stream
     */
    public function close();
}
