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
 * @package    FileLoader
 * @copyright  2012-2014 Thomas Müller
 * @author     Thomas Müller <t_mueller_stolzenhain@yahoo.de>
 * @license    http://www.opensource.org/licenses/MIT MIT License
 * @link       https://github.com/mimmi20/FileLoader/
 */

namespace FileLoader;

use FileLoader\Interfaces\LoaderInterface;
use FileLoader\Interfaces\LoadLinesInterface;

/**
 * class to load a file from a local or remote source
 *
 * @package    FileLoader
 * @author     Thomas Müller <t_mueller_stolzenhain@yahoo.de>
 * @copyright  Copyright (c) 2012-2014 Thomas Müller
 * @version    1.2
 * @license    http://www.opensource.org/licenses/MIT MIT License
 * @link       https://github.com/mimmi20/FileLoader/
 */
class Loader implements LoaderInterface, LoadLinesInterface
{
    /**
     * The library version
     */
    const VERSION = '1.2.0';

    /**
     * Different ways to access remote and local files.
     *
     * UPDATE_FOPEN: Uses the fopen url wrapper (use file_get_contents).
     * UPDATE_FSOCKOPEN: Uses the socket functions (fsockopen).
     * UPDATE_CURL: Uses the cURL extension.
     * UPDATE_LOCAL: Updates from a local file (file_get_contents).
     */
    const UPDATE_FOPEN     = 'URL-wrapper';
    const UPDATE_FSOCKOPEN = 'socket';
    const UPDATE_CURL      = 'cURL';
    const UPDATE_LOCAL     = 'local';

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
    private $userAgent = 'File Loader/%v';

    /**
     * Options for the updater. The array should be overwritten,
     * containing all options as keys, set to the default value.
     *
     * @var array
     */
    private $options = array(
        'ProxyProtocol' => null,
        'ProxyHost'     => null,
        'ProxyPort'     => null,
        'ProxyAuth'     => null,
        'ProxyUser'     => null,
        'ProxyPassword' => null,
    );

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
    private $remoteVerUrl = null;

    /**
     * the mode what defines which way the remote file is loaded
     *
     * @var null|string|\FileLoader\Interfaces\ConnectorInterface
     */
    private $mode = null;

    /**
     * @param array|null $options
     *
     * @throws \FileLoader\Exception
     */
    public function __construct($options = null)
    {
        if ($options !== null) {
            if (is_array($options)) {
                $this->setOptions($options);
            } else {
                throw new Exception('Invalid value for "options", array expected.', Exception::INVALID_OPTION);
            }
        }
    }

    /**
     * Sets multiple loader options at once
     *
     * @param array $options
     *
     * @return \FileLoader\Loader
     */
    public function setOptions(array $options)
    {
        foreach ($options as $option_key => $option_value) {
            $this->setOption($option_key, $option_value);
        }
        return $this;
    }

    /**
     * Sets an loader option value
     *
     * @param string $key
     * @param mixed  $value
     *
     * @return \FileLoader\Loader
     * @throws \FileLoader\Exception
     */
    public function setOption($key, $value)
    {
        if (array_key_exists($key, $this->options)) {
            $this->options[$key] = $value;
        } else {
            throw new Exception('Invalid option key "' . (string) $key . '".', Exception::INVALID_OPTION);
        }
        return $this;
    }

    /**
     * Gets an loader option value
     *
     * @param string $key
     *
     * @return mixed|null
     */
    public function getOption($key)
    {
        if (array_key_exists($key, $this->options)) {
            return $this->options[$key];
        }

        return null;
    }

    /**
     * sets the name of the local file
     *
     * @param string $filename the file name
     *
     * @throws \FileLoader\Exception
     * @return \FileLoader\Loader
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
     * @return \FileLoader\Loader
     */
    public function setRemoteVerUrl($remoteVerUrl)
    {
        if (empty($remoteVerUrl)) {
            throw new Exception('the parameter ' . $remoteVerUrl . ' can not be empty', Exception::VERSION_URL_MISSING);
        }

        $this->remoteVerUrl = $remoteVerUrl;

        return $this;
    }

    /**
     * returns the remote location to get the version of the remote file
     *
     * @return string
     */
    public function getRemoteVerUrl()
    {
        return $this->remoteVerUrl;
    }

    /**
     * returns the timeout
     *
     * @param integer $timeout
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
     * @return integer
     */
    public function getTimeout()
    {
        return $this->timeout;
    }

    /**
     * sets the mode to load the remote file
     *
     * @param string|\FileLoader\Interfaces\ConnectorInterface $mode
     *
     * @return \FileLoader\Loader
     */
    public function setMode($mode = null)
    {
        if (empty($mode)) {
            return $this;
        }

        $this->mode = $mode;

        return $this;
    }

    /**
     * loads the file from a remote or local location and stores it into the cache
     *
     * @return string the file content
     */
    public function load()
    {
        // Get file content
        return $this->getLoader()->load();
    }

    /**
     * returns the uri, used for download
     *
     * @return string
     */
    public function getUri()
    {
        return $this->getLoader()->getUri();
    }

    /**
     * loads the file from a remote or local location and stores it into the cache
     *
     * @return string the file content
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
     * @return \FileLoader\Interfaces\LoaderInterface|\FileLoader\Interfaces\LoadLinesInterface
     */
    public function getLoader()
    {
        return Loader\Factory::build($this, $this->mode, $this->localFile);
    }
    /**
     * return TRUE, if this connector is able to return a file line per line
     *
     * @return bool
     */
    public function isSupportingLoadingLines()
    {
        return $this->getLoader()->isSupportingLoadingLines();
    }

    /**
     * initialize the connection
     *
     * @param string $url the url of the data
     *
     * @return boolean
     */
    public function init($url)
    {
        return $this->getLoader()->init();
    }

    /**
     * checks if the end of the stream is reached
     *
     * @return boolean
     */
    public function isValid()
    {
        return $this->getLoader()->isValid();
    }

    /**
     * reads one line from the stream
     *
     * @return string
     */
    public function getLine()
    {
        return $this->getLoader()->getLine();
    }

    /**
     * closes an open stream
     */
    public function close()
    {
        return $this->getLoader()->close();
    }
}
