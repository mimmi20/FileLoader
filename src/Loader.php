<?php
/**
 * This file is part of the FileLoader package.
 *
 * Copyright (c) 2012-2017, Thomas Mueller <mimmi20@live.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types = 1);
namespace FileLoader;

use FileLoader\Helper\StreamCreator;
use FileLoader\Interfaces\LoaderInterface;
use FileLoader\Loader\Curl;
use FileLoader\Loader\FopenLoader;
use FileLoader\Loader\Local;
use FileLoader\Loader\SocketLoader;
use Psr\Http\Message\ResponseInterface;

/**
 * class to load a file from a local or remote source
 *
 * @author     Thomas Müller <t_mueller_stolzenhain@yahoo.de>
 * @copyright  Copyright (c) 2012-2014 Thomas Müller
 * @license    http://www.opensource.org/licenses/MIT MIT License
 *
 * @link       https://github.com/mimmi20/FileLoader/
 */
class Loader implements LoaderInterface
{
    /**
     * The library version
     */
    public const VERSION = '3.0.0';

    /**
     * The headers to be sent for checking the version and requesting the file.
     */
    public const REQUEST_HEADERS = "GET %s HTTP/1.0\r\nHost: %s\r\nUser-Agent: %s\r\nConnection: Close\r\n\r\n";

    /**
     * The timeout for the requests.
     *
     * @var int
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
    private $localFile;

    /**
     * The Url where the remote file can be found
     *
     * @var string
     */
    private $remoteDataUrl;

    /**
     * The Url where the version of the remote file can be found
     *
     * @var string
     */
    private $remoteVersionUrl;

    /**
     * @var \FileLoader\Interfaces\LoaderInterface|null
     */
    private $loader;

    /**
     * @param iterable|null $options
     *
     * @throws \FileLoader\Exception
     */
    public function __construct(?iterable $options = null)
    {
        if (null !== $options) {
            $this->setOptions($options);
        }
    }

    /**
     * Sets multiple loader options at once
     *
     * @param iterable $options
     *
     * @return void
     */
    public function setOptions(iterable $options): void
    {
        foreach ($options as $optionKey => $optionValue) {
            $this->setOption($optionKey, $optionValue);
        }
    }

    /**
     * Sets an loader option value
     *
     * @param string $key
     * @param mixed  $value
     *
     * @throws \FileLoader\Exception
     *
     * @return void
     */
    public function setOption(string $key, $value): void
    {
        if (array_key_exists($key, $this->options)) {
            $this->options[$key] = $value;

            return;
        }

        throw new Exception('Invalid option key "' . $key . '".', Exception::INVALID_OPTION);
    }

    /**
     * Gets an loader option value
     *
     * @param string $key
     *
     * @throws \FileLoader\Exception
     *
     * @return mixed
     */
    public function getOption(string $key)
    {
        if (array_key_exists($key, $this->options)) {
            return $this->options[$key];
        }

        throw new Exception('Invalid option key "' . $key . '".', Exception::INVALID_OPTION);
    }

    /**
     * sets the name of the local file
     *
     * @param string $filename the file name
     *
     * @throws \FileLoader\Exception
     *
     * @return void
     */
    public function setLocalFile(string $filename): void
    {
        if (empty($filename)) {
            throw new Exception('the filename can not be empty', Exception::LOCAL_FILE_MISSING);
        }

        $this->localFile = $filename;
        $this->loader    = null;
    }

    /**
     * sets the remote location to get the remote file
     *
     * @param string $remoteDataUrl
     *
     * @throws \FileLoader\Exception
     *
     * @return void
     */
    public function setRemoteDataUrl(string $remoteDataUrl): void
    {
        if (empty($remoteDataUrl)) {
            throw new Exception('the parameter $remoteDataUrl can not be empty', Exception::DATA_URL_MISSING);
        }

        $this->remoteDataUrl = $remoteDataUrl;
    }

    /**
     * returns the remote location to get the remote file
     *
     * @return string
     */
    public function getRemoteDataUrl(): string
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
     * @return void
     */
    public function setRemoteVersionUrl(string $remoteVerUrl): void
    {
        if (empty($remoteVerUrl)) {
            throw new Exception('the parameter $remoteVerUrl can not be empty', Exception::VERSION_URL_MISSING);
        }

        $this->remoteVersionUrl = $remoteVerUrl;
    }

    /**
     * returns the remote location to get the version of the remote file
     *
     * @return string
     */
    public function getRemoteVersionUrl(): string
    {
        return $this->remoteVersionUrl;
    }

    /**
     * returns the timeout
     *
     * @param int $timeout
     *
     * @return void
     */
    public function setTimeout(int $timeout): void
    {
        $this->timeout = $timeout;
    }

    /**
     * returns the timeout
     *
     * @return int
     */
    public function getTimeout(): int
    {
        return $this->timeout;
    }

    /**
     * loads the file from a remote or local location and stores it into the cache
     *
     * @return \Psr\Http\Message\ResponseInterface the file content
     */
    public function load(): ResponseInterface
    {
        // Get file content
        return $this->getLoader()->load();
    }

    /**
     * loads the file from a remote or local location and stores it into the cache
     *
     * @return \Psr\Http\Message\ResponseInterface the file modification date or the remote version
     */
    public function getMTime(): ResponseInterface
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
    public function getUserAgent(): string
    {
        return str_replace('%v', self::VERSION, $this->userAgent);
    }

    /**
     * return the actual used loader
     *
     * @return \FileLoader\Interfaces\LoaderInterface
     */
    private function getLoader(): LoaderInterface
    {
        if (null !== $this->loader) {
            return $this->loader;
        }

        if (null !== $this->localFile) {
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
