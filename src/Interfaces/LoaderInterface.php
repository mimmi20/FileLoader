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
namespace FileLoader\Interfaces;

use FileLoader\Exception;
use Psr\Http\Message\ResponseInterface;

interface LoaderInterface
{
    /**
     * loads the ini file from a remote or local location and stores it into
     * the cache dir, parses the ini file
     *
     * @throws \FileLoader\Exception
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function load(): ResponseInterface;

    /**
     * Gets the local ini file update timestamp
     *
     * @throws Exception
     *
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function getMTime(): ResponseInterface;
}
