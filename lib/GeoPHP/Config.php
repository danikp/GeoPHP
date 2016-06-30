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
    public static $roundingPrecision = -1;

    /**
     * Use with sprintf.
     * Do not set this value directly, use setRoundingPrecision() instead.
     *
     * @var string
     */
    public static $roundingPrecisionFormat = '%.16f';

    /**
     * Do not set this value directly, use setTrimUnnecessaryDecimals() instead.
     *
     * @var bool
     */
    public static $trimUnnecessaryDecimals = false;

    /**
     * @param int $roundingPrecision
     *
     * @return bool Old value
     */
    public static function setRoundingPrecision($roundingPrecision)
    {
        $roundingPrecision = (int) $roundingPrecision;

        $oldValue = self::$roundingPrecision;

        if ($roundingPrecision < 0) {
            $roundingPrecision = -1;
            self::$roundingPrecisionFormat = '%.16f';
        } else {
            self::$roundingPrecisionFormat = '%.' . $roundingPrecision . 'f';
        }

        self::$roundingPrecision = $roundingPrecision;

        return $oldValue;
    }

    /**
     * Enables/disables trimming of unnecessary decimals.
     *
     * @param bool $trimLeadingZeros
     *
     * @return bool Old value
     */
    public static function setTrimUnnecessaryDecimals($trimLeadingZeros)
    {
        $oldValue = self::$trimUnnecessaryDecimals;

        self::$trimUnnecessaryDecimals = (bool) $trimLeadingZeros;

        return $oldValue;
    }

    /**
     * Restores back the default values.
     */
    public static function restoreDefaults()
    {
        static::setRoundingPrecision(-1);
        static::setTrimUnnecessaryDecimals(false);
    }

    private function __construct()
    {
    }
}
