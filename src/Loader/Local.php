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
namespace FileLoader\Loader;

use FileLoader\Exception;
use FileLoader\Interfaces\LoaderInterface;
use FileLoader\Psr7\Stream;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ResponseInterface;

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
    private $file;

    /**
     * sets the name of the local file
     *
     * @param string $filename the file name
     *
     * @throws \FileLoader\Exception
     *
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
     *
     * @return \GuzzleHttp\Psr7\Response
     */
    public function load(): ResponseInterface
    {
        if (false === mb_strpos($this->file, '://')) {
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
     *
     * @return \GuzzleHttp\Psr7\Response
     */
    public function getMTime(): ResponseInterface
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
