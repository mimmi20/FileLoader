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

/**
 * Browscap.ini parsing class exception
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
class Exception extends \Exception
{
    public const LOCAL_FILE_MISSING         = 100;
    public const NO_RESULT_CLASS_RETURNED   = 200;
    public const STRING_VALUE_EXPECTED      = 300;
    public const CACHE_DIR_MISSING          = 400;
    public const CACHE_DIR_INVALID          = 401;
    public const CACHE_DIR_NOT_READABLE     = 402;
    public const CACHE_DIR_NOT_WRITABLE     = 403;
    public const CACHE_INCOMPATIBLE         = 500;
    public const INVALID_DATETIME           = 600;
    public const LOCAL_FILE_NOT_READABLE    = 700;
    public const REMOTE_UPDATE_NOT_POSSIBLE = 800;
    public const INI_FILE_MISSING           = 900;
    public const VERSION_URL_MISSING        = 1000;
    public const DATA_URL_MISSING           = 1100;
    public const INVALID_OPTION             = 1200;
}
