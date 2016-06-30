<?php

/*
 * This file is part of the GeoPHP package.
 * Copyright (c) 2011 - 2016 Patrick Hayes and contributors
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace GeoPHP\Adapter;

use GeoPHP\Geometry\Geometry;

/**
 * EWKT (Extended Well Known Text) Adapter.
 */
class EWKT extends WKT
{
    /**
     * Serialize geometries into an EWKT string.
     *
     * @param Geometry $geometry
     *
     * @return string The Extended-WKT string representation of the input geometries
     */
    public function write(Geometry $geometry)
    {
        if ($srid = $geometry->getSRID()) {
            return 'SRID=' . $srid . ';' . $geometry->out('wkt');
        } else {
            return $geometry->out('wkt');
        }
    }
}
