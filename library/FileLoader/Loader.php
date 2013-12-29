<?php
namespace FileLoader;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use WurflCache\Adapter\AdapterInterface;
use WurflCache\Adapter\File;
use WurflCache\Adapter\NullStorage;

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
class Loader
{
    /**
     * The library version
     */
    const VERSION = '0.1.0';

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
     * An associative array of associative arrays in the format
     * `$arr['wrapper']['option'] = $value` passed to stream_context_create()
     * when building a stream resource.
     *
     * Proxy settings are stored in this variable.
     *
     * @see http://www.php.net/manual/en/function.stream-context-create.php
     *
     * @var array
     */
    private $streamContextOptions = array();

    /**
     * A valid context resource created with stream_context_create().
     *
     * @see http://www.php.net/manual/en/function.stream-context-create.php
     *
     * @var resource
     */
    private $streamContext = null;

    /**
     * a \WurflCache\Adapter\AdapterInterface object
     *
     * @var \WurflCache\Adapter\AdapterInterface
     */
    private $cache = null;

    /**
     * The path of the local version of the browscap.ini file from which to
     * update (to be set only if used).
     *
     * @var string
     */
    private $localFile = null;

    /**
     * the name of the cache entry where the loaded file is stored
     *
     * @var string
     */
    private $filename = null;

    /**
     * an logger instance
     *
     * @var LoggerInterface
     */
    private $logger = null;

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
     * @var string
     */
    private $mode = null;

    /**
     * Constructor class, checks for the existence of (and loads) the cache and
     * if needed updated the definitions
     *
     * @param string $cacheDir
     *
     * @throws Exception
     */
    public function __construct($cacheDir = null)
    {
        $this->logger = new NullLogger();
        $this->cache  = new NullStorage();

        if (null !== $cacheDir) {
            if (!is_string($cacheDir)) {
                throw new Exception(
                    'You have to provide a path to read/store the browscap cache file',
                    Exception::CACHE_DIR_MISSING
                );
            }

            $this->cache = new File(
                array(File::DIR => $cacheDir)
            );
        }
    }

    /**
     * sets the logger
     *
     * @param LoggerInterface $logger
     *
     * @return \FileLoader\Loader
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;

        return $this;
    }

    /**
     * sets the cache used to make the detection faster
     *
     * @param \WurflCache\Adapter\AdapterInterface $cache
     *
     * @return Loader
     */
    public function setCache(AdapterInterface $cache)
    {
        $this->cache = $cache;

        return $this;
    }

    /**
     * returns the cache used to make the detection faster
     *
     * @return \WurflCache\Adapter\AdapterInterface
     */
    public function getCache()
    {
        return $this->cache;
    }

    /**
     * sets the name of the local file
     *
     * @param string $filename the file name
     *
     * @throws Exception
     * @return \FileLoader\Loader
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
     * sets the name of the local ini file
     *
     * @param string $filename the file name
     *
     * @throws Exception
     * @return \FileLoader\Loader
     */
    public function setCacheFile($filename)
    {
        if (empty($filename)) {
            throw new Exception(
                'the filename can not be empty',
                Exception::INI_FILE_MISSING
            );
        }

        $this->filename = $filename;

        return $this;
    }

