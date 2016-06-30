<?php

/*
 * This file is part of the GeoPHP package.
 * Copyright (c) 2011 - 2016 Patrick Hayes and contributors
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace GeoPHP;

use GeoPHP\Geometry\Geometry;
use GeoPHP\Geometry\GeometryCollection;

/**
 * Geo.
 */
class Geo
{
    /**
     * Library version.
     */
    const VERSION = '1.3.0-dev';

    /**
     * Adapter map.
     *
     * @var array
     */
    private static $adapterMap = array(
        'wkt' => 'GeoPHP\Adapter\WKT',
        'ewkt' => 'GeoPHP\Adapter\EWKT',
        'wkb' => 'GeoPHP\Adapter\WKB',
        'ewkb' => 'GeoPHP\Adapter\EWKB',
        'json' => 'GeoPHP\Adapter\JSON',
        'geojson' => 'GeoPHP\Adapter\JSON',
        'kml' => 'GeoPHP\Adapter\KML',
        'gpx' => 'GeoPHP\Adapter\GPX',
        'georss' => 'GeoPHP\Adapter\GeoRSS',
        'google_geocode' => 'GeoPHP\Adapter\GoogleGeocode',
        'geohash' => 'GeoPHP\Adapter\GeoHash',
    );

    /**
     * Geometry map.
     *
     * @var array
     */
    private static $map = array(
        'point' => Geometry::TYPE_POINT,
        'linestring' => Geometry::TYPE_LINE_STRING,
        'polygon' => Geometry::TYPE_POLYGON,
        'multipoint' => Geometry::TYPE_MULTI_POINT,
        'multilinestring' => Geometry::TYPE_MULTI_LINE_STRING,
        'multipolygon' => Geometry::TYPE_MULTI_POLYGON,
        'geometrycollection' => Geometry::TYPE_GEOMETRY_COLLECTION,
    );

    /**
     * Returns the version.
     *
     * @return string
     */
    public static function version()
    {
        return self::VERSION;
    }

    /**
     * Loads data.
     *
     * @param mixed $data If an array, all passed in values will be combined into a single geometry
     * @param null $type
     *
     * @return bool|GeometryCollection|mixed
     *
     * @throws Exception
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public static function load($data, $type = null, $otherArgs = null)
    {
        $args = func_get_args();

        $data = array_shift($args);
        $type = array_shift($args);

        $typeMap = static::getAdapterMap();

        // Auto-detect type if needed
        if (!$type) {
            // If the user is trying to load a Geometry from a Geometry... Just pass it back
            if (is_object($data)) {
                if ($data instanceof Geometry) {
                    return $data;
                }
            }

            $detected = static::detectFormat($data);
            if (!$detected) {
                return false;
            }

            $format = explode(':', $detected);
            $type = array_shift($format);
            $args = $format;
        }

        $processorType = $typeMap[$type];

        if (!$processorType) {
            throw new Exception('Could not find an adapter of type ' . htmlentities($type));
        }

        $processor = new $processorType();

        // Data is not an array, just pass it normally
        if (!is_array($data)) {
            $result = call_user_func_array(array($processor, 'read'), array_merge(array($data), $args));
        } // Data is an array, combine all passed in items into a single geomtetry
        else {
            $geoms = array();
            foreach ($data as $item) {
                $geoms[] = call_user_func_array(array($processor, 'read'), array_merge(array($item), $args));
            }
            $result = static::geometryReduce($geoms);
        }

        return $result;
    }

    /**
     * Returns the adapter map.
     *
     * @return array
     */
    public static function getAdapterMap()
    {
        return static::$adapterMap;
    }

    /**
     * Returns the geometry list.
     *
     * @return array
     */
    public static function geometryList()
    {
        return static::$map;
    }

    /**
     * @param null $force
     *
     * @return bool|null
     */
    public static function geosInstalled($force = null)
    {
        static $geosInstalled = null;

        if ($force !== null) {
            $geosInstalled = $force;
        }

        if ($geosInstalled !== null) {
            return $geosInstalled;
        }

        $geosInstalled = class_exists('\GEOSGeometry');

        return $geosInstalled;
    }

    /**
     * @param $geos
     *
     * @return bool|GeometryCollection|mixed
     *
     * @throws Exception
     */
    public static function geosToGeometry($geos)
    {
        if (!static::geosInstalled()) {
            return;
        }

        // Prevent errors witgh GEOS, point and other empty geometries
        // cannot be represented in WKB
        if ($geos->isEmpty() && strpos((string) $geos, 'EMPTY') !== false) {
            $geometry = null;
            $type = trim(str_replace('EMPTY', '', (string) $geos));
            foreach (static::geometryList() as $geomType => $className) {
                if (stripos($type, $geomType) !== false) {
                    $class = 'GeoPHP\\Geometry\\' . $className;
                    $geometry = new $class();
                    break;
                }
            }

            if (!$geometry) {
                throw new Exception\ParseException(sprintf('Could not convert "%s" to geometry.', $geos));
            }
        } else {
            try {
                $wkbWriter = new \GEOSWKBWriter();
                $wkb = $wkbWriter->writeHEX($geos);
            } catch (\Exception $e) {
                throw new Exception\ParseException(sprintf('Could not convert "%s" to geometry.', $geos), 0, $e);
            }

            $geometry = static::load($wkb, 'wkb', true);
        }

        $geometry->setGeos($geos);

        return $geometry;
    }

