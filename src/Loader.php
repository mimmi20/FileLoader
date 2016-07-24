<?php
/**
 * class to load a file from a local or remote source
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

namespace FileLoader;

use FileLoader\Helper\StreamCreator;
use FileLoader\Interfaces\LoaderInterface;
use FileLoader\Loader\Curl;
use FileLoader\Loader\FopenLoader;
use FileLoader\Loader\Local;
use FileLoader\Loader\SocketLoader;

/**
 * class to load a file from a local or remote source
 *
 * @author     Thomas Müller <t_mueller_stolzenhain@yahoo.de>
 * @copyright  Copyright (c) 2012-2014 Thomas Müller
 * @license    http://www.opensource.org/licenses/MIT MIT License
 * @link       https://github.com/mimmi20/FileLoader/
 */
class Loader implements LoaderInterface
{
    /**
     * The library version
     */
    const VERSION = '3.0.0';

    /**
     * The headers to be sent for checking the version and requesting the file.
     */
    const REQUEST_HEADERS = "GET %s HTTP/1.0\r\nHost: %s\r\nUser-Agent: %s\r\nConnection: Close\r\n\r\n";

    /**
     * The timeout for the requests.
     */
    private $timeout = 5;

    /**
     * The useragent to include in the requests made by the class during the
     * update process.
     *
     * @var string
     */
    private $userAgent = 'FileLoader/%v';

    /**
     * Options for the updater. The array should be overwritten,
     * containing all options as keys, set to the default value.
     *
     * @var array
     */
    private $options = [
        'ProxyProtocol' => null,
        'ProxyHost'     => null,
        'ProxyPort'     => null,
        'ProxyAuth'     => null,
        'ProxyUser'     => null,
        'ProxyPassword' => null,
    ];

    /**
     * The path of the local version of the browscap.ini file from which to
     * update (to be set only if used).
     *
     * @var string
     */
    private $localFile = null;

    /**
     * The Url where the remote file can be found
     *
     * @var string
     */
    private $remoteDataUrl = null;

    /**
     * The Url where the version of the remote file can be found
     *
     * @var string
     */
    private $remoteVersionUrl = null;

    /**
     * @var \FileLoader\Interfaces\LoaderInterface
     */
    private $loader = null;

    /**
     * @param array|\Traversable|null $options
     *
     * @throws \FileLoader\Exception
     */
    public function __construct($options = null)
    {
        if ($options !== null) {
            if (is_array($options) || $options instanceof \Traversable) {
                $this->setOptions($options);
            } else {
                throw new Exception('Invalid value for "options", array expected.', Exception::INVALID_OPTION);
            }
        }
    }

    /**
     * Sets multiple loader options at once
     *
     * @param array|\Traversable $options
     *
     * @return \FileLoader\Loader
     */
    public function setOptions($options)
    {
        foreach ($options as $optionKey => $optionValue) {
            $this->setOption($optionKey, $optionValue);
        }

        return $this;
    }

    /**
     * Sets an loader option value
     *
     * @param string $key
     * @param mixed  $value
     *
     * @throws \FileLoader\Exception
     * @return \FileLoader\Loader
     */
    public function setOption($key, $value)
    {
        if (array_key_exists($key, $this->options)) {
            $this->options[$key] = $value;

            return $this;
        }

        throw new Exception('Invalid option key "' . (string) $key . '".', Exception::INVALID_OPTION);
    }

    /**
     * Gets an loader option value
     *
     * @param string $key
     *
     * @throws \FileLoader\Exception
     * @return mixed
     */
    public function getOption($key)
    {
        if (array_key_exists($key, $this->options)) {
            return $this->options[$key];
        }

        throw new Exception('Invalid option key "' . (string) $key . '".', Exception::INVALID_OPTION);
    }

    /**
     * sets the name of the local file
     *
     * @param string $filename the file name
     *
     * @throws \FileLoader\Exception
     *
     * @return \FileLoader\Loader
     */
    public function setLocalFile($filename)
    {
        if (empty($filename)) {
            throw new Exception('the filename can not be empty', Exception::LOCAL_FILE_MISSING);
        }

        $this->localFile = $filename;
        $this->loader    = null;

        return $this;
    }

    /**
     * sets the remote location to get the remote file
     *
     * @param string $remoteDataUrl
     *
     * @throws \FileLoader\Exception
     * @return \FileLoader\Loader
     */
    public function setRemoteDataUrl($remoteDataUrl)
    {
        if (empty($remoteDataUrl)) {
            throw new Exception('the parameter ' . $remoteDataUrl . ' can not be empty', Exception::DATA_URL_MISSING);
        }

        $this->remoteDataUrl = $remoteDataUrl;

        return $this;
    }

    /**
     * returns the remote location to get the remote file
     *
     * @return string
     */
    public function getRemoteDataUrl()
    {
        return $this->remoteDataUrl;
    }

    /**
     * sets the remote location to get the remote file
     *
     * @param string $remoteVerUrl
     *
     * @throws \FileLoader\Exception
     *
     * @return \FileLoader\Loader
     */
    public function setRemoteVersionUrl($remoteVerUrl)
    {
        if (empty($remoteVerUrl)) {
            throw new Exception('the parameter ' . $remoteVerUrl . ' can not be empty', Exception::VERSION_URL_MISSING);
        }

        $this->remoteVersionUrl = $remoteVerUrl;

        return $this;
    }

    /**
     * returns the remote location to get the version of the remote file
     *
     * @return string
     */
    public function getRemoteVersionUrl()
    {
        return $this->remoteVersionUrl;
    }

    /**
     * returns the timeout
     *
     * @param int $timeout
     *
     * @return \FileLoader\Loader
     */
    public function setTimeout($timeout)
    {
        $this->timeout = (int) $timeout;

        return $this;
    }

    /**
     * returns the timeout
     *
     * @return int
     */
    public function getTimeout()
    {
        return $this->timeout;
    }

    /**
     * loads the file from a remote or local location and stores it into the cache
     *
     * @return \Psr\Http\Message\ResponseInterface the file content
     */
    public function load()
    {
        // Get file content
        return $this->getLoader()->load();
    }

    /**
     * loads the file from a remote or local location and stores it into the cache
     *
     * @return \Psr\Http\Message\ResponseInterface the file modification date or the remote version
     */
    public function getMTime()
    {
        // Get time of last modification
        return $this->getLoader()->getMTime();
    }

    /**
     * Format the useragent string to be used in the remote requests made by the
     * class during the update process.
     *
     * @return string the formatted user agent
     */
    public function getUserAgent()
    {
        return str_replace('%v', self::VERSION, $this->userAgent);
    }

    /**
     * return the actual used loader
     *
     * @return \FileLoader\Interfaces\LoaderInterface
     */
    private function getLoader()
    {
        if (null !== $this->loader) {
            return $this->loader;
        }

        if ($this->localFile !== null) {
            $this->loader = new Local($this->localFile);

            return $this->loader;
        }

        if (extension_loaded('curl')) {
            $this->loader = new Curl($this);

            return $this->loader;
        }

        $streamHelper = new StreamCreator($this);

        if (ini_get('allow_url_fopen')) {
            $this->loader = new FopenLoader($this, $streamHelper);

            return $this->loader;
        }

        $this->loader = new SocketLoader($this, $streamHelper);

        return $this->loader;
    }
}
