<?php

/*
 * This file is part of the GeoPHP package.
 * Copyright (c) 2011 - 2016 Patrick Hayes and contributors
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace GeoPHP\Geometry;

use GeoPHP\Exception\UnsupportedException;
use GeoPHP\Geo;

/**
 * Geometry abstract class.
 */
abstract class Geometry
{
    /**
     * Point.
     */
    const TYPE_POINT = 'Point';

    /**
     * Line string.
     */
    const TYPE_LINE_STRING = 'LineString';

    /**
     * Polygon.
     */
    const TYPE_POLYGON = 'Polygon';

    /**
     * Multi point.
     */
    const TYPE_MULTI_POINT = 'MultiPoint';

    /**
     * Multi line string.
     */
    const TYPE_MULTI_LINE_STRING = 'MultiLineString';

    /**
     * Multi polygon.
     */
    const TYPE_MULTI_POLYGON = 'MultiPolygon';

    /**
     * Geometry collection.
     */
    const TYPE_GEOMETRY_COLLECTION = 'GeometryCollection';

    /**
     * Geos reader.
     *
     * @var null|false|\GEOSWKBReader
     */
    private $geos = null;

    /**
     * SRID.
     *
     * @var null|int
     */
    protected $srid = null;

    /**
     * Geometry type.
     *
     * @var null
     */
    protected $geomType = null;

    // Abtract: Standard
    // -----------------
    abstract public function area();

    abstract public function boundary();

    abstract public function centroid();

    abstract public function length();

    abstract public function y();

    abstract public function x();

    abstract public function numGeometries();

    abstract public function geometryN($n);

    abstract public function startPoint();

    abstract public function endPoint();

    abstract public function isRing();            // Mssing dependancy

    abstract public function isClosed();          // Missing dependancy

    abstract public function numPoints();

    abstract public function pointN($n);

    abstract public function exteriorRing();

    abstract public function numInteriorRings();

    abstract public function interiorRingN($n);

    abstract public function dimension();

    abstract public function equals($geom);

    /**
     * @return bool
     */
    abstract public function isEmpty();

    abstract public function isSimple();

    // Abtract: Non-Standard
    // ---------------------
    abstract public function getBBox();

    abstract public function asArray();

    abstract public function getPoints();

    abstract public function explode();

    abstract public function greatCircleLength(); //meters

    abstract public function haversineLength(); //degrees

    // Public: Standard -- Common to all geometries
    // --------------------------------------------
    /**
     * @SuppressWarnings(PHPMD.CamelCaseMethodName)
     *
     * @deprecated
     */
    public function SRID()
    {
        trigger_error('The usage of ' . __METHOD__ . '() has been deprecated, use getSRID() instead', E_USER_DEPRECATED);

        return $this->getSRID();
    }

    public function setSRID($srid)
    {
        if ($this->geos()) {
            $this->geos()->setSRID($srid);
        }
        $this->srid = $srid;
    }

    public function envelope()
    {
        if ($this->isEmpty()) {
            return new Polygon();
        }

        if (Geo::geosInstalled() && $this->geos()) {
            return Geo::geosToGeometry($this->geos()->envelope());
        }

        $bbox = $this->getBBox();
        $points = array(
            new Point($bbox['maxx'], $bbox['miny']),
            new Point($bbox['maxx'], $bbox['maxy']),
            new Point($bbox['minx'], $bbox['maxy']),
            new Point($bbox['minx'], $bbox['miny']),
            new Point($bbox['maxx'], $bbox['miny']),
        );

        $outerBoundary = new LineString($points);

        return new Polygon(array($outerBoundary));
    }

    /**
     * Returns the geometry type.
     *
     * @return string
     */
    public function geometryType()
    {
        return $this->geomType;
    }

    // Public: Non-Standard -- Common to all geometries
    // ------------------------------------------------

