<?php

/*
 * This file is part of the GeoPHP package.
 * Copyright (c) 2011 - 2016 Patrick Hayes and contributors
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace GeoPHP\Tests;

use GeoPHP\Geo;

class BcTest extends BaseTest
{
    public function testClassAliases()
    {
        require_once __DIR__ . '/../geoPHP.inc';

        $this->assertTrue(class_exists('\geoPHP'));
        $this->assertTrue(class_exists('\GeoAdapter'));
        $this->assertTrue(class_exists('\Geohash'));
        $this->assertTrue(class_exists('\GeoHash'));
        $this->assertTrue(class_exists('\Geometry'));

        $geom = Geo::load('POINT EMPTY');
        $this->assertTrue($geom instanceof \Geometry);
        $this->assertTrue($geom instanceof \Point);

        $geom = Geo::load('POLYGON EMPTY');
        $this->assertTrue($geom instanceof \Geometry);
        $this->assertTrue($geom instanceof \Polygon);

        $geom = Geo::load(file_get_contents(__DIR__ . '/input/multipolygon.wkt'));
        $this->assertTrue($geom instanceof \Geometry);
        $this->assertTrue($geom instanceof \MultiPolygon);

        $geom = Geo::load(file_get_contents(__DIR__ . '/input/multilinestring.wkt'));
        $this->assertTrue($geom instanceof \Geometry);
        $this->assertTrue($geom instanceof \MultiLineString);
    }
}
