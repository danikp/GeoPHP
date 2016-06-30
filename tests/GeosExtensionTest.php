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

class GeosExtensionTest extends BaseTest
{
    public function setUp()
    {
        parent::setUp();
        if (!Geo::geosInstalled()) {
            $this->markTestSkipped('GEOS extension is not installed.');

            return;
        }
    }

    public function testGeos()
    {
        foreach (glob($this->fixturesDir . '/*.*') as $file) {
            $parts = explode('.', $file);
            $format = end($parts);
            $value = file_get_contents($file);
            $basename = basename($file);

            $this->log('Testing ' . $basename . ' for format: ' . $format);

            $geometry = Geo::load($value, $format);

            $geosMethods = array(
                array('name' => 'geos'),
                array('name' => 'setGeos', 'argument' => $geometry->geos()),
                array('name' => 'pointOnSurface'),
                array('name' => 'equals', 'argument' => $geometry),
                array('name' => 'equalsExact', 'argument' => $geometry),
                array('name' => 'relate', 'argument' => $geometry),
                array('name' => 'checkValidity'),
                array('name' => 'isSimple'),
                array('name' => 'buffer', 'argument' => '10'),
                array('name' => 'intersection', 'argument' => $geometry),
                array('name' => 'convexHull'),
                array('name' => 'difference', 'argument' => $geometry),
                array('name' => 'symDifference', 'argument' => $geometry),
                array('name' => 'union', 'argument' => $geometry),
                array('name' => 'simplify', 'argument' => '0'),
                array('name' => 'disjoint', 'argument' => $geometry),
                array('name' => 'touches', 'argument' => $geometry),
                array('name' => 'intersects', 'argument' => $geometry),
                array('name' => 'crosses', 'argument' => $geometry),
                array('name' => 'within', 'argument' => $geometry),
                array('name' => 'contains', 'argument' => $geometry),
                array('name' => 'overlaps', 'argument' => $geometry),
                array('name' => 'covers', 'argument' => $geometry),
                array('name' => 'coveredBy', 'argument' => $geometry),
                array('name' => 'distance', 'argument' => $geometry),
                array('name' => 'hausdorffDistance', 'argument' => $geometry),
            );

            foreach ($geosMethods as $method) {
                $argument = null;
                $method_name = $method['name'];
                if (isset($method['argument'])) {
                    $argument = $method['argument'];
                }

                switch ($method_name) {
                    case 'isSimple':
                    case 'equals':
                    case 'geos':
                        if ($geometry->geometryType() == 'Point') {
                            $this->assertNotNull($geometry->$method_name($argument), 'Failed on ' . $method_name . ' (test file: ' . $file . ')');
                        }
                        if ($geometry->geometryType() == 'LineString') {
                            $this->assertNotNull($geometry->$method_name($argument), 'Failed on ' . $method_name . ' (test file: ' . $file . ')');
                        }
                        if ($geometry->geometryType() == 'MultiLineString') {
                            $this->assertNotNull($geometry->$method_name($argument), 'Failed on ' . $method_name . ' (test file: ' . $file . ')');
                        }
                        break;
                    default:

                        if ($geometry->geometryType() == 'Point') {
                            $this->assertNotNull($geometry->$method_name($argument), 'Failed on ' . $method_name . ' (test file: ' . $file . ')');
                        }
                        if ($geometry->geometryType() == 'LineString') {
                            $this->assertNotNull($geometry->$method_name($argument), 'Failed on ' . $method_name . ' (test file: ' . $file . ')');
                        }
                        if ($geometry->geometryType() == 'MultiLineString') {
                            $this->assertNotNull($geometry->$method_name($argument), 'Failed on ' . $method_name . ' (test file: ' . $file . ')');
                        }
                }
            }
        }
    }
}