    // Reduce a geometry, or an array of geometries, into their 'lowest' available common geometry.
    // For example a GeometryCollection of only points will become a MultiPoint
    // A multi-point containing a single point will return a point.
    // An array of geometries can be passed and they will be compiled into a single geometry
    public static function geometryReduce($geometry)
    {
        // If it's an array of one, then just parse the one
        if (is_array($geometry)) {
            if (empty($geometry)) {
                return false;
            }
            if (count($geometry) == 1) {
                return static::geometryReduce(array_shift($geometry));
            }
        }

        $passbacks = array();
        // If the geometry cannot even theoretically be reduced more, then pass it back
        if (is_object($geometry)) {
            $passbacks = array('Point', 'LineString', 'Polygon');
            if (in_array($geometry->geometryType(), $passbacks)) {
                return $geometry;
            }
        }

        // If it is a multi-geometry, check to see if it just has one member
        // If it does, then pass the member, if not, then just pass back the geometry
        if (is_object($geometry)) {
            if (in_array(get_class($geometry), $passbacks)) {
                $components = $geometry->getComponents();
                if (count($components) == 1) {
                    return $components[0];
                } else {
                    return $geometry;
                }
            }
        }

        // So now we either have an array of geometries, a GeometryCollection, or an array of GeometryCollections
        if (!is_array($geometry)) {
            $geometry = array($geometry);
        }

        $geometries = array();
        $geomTypes = array();

        $collections = array(
            'GeoPHP\\Geometry\\MultiPoint',
            'GeoPHP\\Geometry\\MultiLineString',
            'GeoPHP\\Geometry\\MultiPolygon',
            'GeoPHP\\Geometry\\GeometryCollection',
        );

        foreach ($geometry as $item) {
            if ($item) {
                if (in_array(get_class($item), $collections)) {
                    foreach ($item->getComponents() as $component) {
                        $geometries[] = $component;
                        $geomTypes[] = $component->geometryType();
                    }
                } else {
                    $geometries[] = $item;
                    $geomTypes[] = $item->geometryType();
                }
            }
        }

        $geomTypes = array_unique($geomTypes);

        if (empty($geomTypes)) {
            return false;
        }

        if (count($geomTypes) == 1) {
            if (count($geometries) == 1) {
                return $geometries[0];
            } else {
                $class = 'GeoPHP\Geometry\Multi' . $geomTypes[0];

                return new $class($geometries);
            }
        } else {
            return new GeometryCollection($geometries);
        }
    }

    // Detect a format given a value. This function is meant to be SPEEDY.
    // It could make a mistake in XML detection if you are mixing or using namespaces in weird ways (ie, KML inside an RSS feed)
    public static function detectFormat(&$input)
    {
        $mem = fopen('php://memory', 'r+');
        fwrite($mem, $input, 11); // Write 11 bytes - we can detect the vast majority of formats in the first 11 bytes
        fseek($mem, 0);

        $bytes = unpack('c*', fread($mem, 11));

        // If bytes is empty, then we were passed empty input
        if (empty($bytes)) {
            return false;
        }

        // First char is a tab, space or carriage-return. trim it and try again
        if ($bytes[1] == 9 || $bytes[1] == 10 || $bytes[1] == 32) {
            $ltinput = ltrim($input);

            return static::detectFormat($ltinput);
        }

        // Detect WKB or EWKB -- first byte is 1 (little endian indicator)
        if ($bytes[1] == 1) {
            // If SRID byte is true (1), it's EWKB
            if ($bytes[5]) {
                return 'ewkb';
            } else {
                return 'wkb';
            }
        }

        // Detect HEX encoded WKB or EWKB (PostGIS format) -- first byte is 48, second byte is 49 (hex '01' => first-byte = 1)
        if ($bytes[1] == 48 && $bytes[2] == 49) {
            // The shortest possible WKB string (LINESTRING EMPTY) is 18 hex-chars (9 encoded bytes) long
            // This differentiates it from a geohash, which is always shorter than 18 characters.
            if (strlen($input) >= 18) {
                //@@TODO: Differentiate between EWKB and WKB -- check hex-char 10 or 11 (SRID bool indicator at encoded byte 5)
                return 'ewkb:1';
            }
        }

        // Detect GeoJSON - first char starts with {
        if ($bytes[1] == 123) {
            return 'json';
        }

        // Detect EWKT - first char is S
        if ($bytes[1] == 83) {
            return 'ewkt';
        }

        // Detect WKT - first char starts with P (80), L (76), M (77), or G (71)
        $wktChars = array(80, 76, 77, 71);
        if (in_array($bytes[1], $wktChars)) {
            return 'wkt';
        }

        // Detect XML -- first char is <
        if ($bytes[1] == 60) {
            // grab the first 256 characters
            $string = substr($input, 0, 256);
            if (strpos($string, '<kml') !== false) {
                return 'kml';
            }
            if (strpos($string, '<coordinate') !== false) {
                return 'kml';
            }
            if (strpos($string, '<gpx') !== false) {
                return 'gpx';
            }
            if (strpos($string, '<georss') !== false) {
                return 'georss';
            }
            if (strpos($string, '<rss') !== false) {
                return 'georss';
            }
            if (strpos($string, '<feed') !== false) {
                return 'georss';
            }
            if (strpos($string, '<box') !== false) {
                return 'georss';
            }
            if (strpos($string, '<circle') !== false) {
                return 'georss';
            }
            if (strpos($string, '<line') !== false) {
                return 'georss';
            }
            if (strpos($string, '<polygon') !== false) {
                return 'georss';
            }
        }

        // We need an 8 byte string for geohash and unpacked WKB / WKT
        fseek($mem, 0);
        $string = trim(fread($mem, 8));

        // Detect geohash - geohash ONLY contains lowercase chars and numerics
        preg_match('/[a-z0-9]+/', $string, $matches);
        if ($matches[0] == $string) {
            return 'geohash';
        }

        // What do you get when you cross an elephant with a rhino?
        // http://youtu.be/RCBn5J83Poc
        return false;
    }
}
