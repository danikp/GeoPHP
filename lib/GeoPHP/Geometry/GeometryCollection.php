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
 * A heterogeneous collection of geometries.
 */
class GeometryCollection extends Collection
{
    protected $geomType = self::TYPE_GEOMETRY_COLLECTION;

    // We need to override asArray. Because geometryCollections are heterogeneous
    // we need to specify which type of geometries they contain. We need to do this
    // because, for example, there would be no way to tell the difference between a
    // MultiPoint or a LineString, since they share the same structure (collection
    // of points). So we need to call out the type explicitly.
    public function asArray()
    {
        $array = array();
        foreach ($this->components as $component) {
            $array[] = array(
                'type' => $component->geometryType(),
                'components' => $component->asArray(),
            );
        }

        return $array;
    }

    // Not valid for this geometry
    public function boundary()
    {
        return;
    }

    public function isSimple()
    {
        return;
    }
}
