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

class PlaceholdersTests extends BaseTest
{
    public function testPlaceholders()
    {
        foreach (scandir($this->fixturesDir) as $file) {
            $parts = explode('.', $file);
            if ($parts[0]) {
                $format = $parts[1];
                $value = file_get_contents($this->fixturesDir . '/' . $file);
                $this->log('loading: ' . $file . ' for format: ' . $format);
                $geometry = Geo::load($value, $format);

                $placeholders = array(
                    array('name' => 'hasZ'),
                    array('name' => 'is3D'),
                    array('name' => 'isMeasured'),
                    array('name' => 'isEmpty'),
                    array('name' => 'coordinateDimension'),
                    array('name' => 'z'),
                    array('name' => 'm'),
                );

                foreach ($placeholders as $method) {
                    $argument = null;
                    $method_name = $method['name'];
                    if (isset($method['argument'])) {
                        $argument = $method['argument'];
                    }

                    switch ($method_name) {
                        case 'hasZ':
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
                        case 'm':
                        case 'z':
                        case 'coordinateDimension':
                        case 'isEmpty':
                        case 'isMeasured':
                        case 'is3D':
                    }
                }
            }
        }
    }
}
