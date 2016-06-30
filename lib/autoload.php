<?php

/*
 * This file is part of the GeoPHP package.
 * Copyright (c) 2011 - 2016 Patrick Hayes and contributors
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

spl_autoload_register(

    function ($class) {
        $aliases = array(
            'geoPHP' => 'GeoPHP\Geo',
            'GeoPHP' => 'GeoPHP\Geo',
            'GeoAdapter' => 'GeoPHP\Adapter\Adapter',
            'EWKT' => 'GeoPHP\Adapter\EWKT',
            'WKT' => 'GeoPHP\Adapter\WKT',
            'WKB' => 'GeoPHP\Adapter\WKB',
            'EWKB' => 'GeoPHP\Adapter\EWKB',
            'GPX' => 'GeoPHP\Adapter\GPX',
            'GeoRSS' => 'GeoPHP\Adapter\GeoRSS',
            'GoogleGeocode' => 'GeoPHP\Adapter\GoogleGeocode',
            'Geohash' => 'GeoPHP\Adapter\GeoHash',
            'GeoHash' => 'GeoPHP\Adapter\GeoHash',
            'Collection' => 'GeoPHP\Geometry\Collection',
            'GeometryCollection' => 'GeoPHP\Geometry\GeometryCollection',
            'Geometry' => 'GeoPHP\Geometry\Geometry',
            'LineString' => 'GeoPHP\Geometry\LineString',
            'MultiLineString' => 'GeoPHP\Geometry\MultiLineString',
            'Point' => 'GeoPHP\Geometry\Point',
            'MultiPoint' => 'GeoPHP\Geometry\MultiPoint',
            'Polygon' => 'GeoPHP\Geometry\Polygon',
            'MultiPolygon' => 'GeoPHP\Geometry\MultiPolygon',

            // internal
            'GeoPHP\Exception\Parse' => 'GeoPHP\Exception\ParseException',
            'GeoPHP\Exception\Unsupported' => 'GeoPHP\Exception\UnsupportedException',
        );

        if (!isset($aliases[$class])) {
            return false;
        }

        // trigger error
        trigger_error(sprintf('Usage of %s is deprecated, please use %s instead', $class, $aliases[$class]), E_USER_DEPRECATED);

        return class_alias($aliases[$class], $class);
    }
);
