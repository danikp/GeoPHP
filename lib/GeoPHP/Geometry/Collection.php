<?php

/*
 * This file is part of the GeoPHP package.
 * Copyright (c) 2011 - 2016 Patrick Hayes and contributors
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace GeoPHP\Geometry;

use GeoPHP\Geo;

/**
 * Abstract class for compound geometries.
 *
 * A geometry is a collection if it is made up of other component geometries.
 * Therefore everything but a Point is a Collection. For example a LingString is a collection
 * of Points. A Polygon is a collection of LineStrings etc.
 */
abstract class Collection extends Geometry
{
    /**
     * @var array
     */
    public $components = array();

    /**
     * Constructor: Checks and sets component geometries.
     *
     * @param array $components array of geometries
     *
     * @throws
     */
    public function __construct($components = array())
    {
        if (!is_array($components)) {
            throw new \InvalidArgumentException('Component geometries must be passed as an array');
        }

        foreach ($components as $component) {
            if ($component instanceof Geometry) {
                $this->components[] = $component;
            } else {
                throw new \InvalidArgumentException('Cannot create a collection with non-geometries');
            }
        }
    }

    /**
     * Returns Collection component geometries.
     *
     * @return array
     */
    public function getComponents()
    {
        return $this->components;
    }

    public function centroid()
    {
        if ($this->isEmpty()) {
            return;
        }

        if ($this->geos()) {
            $geosCentroid = $this->geos()->centroid();
            if ($geosCentroid->typeName() == self::TYPE_POINT) {
                return Geo::geosToGeometry($this->geos()->centroid());
            }
        }

        // As a rough estimate, we say that the centroid of a colletion is the centroid of it's envelope
        // @@TODO: Make this the centroid of the convexHull
        // Note: Outside of polygons, geometryCollections and the trivial case of points, there is no standard on what a "centroid" is
        $centroid = $this->envelope()->centroid();

        return $centroid;
    }

    public function getBBox()
    {
        if ($this->isEmpty()) {
            return;
        }

        if ($this->geos()) {
            $envelope = $this->geos()->envelope();
            if ($envelope->typeName() == self::TYPE_POINT) {
                return Geo::geosToGeometry($envelope)->getBBOX();
            }

            $geosRing = $envelope->exteriorRing();

            return array(
                'maxy' => $geosRing->pointN(3)->getY(),
                'miny' => $geosRing->pointN(1)->getY(),
                'maxx' => $geosRing->pointN(1)->getX(),
                'minx' => $geosRing->pointN(3)->getX(),
            );
        }

        // Go through each component and get the max and min x and y
        $i = 0;
        foreach ($this->components as $component) {
            $componentBbox = $component->getBBox();

            // On the first run through, set the bbox to the component bbox
            if ($i == 0) {
                $maxx = $componentBbox['maxx'];
                $maxy = $componentBbox['maxy'];
                $minx = $componentBbox['minx'];
                $miny = $componentBbox['miny'];
            }

            // Do a check and replace on each boundary, slowly growing the bbox
            $maxx = $componentBbox['maxx'] > $maxx ? $componentBbox['maxx'] : $maxx;
            $maxy = $componentBbox['maxy'] > $maxy ? $componentBbox['maxy'] : $maxy;
            $minx = $componentBbox['minx'] < $minx ? $componentBbox['minx'] : $minx;
            $miny = $componentBbox['miny'] < $miny ? $componentBbox['miny'] : $miny;
            ++$i;
        }

        return array(
            'maxy' => $maxy,
            'miny' => $miny,
            'maxx' => $maxx,
            'minx' => $minx,
        );
    }

    public function asArray()
    {
        $array = array();
        foreach ($this->components as $component) {
            $array[] = $component->asArray();
        }

        return $array;
    }

    public function area()
    {
        if ($this->geos()) {
            return $this->geos()->area();
        }

        $area = 0;
        foreach ($this->components as $component) {
            $area += $component->area();
        }

        return $area;
    }

