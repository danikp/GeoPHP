<?php

/*
 * This file is part of the GeoPHP package.
 * Copyright (c) 2011 - 2016 Patrick Hayes and contributors
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

require_once __DIR__ . '/../geoPHP.inc';

check_class_exists('geoPHP');
check_class_exists('Geometry');
check_class_exists('Point');
check_class_exists('Collection');
check_class_exists('LineString');
check_class_exists('MultiPoint');
check_class_exists('Polygon');
check_class_exists('MultiPolygon');
check_class_exists('MultiLineString');
check_class_exists('GeometryCollection');
check_class_exists('GeoAdapter');
check_class_exists('EWKT');
check_class_exists('WKT');
check_class_exists('WKB');
check_class_exists('EWKB');
check_class_exists('GPX');
check_class_exists('GeoRSS');
check_class_exists('GoogleGeocode');
check_class_exists('GeoHash');

function check_class_exists($class)
{
    if (!class_exists($class)) {
        echo "Class $class does not exist\n";
        exit(1);
    }
}

echo "OK\n";
exit(0);