    /**
     * sets the remote location to get the remote file
     *
     * @param string $remoteDataUrl
     *
     * @throws Exception
     * @return \FileLoader\Loader
     */
    public function setRemoteDataUrl($remoteDataUrl)
    {
        if (empty($remoteDataUrl)) {
            throw new Exception(
                'the parameter $remoteDataUrl can not be empty',
                Exception::DATA_URL_MISSING
            );
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
     * @throws Exception
     * @return \FileLoader\Loader
     */
    public function setRemoteVerUrl($remoteVerUrl)
    {
        if (empty($remoteVerUrl)) {
            throw new Exception(
                'the parameter $remoteVerUrl can not be empty',
                Exception::VERSION_URL_MISSING
            );
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
        $this->timeout = (int)$timeout;

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
     * @param string $mode
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
     * Load (auto-set) proxy settings from environment variables.
     *
     * @return \FileLoader\Loader
     */
    public function autodetectProxySettings()
    {
        $wrappers = array('http', 'https', 'ftp');

        foreach ($wrappers as $wrapper) {
            $url = getenv($wrapper . '_proxy');

            if (!empty($url)) {
                $params = array_merge(
                    array(
                         'port' => null,
                         'user' => null,
                         'pass' => null,
                    ),
                    parse_url($url)
                );

                $this->addProxySettings($params['host'], $params['port'], $wrapper, $params['user'], $params['pass']);
            }
        }

        return $this;
    }

    /**
     * Add proxy settings to the stream context array.
     *
     * @param string $server   Proxy server/host
     * @param int    $port     Port
     * @param string $wrapper  Wrapper: "http", "https", "ftp", others...
     * @param string $username Username (when requiring authentication)
     * @param string $password Password (when requiring authentication)
     *
     * @return \FileLoader\Loader
     */
    public function addProxySettings($server, $port = 3128, $wrapper = 'http', $username = null, $password = null)
    {
        $settings = array(
            $wrapper => array(
                'proxy'           => sprintf('tcp://%s:%d', $server, $port),
                'request_fulluri' => true,
            )
        );

        // Proxy authentication (optional)
        if (isset($username) && isset($password)) {
            $settings[$wrapper]['header'] = 'Proxy-Authorization: Basic ' . base64_encode($username . ':' . $password);
        }

        // Add these new settings to the stream context options array
        $this->streamContextOptions = array_merge(
            $this->streamContextOptions,
            $settings
        );

        /* Return $this so we can chain addProxySettings() calls like this:
         * $browscap->
         *   addProxySettings('http')->
         *   addProxySettings('https')->
         *   addProxySettings('ftp');
         */
        return $this;
    }

    /**
     * Clear proxy settings from the stream context options array.
     *
     * @param string $wrapper Remove settings from this wrapper only
     *
     * @return array Wrappers cleared
     */
    public function clearProxySettings($wrapper = null)
    {
        $wrappers = isset($wrapper) ? array($wrapper) : array_keys($this->streamContextOptions);

        $clearedWrappers = array();
        $options         = array('proxy', 'request_fulluri', 'header');

        foreach ($wrappers as $wrapper) {
            // remove wrapper options related to proxy settings
            if (isset($this->streamContextOptions[$wrapper]['proxy'])) {
                foreach ($options as $option) {
                    unset($this->streamContextOptions[$wrapper][$option]);
                }

                // remove wrapper entry if there are no other options left
                if (empty($this->streamContextOptions[$wrapper])) {
                    unset($this->streamContextOptions[$wrapper]);
                }

                $clearedWrappers[] = $wrapper;
            }
        }

        return $clearedWrappers;
    }

    /**
     * Returns the array of stream context options.
     *
     * @return array
     */
    public function getStreamContextOptions()
    {
        return $this->streamContextOptions;
    }

    /**
     * loads the file from a remote or local location and stores it into the cache
     *
     * @return string the file content
     */
    public function load()
    {
        $success = null;
        $content = $this->cache->getItem($this->filename, $success);

        if (!$success) {
            $internalLoader = Loader\Factory::build($this, $this->mode, $this->localFile);
            $internalLoader->setLogger($this->logger);

            // Get file content
            $content = $internalLoader->load();

            $this->cache->setItem($this->filename, $content);
        }

        return $content;
    }

    /**
     * loads the file from a remote or local location and stores it into the cache
     *
     * @return string the file content
     */
    public function getMTime()
    {
        $success = null;
        $content = $this->cache->getItem($this->filename . '.version', $success);

        if (!$success) {
            $internalLoader = Loader\Factory::build($this, $this->mode, $this->localFile);
            $internalLoader->setLogger($this->logger);

            // Get file content
            $content = $internalLoader->getMTime();

            $this->cache->setItem($this->filename, $content);
        }

        return $content;
    }

    /**
     * Lazy getter for the stream context resource.
     *
     * @param bool $recreate
     *
     * @return resource
     */
    public function getStreamContext($recreate = false)
    {
        if (!isset($this->streamContext)
            || !is_resource($this->streamContext)
            || $recreate
        ) {
            $this->streamContext = stream_context_create($this->getStreamContextOptions());
        }

        return $this->streamContext;
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
}
