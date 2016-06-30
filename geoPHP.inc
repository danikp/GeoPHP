<?php

/*
 * This file is part of the GeoPHP package.
 *
 * Copyright (c) 2011 - 2014 Patrick Hayes and contributors
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

// This file is here only for BC purpose
require_once __DIR__ . '/lib/autoload.php';

require_once __DIR__ . '/lib/GeoPHP/Geo.php';
require_once __DIR__ . '/lib/GeoPHP/Geometry/Geometry.php';
require_once __DIR__ . '/lib/GeoPHP/Geometry/Point.php';
require_once __DIR__ . '/lib/GeoPHP/Geometry/Collection.php';
require_once __DIR__ . '/lib/GeoPHP/Geometry/LineString.php';
require_once __DIR__ . '/lib/GeoPHP/Geometry/MultiPoint.php';
require_once __DIR__ . '/lib/GeoPHP/Geometry/Polygon.php';
require_once __DIR__ . '/lib/GeoPHP/Geometry/MultiPolygon.php';
require_once __DIR__ . '/lib/GeoPHP/Geometry/MultiLineString.php';
require_once __DIR__ . '/lib/GeoPHP/Geometry/GeometryCollection.php';
require_once __DIR__ . '/lib/GeoPHP/Adapter/Adapter.php';
require_once __DIR__ . '/lib/GeoPHP/Adapter/JSON.php';
require_once __DIR__ . '/lib/GeoPHP/Adapter/WKT.php';
require_once __DIR__ . '/lib/GeoPHP/Adapter/EWKT.php';
require_once __DIR__ . '/lib/GeoPHP/Adapter/WKB.php';
require_once __DIR__ . '/lib/GeoPHP/Adapter/EWKB.php';
require_once __DIR__ . '/lib/GeoPHP/Adapter/GPX.php';
require_once __DIR__ . '/lib/GeoPHP/Adapter/GeoRSS.php';
require_once __DIR__ . '/lib/GeoPHP/Adapter/GoogleGeocode.php';
require_once __DIR__ . '/lib/GeoPHP/Adapter/GeoHash.php';

// let the autoloader setup the aliases
class_exists('\Geometry');
class_exists('\Point');
class_exists('\Collection');
class_exists('\LineString');
class_exists('\MultiPoint');
class_exists('\Polygon');
class_exists('\MultiPolygon');
class_exists('\MultiLineString');
class_exists('\GeometryCollection');

class_exists('\GeoAdapter');
class_exists('\EWKT');
class_exists('\WKT');
class_exists('\WKB');
class_exists('\EWKB');
class_exists('\GPX');
class_exists('\GeoRSS');
class_exists('\GoogleGeocode');
class_exists('\GeoHash');
