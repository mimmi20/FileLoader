<?php
namespace FileLoader\Loader;

use WurflCache\Adapter\AdapterInterface;

/** the main loader class */
use FileLoader\Loader;

/** @var \FileLoader\Exception */
use FileLoader\Exception;

/**
 * class to load a file from a local or remote source
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
class Factory
{
    /**
     * loads the ini file from a remote or local location and stores it into
     * the cache dir, parses the ini file
     *
     * @param \FileLoader\Loader $loader
     * @param string             $localeFile
     *
     * @return RemoteLoader the loader to use
     * @throws \FileLoader\Exception
     */
    public static function build(Loader $loader, $localeFile = null)
    {
        // Caches the result
        if ($localeFile !== null) {
            $internalLoader = new Local($loader);
            $internalLoader->setLocaleFile($localeFile);
        } elseif (ini_get('allow_url_fopen') && function_exists('file_get_contents')) {
            $internalLoader = new FopenLoader($loader);
        } elseif (function_exists('fsockopen')) {
            $internalLoader = new SocketLoader($loader);
        } elseif (extension_loaded('curl')) {
            $internalLoader = new Curl($loader);
        } else {
            throw new Exception('no valid loader found');
        }

        return $internalLoader;
    }
}
