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
 * MultiPoint: A collection Points.
 */
class MultiPoint extends Collection
{
    protected $geomType = self::TYPE_MULTI_POINT;

    public function numPoints()
    {
        return $this->numGeometries();
    }

    public function isSimple()
    {
        return true;
    }

    // Not valid for this geometry type
    // --------------------------------
    public function explode()
    {
        return;
    }
}
