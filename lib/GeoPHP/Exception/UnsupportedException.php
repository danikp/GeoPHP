<?php

/*
 * This file is part of the GeoPHP package.
 * Copyright (c) 2011 - 2016 Patrick Hayes and contributors
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace GeoPHP\Exception;

use GeoPHP\Exception;

/**
 * Unsupported exception.
 */
class UnsupportedException extends Exception
{
    public static function methodNotSupported($methodName)
    {
        return new self(sprintf('The method "%s()" is not supported. Use must install GEOS extension to use this method.', $methodName), self::CODE_METHOD_NOT_SUPPORTED);
    }
}