    // By default, the boundary of a collection is the boundary of it's components
    public function boundary()
    {
        if ($this->isEmpty()) {
            return new LineString();
        }

        if ($this->geos()) {
            return $this->geos()->boundary();
        }

        $componentsBoundaries = array();
        foreach ($this->components as $component) {
            $componentsBoundaries[] = $component->boundary();
        }

        return Geo::geometryReduce($componentsBoundaries);
    }

    public function numGeometries()
    {
        return count($this->components);
    }

    // Note that the standard is 1 based indexing
    public function geometryN($n)
    {
        $n = intval($n);
        if (array_key_exists($n - 1, $this->components)) {
            return $this->components[$n - 1];
        } else {
            return;
        }
    }

    public function length()
    {
        $length = 0;
        foreach ($this->components as $component) {
            $length += $component->length();
        }

        return $length;
    }

    public function greatCircleLength($radius = 6378137)
    {
        $length = 0;
        foreach ($this->components as $component) {
            $length += $component->greatCircleLength($radius);
        }

        return $length;
    }

    public function haversineLength()
    {
        $length = 0;
        foreach ($this->components as $component) {
            $length += $component->haversineLength();
        }

        return $length;
    }

    public function dimension()
    {
        $dimension = 0;
        foreach ($this->components as $component) {
            if ($component->dimension() > $dimension) {
                $dimension = $component->dimension();
            }
        }

        return $dimension;
    }

    // A collection is empty if it has no components OR all it's components are empty
    public function isEmpty()
    {
        if (!count($this->components)) {
            return true;
        } else {
            foreach ($this->components as $component) {
                if (!$component->isEmpty()) {
                    return false;
                }
            }

            return true;
        }
    }

    public function numPoints()
    {
        $num = 0;
        foreach ($this->components as $component) {
            $num += $component->numPoints();
        }

        return $num;
    }

    public function getPoints()
    {
        $points = array();
        foreach ($this->components as $component) {
            $points = array_merge($points, $component->getPoints());
        }

        return $points;
    }

    public function equals($geometry)
    {
        if ($this->geos()) {
            return $this->geos()->equals($geometry->geos());
        }

        // To test for equality we check to make sure that there is a matching point
        // in the other geometry for every point in this geometry.
        // This is slightly more strict than the standard, which
        // uses Within(A,B) = true and Within(B,A) = true
        // @@TODO: Eventually we could fix this by using some sort of simplification
        // method that strips redundant vertices (that are all in a row)

        $thisPoints = $this->getPoints();
        $otherPoints = $geometry->getPoints();

        // First do a check to make sure they have the same number of vertices
        if (count($thisPoints) != count($otherPoints)) {
            return false;
        }

        foreach ($thisPoints as $point) {
            $foundMatch = false;
            foreach ($otherPoints as $key => $testPoint) {
                if ($point->equals($testPoint)) {
                    $foundMatch = true;
                    unset($otherPoints[$key]);
                    break;
                }
            }
            if (!$foundMatch) {
                return false;
            }
        }

        // All points match, return true
        return true;
    }

    public function isSimple()
    {
        if ($this->geos()) {
            return $this->geos()->isSimple();
        }

        // A collection is simple if all it's components are simple
        foreach ($this->components as $component) {
            if (!$component->isSimple()) {
                return false;
            }
        }

        return true;
    }

    public function explode()
    {
        $parts = array();
        foreach ($this->components as $component) {
            $parts = array_merge($parts, $component->explode());
        }

        return $parts;
    }

    // Not valid for this geometry type
    // --------------------------------
    public function x()
    {
        return;
    }

    public function y()
    {
        return;
    }

    public function startPoint()
    {
        return;
    }

    public function endPoint()
    {
        return;
    }

    public function isRing()
    {
        return;
    }

    public function isClosed()
    {
        return;
    }

    public function pointN($n)
    {
        return;
    }

    public function exteriorRing()
    {
        return;
    }

    public function numInteriorRings()
    {
        return;
    }

    public function interiorRingN($n)
    {
        return;
    }
}
