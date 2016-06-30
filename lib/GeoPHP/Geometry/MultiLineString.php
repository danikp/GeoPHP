<?php

/*
 * This file is part of the GeoPHP package.
 * Copyright (c) 2011 - 2016 Patrick Hayes and contributors
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace GeoPHP\Geometry;

/**
 * MultiLineString.
 */
class MultiLineString extends Collection
{
    protected $geomType = self::TYPE_MULTI_LINE_STRING;

    // MultiLineString is closed if all it's components are closed
    public function isClosed()
    {
        foreach ($this->components as $line) {
            if (!$line->isClosed()) {
                return false;
            }
        }

        return true;
    }
}
