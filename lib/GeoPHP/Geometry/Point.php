<?php

/*
 * This file is part of the GeoPHP package.
 * Copyright (c) 2011 - 2016 Patrick Hayes and contributors
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace GeoPHP\Geometry;

use GeoPHP\Exception;

/**
 * The most basic geometry type. All other geometries are built out of Points.
 */
class Point extends Geometry
{
    /**
     * @var array
     */
    public $coords = array(2);

    /**
     * @var string
     */
    protected $geomType = self::TYPE_POINT;

    /**
     * @var int
     */
    protected $dimension = 2;

    /**
     * Constructor.
     *
     * @param float|string $x The x coordinate (or longitude)
     * @param float|string $y The y coordinate (or latitude)
     * @param float|string $z The z coordinate (or altitude) - optional
     *
     * @throws
     */
    public function __construct($x = null, $y = null, $z = null)
    {
        // Check if it's an empty point
        // POINT EMPTY is now stored as POINT(NaN NaN) in WKB, instead of as MULTIPOINT EMPTY
        // see: https://trac.osgeo.org/postgis/ticket/3181
        if ($x === null && $y === null || ($x === 'nan' && $y === 'nan')) {
            $this->coords = array(null, null);
            $this->dimension = 0;

            return;
        }

        // Basic validation on x and y
        if (!is_numeric($x) || !is_numeric($y)) {
            throw new Exception(sprintf('Cannot construct Point. x and y should be numeric. "%s","%s" given', is_object($x) ? get_class($x) : $x, is_object($y) ? get_class($y) : $y));
        }

        // Check to see if this is a 3D point
        if ($z !== null) {
            if (!is_numeric($z)) {
                throw new Exception('Cannot construct Point. z should be numeric');
            }
            $this->dimension = 3;
        }

        // Convert to floatval in case they are passed in as a string or integer etc.
        $x = floatval($x);
        $y = floatval($y);
        $z = floatval($z);

        // Add poitional elements
        if ($this->dimension == 2) {
            $this->coords = array($x, $y);
        }
        if ($this->dimension == 3) {
            $this->coords = array($x, $y, $z);
        }
    }

    /**
     * Get X (longitude) coordinate.
     *
     * @return float The X coordinate
     */
    public function x()
    {
        return $this->coords[0];
    }

    /**
     * Returns Y (latitude) coordinate.
     *
     * @return float The Y coordinate
     */
    public function y()
    {
        return $this->coords[1];
    }

    /**
     * Returns Z (altitude) coordinate.
     *
     * @return float The Z coordinate or null is not a 3D point
     */
    public function z()
    {
        if ($this->dimension == 3) {
            return $this->coords[2];
        } else {
            return;
        }
    }

    /**
     * A point's centroid is itself.
     *
     * @return $this
     */
    public function centroid()
    {
        return $this;
    }

    /**
     * Returns the BBox.
     *
     * @return array
     */
    public function getBBox()
    {
        return array(
            'maxy' => $this->getY(),
            'miny' => $this->getY(),
            'maxx' => $this->getX(),
            'minx' => $this->getX(),
        );
    }

    public function asArray($assoc = false)
    {
        return $this->coords;
    }

    public function area()
    {
        return 0;
    }

    public function length()
    {
        return 0;
    }

    public function greatCircleLength()
    {
        return 0;
    }

    public function haversineLength()
    {
        return 0;
    }

    // The boundary of a point is itself
    public function boundary()
    {
        return $this;
    }

    public function dimension()
    {
        return 0;
    }

    public function isEmpty()
    {
        if ($this->dimension == 0) {
            return true;
        } else {
            return false;
        }
    }

    public function numPoints()
    {
        return 1;
    }

    public function getPoints()
    {
        return array($this);
    }

    public function equals($geometry)
    {
        if (!($geometry instanceof self)) {
            return false;
        }

        if (!$this->isEmpty() && !$geometry->isEmpty()) {
            return abs($this->x() - $geometry->x()) <= 1.0E-9 && abs($this->y() - $geometry->y()) <= 1.0E-9;
        } elseif ($this->isEmpty() && $geometry->isEmpty()) {
            return true;
        } else {
            return false;
        }
    }

    public function isSimple()
    {
        return true;
    }

    // Not valid for this geometry type
    public function numGeometries()
    {
        return;
    }

    public function geometryN($n)
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

    public function pointOnSurface()
    {
        return $this;
    }

    public function simplify($tolerance, $preserveTopology = false)
    {
        return $this;
    }

    public function explode()
    {
        return;
    }
}
