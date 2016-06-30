<?php
/**
 * Used by PhpStorm to map class aliases and factory methods to classes for code completion,
 * source code analysis, etc.
 *
 * The code is not ever actually executed and it only needed during development when coding with PhpStorm.
 *
 * @see http://confluence.jetbrains.com/display/PhpStorm/PhpStorm+Advanced+Metadata
 * @see http://blog.jetbrains.com/webide/2013/04/phpstorm-6-0-1-eap-build-129-177/
 */

namespace PHPSTORM_META {

    /** @noinspection PhpUnusedLocalVariableInspection */ // just to have a green code below
    /** @noinspection PhpIllegalArrayKeyTypeInspection */
    $STATIC_METHOD_TYPES = [];

    /** @noinspection PhpUnusedLocalVariableInspection */
    /** @noinspection PhpIllegalArrayKeyTypeInspection */
    $STATIC_METHOD_TYPES = [];
}

// global namespace
namespace {

    class GEOSGeometry
    {
        public function __construct()
        {
        }

        public function __toString()
        {
        }

        public function project(GEOSGeometry $geometry, $normalized = null)
        {
        }

        public function interpolate()
        {
        }

        public function buffer(GEOSGeometry $geometry)
        {
        }

        public function offsetCurve()
        {
        }

        public function envelope()
        {
        }

        public function intersection(GEOSGeometry $geometry)
        {
        }

        public function convexHull()
        {
        }

        public function difference(GEOSGeometry $geometry)
        {
        }

        public function symDifference(GEOSGeometry $geometry)
        {
        }

        public function boundary()
        {
        }

        public function union(GEOSGeometry $geometry)
        {
        }

        public function pointOnSurface()
        {
        }

        /**
         * @return GEOSGeometry
         */
        public function centroid()
        {
        }

        public function relate(GEOSGeometry $geometry, $pattern = null)
        {
        }

        public function relateBoundaryNodeRule()
        {
        }

        public function simplify($tolerance, $preserveTopology)
        {
        }

        public function extractUniquePoints()
        {
        }

        public function disjoint(GEOSGeometry $geometry)
        {
        }

        public function touches(GEOSGeometry $geometry)
        {
        }

        public function intersects(GEOSGeometry $geometry)
        {
        }

        public function crosses()
        {
        }

        public function within(GEOSGeometry $geometry)
        {
        }

        public function contains(GEOSGeometry $geometry)
        {
        }

        public function overlaps(GEOSGeometry $geometry)
        {
        }

        public function covers(GEOSGeometry $geometry)
        {
        }

        public function coveredBy(GEOSGeometry $geometry)
        {
        }

        public function equals(GEOSGeometry $geometry)
        {
        }

        public function equalsExact(GEOSGeometry $geometry)
        {
        }

        public function isEmpty()
        {
        }

        public function checkValidity()
        {
        }

        public function isSimple()
        {
        }

        public function isRing()
        {
        }

        public function hasZ()
        {
        }

        public function isClosed()
        {
        }

        public function typeName()
        {
        }

        public function typeId()
        {
        }

        public function getSRID()
        {
        }

        public function setSRID($srid)
        {
        }

        public function numGeometries()
        {
        }

        public function geometryN()
        {
        }

        public function numInteriorRings()
        {
        }

        public function numPoints()
        {
        }

        public function getX()
        {
        }

        public function getY()
        {
        }

        public function interiorRingN()
        {
        }

        public function exteriorRing()
        {
        }

        public function numCoordinates()
        {
        }

        public function dimension()
        {
        }

        public function coordinateDimension()
        {
        }

        public function pointN()
        {
        }

        public function startPoint()
        {
        }

        public function endPoint()
        {
        }

        public function area()
        {
        }

        public function length()
        {
        }

        public function distance(GEOSGeometry $geometry)
        {
        }

        public function hausdorffDistance(GEOSGeometry $geometry)
        {
        }

        public function snapTo()
        {
        }

        public function node()
        {
        }

        public function delaunayTriangulation()
        {
        }
    }

    class GEOSWKTReader
    {
        public function __construct()
        {
        }

        public function read($string)
        {
        }
    }

    class GEOSWKBReader
    {
        public function __construct()
        {
        }

        /**
         * @return GEOSGeometry
         */
        public function readHEX($string)
        {
        }

    }

    class GEOSWKTWriter
    {
        public function __construct()
        {
        }

        public function setTrim($flag)
        {
        }

        public function setRoundingPrecision($percision)
        {
        }

        public function getRoundingPrecision()
        {
        }

        public function setOutputDimension()
        {
        }

        public function getOutputDimension()
        {
        }

        public function setOld3D()
        {
        }

        /**
         * @return string
         */
        public function write(GEOSGeometry $geometry)
        {
        }

    }

}
