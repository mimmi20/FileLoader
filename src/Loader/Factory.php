<?php
namespace FileLoader\Loader;

/** the main loader class */

/** @var \FileLoader\Exception */
use FileLoader\Exception;
use FileLoader\Loader;

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
     * @param Loader $loader
     * @param string $mode
     * @param string $localeFile
     *
     * @return RemoteLoader the loader to use
     * @throws \FileLoader\Exception
     */
    public static function build(Loader $loader, $mode = null, $localeFile = null)
    {
        if ($localeFile !== null
            && (null === $mode || Loader::UPDATE_LOCAL === $mode)
        ) {
            $internalLoader = new Local($loader);
            $internalLoader->setLocaleFile($localeFile);
        } elseif (null === $mode || Loader::UPDATE_FSOCKOPEN === $mode) {
            $internalLoader = new SocketLoader($loader);
        } elseif (ini_get('allow_url_fopen')
            && (null === $mode || Loader::UPDATE_FOPEN === $mode)
        ) {
            $internalLoader = new FopenLoader($loader);
        } elseif (extension_loaded('curl')
            && (null === $mode || Loader::UPDATE_CURL === $mode)
        ) {
            $internalLoader = new Curl($loader);
        } else {
            throw new Exception('no valid loader found');
        }

        $logger = $loader->getLogger();

        if (null !== $logger) {
            $internalLoader->setLogger($logger);
        }

        return $internalLoader;
    }
}