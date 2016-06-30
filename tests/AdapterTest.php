<?php

/*
 * This file is part of the GeoPHP package.
 * Copyright (c) 2011 - 2016 Patrick Hayes and contributors
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace GeoPHP\Tests;

use GeoPHP\Exception\UnsupportedException;
use GeoPHP\Geo;

class AdapterTests extends BaseTest
{
    public function testAdapters()
    {
        $skipAdapters = $this->remoteAdapters;

        foreach (glob($this->fixturesDir . '/*.*') as $file) {
            $parts = explode('.', basename($file));
            $format = $parts[1];
            $input = file_get_contents($file);

            $this->log("Testing adapter for file '" . basename($file) . "' for format: '" . $format . "'");

            $geometry = Geo::load($input, $format);

            foreach (Geo::getAdapterMap() as $adapter_key => $adapter_class) {
                if (in_array($adapter_key, $skipAdapters)) {
                    continue;
                }

                try {
                    $output = $geometry->out($adapter_key);
                    $this->assertNotNull($output, sprintf('Output is not empty using adapter "%s"', $adapter_key));
                    if ($output) {
                        $adapter_loader = new $adapter_class();
                        $test_geom_1 = $adapter_loader->read($output);
                        $test_geom_2 = $adapter_loader->read($test_geom_1->out($adapter_key));
                        $this->assertEquals(
                            $test_geom_1->out('wkt'),
                            $test_geom_2->out('wkt'), 'Mismatched adapter output in ' . $adapter_class . ' (test file: ' . $file . ')');
                    }
                } catch (UnsupportedException $e) {
                    // fail if the geometry is not empty
                    if (!$geometry->isEmpty() && isset($this->allowedEmptyOutput[$adapter_key][basename($file)])) {
                        $this->fail('Empty output on ' . $adapter_key . ', file ' . $file);
                    }
                }
            }

            // Test to make sure adapter work the same wether GEOS is ON or OFF
            // Cannot test methods if GEOS is not intstalled
            if (!Geo::geosInstalled()) {
                $this->markTestSkipped('GEOS is not installed');

                return;
            }

            foreach (Geo::getAdapterMap() as $adapter_key => $adapter_class) {
                if (in_array($adapter_key, $skipAdapters)) {
                    continue;
                }

                // Turn GEOS on
                Geo::geosInstalled(true);

                try {
                    $output = $geometry->out($adapter_key);
                    if ($output) {
                        $adapter_loader = new $adapter_class();
                        $test_geom_1 = $adapter_loader->read($output);
                        // Turn GEOS off
                        Geo::geosInstalled(false);
                        $test_geom_2 = $adapter_loader->read($output);
                        // Turn GEOS back On
                        Geo::geosInstalled(true);
                        // Check to make sure a both are the same with geos and without
                        $this->assertEquals($test_geom_1->out('wkt'), $test_geom_2->out('wkt'), 'Mismatched adapter output between GEOS and NORM in ' . $adapter_class . ' (test file: ' . $file . ')');
                    }
                } catch (UnsupportedException $e) {
                    // fail if the geometry is not empty
                    if (!$geometry->isEmpty() && isset($this->allowedEmptyOutput[$adapter_key][basename($file)])) {
                        $this->fail('Empty output on ' . $adapter_key . ', file ' . $file);
                    }
                }
            }
        }
    }
}