    /**
     * Output.
     *
     * @param string|null $format
     * @param null|mixed $otherArgs
     *
     * @return string
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function out($format, $otherArgs = null)
    {
        $args = func_get_args();

        $format = array_shift($args);
        $typeMap = Geo::getAdapterMap();
        $processorType = $typeMap[$format];
        $processor = new $processorType();

        array_unshift($args, $this);
        $result = call_user_func_array(array($processor, 'write'), $args);

        return $result;
    }

    // Public: Aliases
    // ---------------
    public function getCentroid()
    {
        return $this->centroid();
    }

    public function getArea()
    {
        return $this->area();
    }

    public function getX()
    {
        return $this->x();
    }

    public function getY()
    {
        return $this->y();
    }

    public function getGeos()
    {
        return $this->geos();
    }

    public function getGeomType()
    {
        return $this->geometryType();
    }

    public function getSRID()
    {
        return $this->srid;
    }

    public function asText()
    {
        return $this->out('wkt');
    }

    public function asBinary()
    {
        return $this->out('wkb');
    }

    /**
     * Returns the GEOS reader object or false when GEOS is not installed.
     *
     * @return false|\GEOSGeometry
     */
    public function geos()
    {
        // If it's already been set, just return it
        if (null !== $this->geos) {
            return $this->geos;
        }

        // It hasn't been set yet, generate it
        if (Geo::geosInstalled()) {
            $reader = new \GEOSWKBReader();
            $this->geos = $reader->readHEX($this->out('wkb', true));
        } else {
            $this->geos = false;
        }

        return $this->geos;
    }

    /**
     * Set the GEOS.
     *
     * @param false|\GEOSWKBReader $geos
     *
     * @return true
     */
    public function setGeos($geos)
    {
        $this->geos = $geos;

        return true;
    }

    public function pointOnSurface()
    {
        if ($this->geos()) {
            return Geo::geosToGeometry($this->geos()->pointOnSurface());
        }

        throw UnsupportedException::methodNotSupported(__METHOD__);
    }

    public function equalsExact(Geometry $geometry)
    {
        if ($this->geos()) {
            return $this->geos()->equalsExact($geometry->geos());
        }

        throw UnsupportedException::methodNotSupported(__METHOD__);
    }

    public function relate(Geometry $geometry, $pattern = null)
    {
        if ($this->geos()) {
            if ($pattern) {
                return $this->geos()->relate($geometry->geos(), $pattern);
            } else {
                return $this->geos()->relate($geometry->geos());
            }
        }

        throw UnsupportedException::methodNotSupported(__METHOD__);
    }

    public function checkValidity()
    {
        if ($this->geos()) {
            return $this->geos()->checkValidity();
        }

        throw UnsupportedException::methodNotSupported(__METHOD__);
    }

    public function buffer($distance)
    {
        if ($this->geos()) {
            return Geo::geosToGeometry($this->geos()->buffer($distance));
        }

        throw UnsupportedException::methodNotSupported(__METHOD__);
    }

    public function intersection(Geometry $geometry)
    {
        if ($this->geos()) {
            return Geo::geosToGeometry($this->geos()->intersection($geometry->geos()));
        }

        throw UnsupportedException::methodNotSupported(__METHOD__);
    }

    public function convexHull()
    {
        if ($this->geos()) {
            return Geo::geosToGeometry($this->geos()->convexHull());
        }

        throw UnsupportedException::methodNotSupported(__METHOD__);
    }

    public function difference(Geometry $geometry)
    {
        if ($this->geos()) {
            return Geo::geosToGeometry($this->geos()->difference($geometry->geos()));
        }

        throw UnsupportedException::methodNotSupported(__METHOD__);
    }

    public function symDifference(Geometry $geometry)
    {
        if ($this->geos()) {
            return Geo::geosToGeometry($this->geos()->symDifference($geometry->geos()));
        }

        throw UnsupportedException::methodNotSupported(__METHOD__);
    }

