<?php

/*
 * This file is part of the GeoPHP package.
 * Copyright (c) 2011 - 2016 Patrick Hayes and contributors
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace GeoPHP\Tests;

use GeoPHP\Config;
use GeoPHP\Geo;

class RoundingPrecisionTest extends BaseTest
{
    /**
     * @dataProvider getDataForPrecisionTest
     */
    public function testPrecisionWKT($precision, $geometryWkt, $expected)
    {
        $installed = false;
        if (Geo::geosInstalled()) {
            $installed = true;
            Geo::geosInstalled(false);
        }

        if ($precision !== null) {
            Config::setRoundingPrecision($precision);
        }

        // try native
        $geom = Geo::load($geometryWkt);
        $wkt = $geom->out('wkt');
        $this->assertEquals($expected, $wkt);

        if (!$installed) {
            return;
        }

        Geo::geosInstalled(true);

        // try geos
        $geom = Geo::load($geometryWkt);
        $wkt = $geom->out('wkt');
        $this->assertEquals($expected, $wkt);
    }

    public function getDataForPrecisionTest()
    {
        return array(
            array(null, 'POINT (14.1 45.2)', 'POINT (14.0999999999999996 45.2000000000000028)'),
            array(5, 'POINT (14.1 45.2)', 'POINT (14.10000 45.20000)'),
        );
    }
}
