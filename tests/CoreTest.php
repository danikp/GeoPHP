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
use GeoPHP\Geometry\Geometry;

class CoreTest extends BaseTest
{
    public function testGeometries()
    {
        foreach (glob($this->fixturesDir . '/*.*') as $file) {
            $parts = explode('.', $file);
            $format = end($parts);
            $value = file_get_contents($file);
            $this->log('Testing ' . basename($file));
            $geometry = Geo::load($value, $format);
            $this->checkAdapters($geometry, $format, $file);
            $this->checkMethods($geometry);
            $this->checkGeometry($geometry);
            $this->checkDetection($value, $format, $file);
        }
    }

    private function checkAdapters($geometry, $format, $file)
    {
        $skipAdapters = $this->remoteAdapters;

        // Test adapter output and input. Do a round-trip and re-test
        foreach (Geo::getAdapterMap() as $adapter_key => $adapter_class) {
            if (in_array($adapter_key, $skipAdapters)) {
                $this->log("Skipping $adapter_class");
                continue;
            }

            try {
                $output = $geometry->out($adapter_key);
                if ($output) {
                    $adapter_loader = new $adapter_class();
                    $test_geom_1 = $adapter_loader->read($output);
                    $test_geom_2 = $adapter_loader->read($test_geom_1->out($adapter_key));
                    $out1 = $test_geom_1->out('wkt');
                    $out2 = $test_geom_2->out('wkt');

                    $this->assertEquals($out1, $out2, "Output is not the same for $file (adapter: $adapter_class)");
                } else {
                    // fail if the geometry is not empty
                    if (!$geometry->isEmpty() && isset($this->allowedEmptyOutput[$adapter_key][basename($file)])) {
                        $this->fail('Empty output on ' . $adapter_key . ', file ' . $file);
                    }
                }
            } catch (UnsupportedException $e) {
            }
        }

        // Test to make sure adapter work the same whether GEOS is ON or OFF
        // Cannot test methods if GEOS is not installed
        if (!Geo::geosInstalled()) {
            return;
        }

        foreach (Geo::getAdapterMap() as $adapter_key => $adapter_class) {
            if (in_array($adapter_key, $skipAdapters)) {
                $this->log("Skipping $adapter_class, remote test is not enabled");
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
                    $this->assertEquals($test_geom_1->out('wkt'), $test_geom_2->out('wkt'), "Native and GEOS wkt output is not the same for $file (adapter: $adapter_class)");
                } else {
                    // fail if the geometry is not empty
                    if (!$geometry->isEmpty() && isset($this->allowedEmptyOutput[$adapter_key][basename($file)])) {
                        $this->fail('Empty output on ' . $adapter_key . ', file ' . $file);
                    }
                }
            } catch (UnsupportedException $e) {
            }
        }
    }

    private function checkMethods($geometry)
    {
        // Cannot test methods if GEOS is not intstalled
        if (!Geo::geosInstalled()) {
            return;
        }

        $methods = array(
            //'boundary', //@@TODO: Uncomment this and fix errors
            'envelope',   //@@TODO: Testing reveales errors in this method -- POINT vs. POLYGON
            'getBBox',
            'x',
            'y',
            'startPoint',
            'endPoint',
            'isRing',
            'isClosed',
            'numPoints',
        );

        foreach ($methods as $method) {
            // Turn GEOS on
            Geo::geosInstalled(true);
            $geos_result = $geometry->$method();

            // Turn GEOS off
            Geo::geosInstalled(false);
            $norm_result = $geometry->$method();

            // Turn GEOS back On
            Geo::geosInstalled(true);

            $geosType = gettype($geos_result);
            $normType = gettype($norm_result);

            if ($geosType != $normType) {
                $this->fail(sprintf('Type mismatch on "%s()", GEOS: %s, NORM: %s', $method,
                    var_export($geos_result, true),
                    var_export($norm_result, true)
                ));
            }

            // Now check base on type
            if ($geosType == 'object') {
                $haus_dist = $geos_result->hausdorffDistance(Geo::load($norm_result->out('wkt'), 'wkt'));

                // Get the length of the diagonal of the bbox - this is used to scale the haustorff distance
                // Using Pythagorean theorem
                $bb = $geos_result->getBBox();
                $scale = sqrt((($bb['maxy'] - $bb['miny']) ^ 2) + (($bb['maxx'] - $bb['minx']) ^ 2));

                // The difference in the output of GEOS and native-PHP methods should be less than 0.5 scaled haustorff units
                if ($haus_dist / $scale > 0.5) {
                    $this->fail('Output mismatch on ' . $method . ":\n" .
                        'GEOS: ' . $geos_result->out('wkt') . "\n" . 'NORM: ' . $norm_result->out('wkt'));
                }
            }

            if ($geosType == 'boolean' || $geosType == 'string') {
                if ($geos_result !== $norm_result) {
                    $this->fail('Output mismatch on ' . $method .
                        'GEOS: ' . (string)$geos_result . "\n" .
                        'NORM: ' . (string)$norm_result
                    );
                    continue;
                }
            }

            //@@TODO: Run tests for output of types arrays and float
            //@@TODO: centroid function is non-compliant for collections and strings
        }
    }

    private function checkGeometry(Geometry $geometry)
    {
        // Test common functions
        $geometry->area();
        $geometry->boundary();
        $geometry->envelope();
        $geometry->getBBox();
        $geometry->centroid();
        $geometry->length();
        $geometry->greatCircleLength();
        $geometry->haversineLength();
        $geometry->y();
        $geometry->x();
        $geometry->numGeometries();
        $geometry->geometryN(1);
        $geometry->startPoint();
        $geometry->endPoint();
        $geometry->isRing();
        $geometry->isClosed();
        $geometry->numPoints();
        $geometry->pointN(1);
        $geometry->exteriorRing();
        $geometry->numInteriorRings();
        $geometry->interiorRingN(1);
        $geometry->dimension();
        $geometry->geometryType();
        $geometry->getSRID();
        $geometry->setSRID(4326);

        // Aliases
        $geometry->getCentroid();
        $geometry->getArea();
        $geometry->getX();
        $geometry->getY();
        $geometry->getGeos();
        $geometry->getGeomType();
        $geometry->getSRID();
        $geometry->asText();
        $geometry->asBinary();

        // GEOS only functions
        $geometry->geos();
        $geometry->setGeos($geometry->geos());
        $geometry->pointOnSurface();
        $geometry->equals($geometry);
        $geometry->equalsExact($geometry);
        $geometry->relate($geometry);
        $geometry->checkValidity();
        $geometry->isSimple();
        $geometry->buffer(10);
        $geometry->intersection($geometry);
        $geometry->convexHull();
        $geometry->difference($geometry);
        $geometry->symDifference($geometry);
        $geometry->union($geometry);
        $geometry->simplify(0);// @@TODO: Adjust this once we can deal with empty geometries
        $geometry->disjoint($geometry);
        $geometry->touches($geometry);
        $geometry->intersects($geometry);
        $geometry->crosses($geometry);
        $geometry->within($geometry);
        $geometry->contains($geometry);
        $geometry->overlaps($geometry);
        $geometry->covers($geometry);
        $geometry->coveredBy($geometry);
        $geometry->distance($geometry);
        $geometry->hausdorffDistance($geometry);

        // Place holders
        $geometry->hasZ();
        $geometry->is3D();
        $geometry->isMeasured();
        $geometry->isEmpty();
        $geometry->coordinateDimension();
        $geometry->z();
        $geometry->m();
    }

    private function checkDetection($value, $format, $file)
    {
        $detected = Geo::detectFormat($value);
        if ($detected != $format) {
            $this->fail(
                $detected ? ('detected as ' . $detected) : "format $format not detected for $file");
        }
        // Make sure it loads using auto-detect
        Geo::load($value);
    }
}
