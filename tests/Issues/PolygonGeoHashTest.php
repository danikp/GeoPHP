<?php

/*
 * This file is part of the GeoPHP package.
 * Copyright (c) 2011 - 2016 Patrick Hayes and contributors
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace GeoPHP\Tests\Issues;

use GeoPHP\Adapter\GeoHash;
use GeoPHP\Geo;
use GeoPHP\Geometry\Geometry;
use GeoPHP\Tests\BaseTest;

class PolygonGeoHashTest extends BaseTest
{
    /**
     * @dataProvider getDataForIssueTest
     */
    public function testIssue(Geometry $geometry, $expected, $exception = null)
    {
        $adapter = new GeoHash();

        if ($exception) {
            $this->setExpectedException($exception);
        }

        $this->assertEquals($expected, $adapter->write($geometry, 0.00000000000001));

        $read = $adapter->read($expected, true);
        $this->assertEquals($geometry, $read);
    }

    public function getDataForIssueTest()
    {
        return array(
            // http://postgis.net/docs/ST_GeomFromGeoHash.html
            array(
                Geo::load(file_get_contents(__DIR__ . '/../input/big_n_ugly.kml')), null, 'GeoPHP\Exception\UnsupportedException',
                Geo::load('POLYGON((-115.172816 36.114646,-115.172816 36.114646,-115.172816 36.114646,-115.172816 36.114646,-115.172816 36.114646))', 'wkt'), '9qqj7nmxncgyy4d0dbxqz0',
            ),
        );
    }
}
