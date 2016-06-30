<?php

/*
 * This file is part of the GeoPHP package.
 * Copyright (c) 2011 - 2016 Patrick Hayes and contributors
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace GeoPHP;

final class Config
{
    /**
     * Do not set this value directly, use setRoundingPrecision() instead.
     *
     * @var int
     */
    public static $roundingPrecision = 16;

    /**
     * Use with sprintf.
     * Do not set this value directly, use setRoundingPrecision() instead.
     *
     * @var string
     */
    public static $roundingPrecisionFormat = '%.16f';

    /**
     * @param int $roundingPrecision
     */
    public static function setRoundingPrecision($roundingPrecision)
    {
        self::$roundingPrecision = $roundingPrecision;
        self::$roundingPrecisionFormat = '%.' . $roundingPrecision . 'f';
    }

    /**
     * Restores back the default rounding precision.
     */
    public static function restoreDefaultRoundingPrecision()
    {
        static::setRoundingPrecision(16);
    }

    private function __construct()
    {
    }
}