    // Can pass in a geometry or an array of geometries
    public function union(Geometry $geometry)
    {
        if ($this->geos()) {
            if (is_array($geometry)) {
                $geom = $this->geos();
                foreach ($geometry as $item) {
                    $geom = $geom->union($item->geos());
                }

                return Geo::geosToGeometry($geom);
            } else {
                return Geo::geosToGeometry($this->geos()->union($geometry->geos()));
            }
        }

        throw UnsupportedException::methodNotSupported(__METHOD__);
    }

    public function simplify($tolerance, $preserveTopology = false)
    {
        if ($this->geos()) {
            return Geo::geosToGeometry($this->geos()->simplify($tolerance, $preserveTopology));
        }

        throw UnsupportedException::methodNotSupported(__METHOD__);
    }

    public function disjoint(Geometry $geometry)
    {
        if ($this->geos()) {
            return $this->geos()->disjoint($geometry->geos());
        }

        throw UnsupportedException::methodNotSupported(__METHOD__);
    }

    public function touches(Geometry $geometry)
    {
        if ($this->geos()) {
            return $this->geos()->touches($geometry->geos());
        }

        throw UnsupportedException::methodNotSupported(__METHOD__);
    }

    public function intersects(Geometry $geometry)
    {
        if ($this->geos()) {
            return $this->geos()->intersects($geometry->geos());
        }

        throw UnsupportedException::methodNotSupported(__METHOD__);
    }

    public function crosses(Geometry $geometry)
    {
        if ($this->geos()) {
            return $this->geos()->crosses($geometry->geos());
        }

        throw UnsupportedException::methodNotSupported(__METHOD__);
    }

    public function within(Geometry $geometry)
    {
        if ($this->geos()) {
            return $this->geos()->within($geometry->geos());
        }

        throw UnsupportedException::methodNotSupported(__METHOD__);
    }

    public function contains(Geometry $geometry)
    {
        if ($this->geos()) {
            return $this->geos()->contains($geometry->geos());
        }

        throw UnsupportedException::methodNotSupported(__METHOD__);
    }

    public function overlaps(Geometry $geometry)
    {
        if ($this->geos()) {
            return $this->geos()->overlaps($geometry->geos());
        }

        throw UnsupportedException::methodNotSupported(__METHOD__);
    }

    public function covers(Geometry $geometry)
    {
        if ($this->geos()) {
            return $this->geos()->covers($geometry->geos());
        }

        throw UnsupportedException::methodNotSupported(__METHOD__);
    }

    public function coveredBy(Geometry $geometry)
    {
        if ($this->geos()) {
            return $this->geos()->coveredBy($geometry->geos());
        }

        throw UnsupportedException::methodNotSupported(__METHOD__);
    }

    public function distance(Geometry $geometry)
    {
        if ($this->geos()) {
            return $this->geos()->distance($geometry->geos());
        }

        throw UnsupportedException::methodNotSupported(__METHOD__);
    }

    public function hausdorffDistance(Geometry $geometry)
    {
        if ($this->geos()) {
            return $this->geos()->hausdorffDistance($geometry->geos());
        }

        throw UnsupportedException::methodNotSupported(__METHOD__);
    }

    public function project(Geometry $point, $normalized = null)
    {
        if ($this->geos()) {
            return $this->geos()->project($point->geos(), $normalized);
        }

        throw UnsupportedException::methodNotSupported(__METHOD__);
    }

    // Public - Placeholders
    // ---------------------
    public function hasZ()
    {
        // geoPHP does not support Z values at the moment
        return false;
    }

    public function is3D()
    {
        // geoPHP does not support 3D geometries at the moment
        return false;
    }

    public function isMeasured()
    {
        // geoPHP does not yet support M values
        return false;
    }

    public function coordinateDimension()
    {
        // geoPHP only supports 2-dimensional space
        return 2;
    }

    public function z()
    {
        // geoPHP only supports 2-dimensional space
        return;
    }

    public function m()
    {
        // geoPHP only supports 2-dimensional space
        return;
    }

    public function __toString()
    {
        return $this->asText();
    }
}
