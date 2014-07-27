<?php
/**
 * a factory class to build the required loader and the needed helpers
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

namespace FileLoader\Loader;

use FileLoader\Interfaces\ConnectorInterface;
use FileLoader\Connector\Curl;
use FileLoader\Connector\FopenLoader;
use FileLoader\Connector\SocketLoader;
use FileLoader\Exception;
use FileLoader\Helper\Http;
use FileLoader\Helper\StreamCreator;
use FileLoader\Loader;

/**
 * a factory class to build the required loader and the needed helpers
 *
 * @package    Browscap
 * @author     Thomas Müller <t_mueller_stolzenhain@yahoo.de>
 * @copyright  Copyright (c) 2014 Thomas Müller
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
     * @param \FileLoader\Loader                               $loader
     * @param string|\FileLoader\Interfaces\ConnectorInterface $mode
     * @param string                                           $localFile
     *
     * @return \FileLoader\Interfaces\LoaderInterface|\FileLoader\Interfaces\LoadLinesInterface the loader to use
     * @throws \FileLoader\Exception
     */
    public static function build(Loader $loader, $mode = null, $localFile = null)
    {
        if ($localFile !== null
            && (null === $mode || Loader::UPDATE_LOCAL === $mode)
        ) {
            $connector = new Local($loader);
            $connector->setLocalFile($localFile);

            return $connector;
        }

        $httpHelper   = new Http();
        $streamHelper = new StreamCreator();
        $streamHelper->setLoader($loader);

        if ($mode instanceof ConnectorInterface) {
            $connector = $mode;
        } elseif (extension_loaded('curl')
            && (null === $mode || Loader::UPDATE_CURL === $mode)
        ) {
            $connector = new Curl();
            $connector->setHttpHelper($httpHelper);
        } elseif (ini_get('allow_url_fopen')
            && (null === $mode || Loader::UPDATE_FOPEN === $mode)
        ) {
            $connector = new FopenLoader();
            $connector
                ->setStreamHelper($streamHelper)
                ->setHttpHelper($httpHelper)
            ;
        } elseif (null === $mode || Loader::UPDATE_FSOCKOPEN === $mode) {
            $connector = new SocketLoader();
            $connector
                ->setStreamHelper($streamHelper)
                ->setHttpHelper($httpHelper)
            ;
        } else {
            throw new Exception('no valid connector found');
        }

        $connector->setLoader($loader);

        $internalLoader = new RemoteLoader();
        $internalLoader
            ->setLoader($loader)
            ->setHttpHelper($httpHelper)
            ->setStreamHelper($streamHelper)
            ->setConnector($connector)
        ;

        return $internalLoader;
    }
}
