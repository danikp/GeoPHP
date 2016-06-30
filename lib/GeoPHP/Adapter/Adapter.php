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
use GeoPHP\Geometry\GeometryCollection;

/**
 * Abstract class which represents an adapter for reading and writing to and from Geometry objects.
 */
abstract class Adapter
{
    /**
     * Read input and return a Geometry or GeometryCollection.
     *
     * @return Geometry|GeometryCollection
     */
    abstract public function read($input);

    /**
     * Write out a Geometry or GeometryCollection in the adapter's format.
     *
     * @param Geometry
     *
     * @return mixed
     */
    abstract public function write(Geometry $geometry);
}
