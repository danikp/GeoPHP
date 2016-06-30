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
    public function testPrecisionWKT($precision, $trim, $geometryWkt, $expected)
    {
        $installed = false;
        if (Geo::geosInstalled()) {
            $installed = true;
            Geo::geosInstalled(false);
        }

        Config::setRoundingPrecision($precision);
        Config::setTrimUnnecessaryDecimals($trim);

        // try native
        $geom = Geo::load($geometryWkt);
        $wkt = $geom->out('wkt');
        $this->assertEquals($expected, $wkt);

        if (!$installed) {
            Config::restoreDefaults();

            return;
        }

        Geo::geosInstalled(true);

        // try geos
        $geom = Geo::load($geometryWkt);
        $wkt = $geom->out('wkt');

        Config::restoreDefaults();

        $this->assertEquals($expected, $wkt);
    }

    public function getDataForPrecisionTest()
    {
        return array(
            array(-1, false, 'POINT (14.1 45.2)', 'POINT (14.0999999999999996 45.2000000000000028)'),
            array(-1, true, 'POINT (14.1 45.2)', 'POINT (14.1 45.2)'),

            array(5, false, 'POINT (14.1 45.2)', 'POINT (14.10000 45.20000)'),
            array(5, false, 'POINT(-117.1234567 33.1234567)', 'POINT (-117.12346 33.12346)'),

            array(-1, false, 'MULTILINESTRING((-71.160281 42.258729,-71.160837 42.259113,-71.161141 42.25932))', 'MULTILINESTRING ((-71.1602809999999977 42.2587290000000024, -71.1608370000000008 42.2591129999999993, -71.1611410000000006 42.2593200000000024))'),

            // trim
            array(-1, true, 'POINT (14.1 45.2)', 'POINT (14.1 45.2)'),

            array(5, true, 'POINT (14.1 45.2)', 'POINT (14.1 45.2)'),

            array(-1, true, 'MULTILINESTRING((-71.160281 42.258729,-71.160837 42.259113,-71.161141 42.25932))', 'MULTILINESTRING ((-71.160281 42.258729, -71.160837 42.259113, -71.161141 42.25932))'),
            array(5, true, 'MULTILINESTRING((-71.160281 42.258729,-71.160837 42.259113,-71.161141 42.25932))', 'MULTILINESTRING ((-71.16 42.259, -71.161 42.259, -71.161 42.259))'),
        );
    }
}
