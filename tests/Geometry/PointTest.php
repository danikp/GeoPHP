<?php

/*
 * This file is part of the GeoPHP package.
 * Copyright (c) 2011 - 2016 Patrick Hayes and contributors
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace GeoPHP\Tests\Geometry;

use GeoPHP\Geometry\Point;
use GeoPHP\Tests\BaseTest;

class PointTest extends BaseTest
{
    /**
     * @dataProvider getDataForEqualsTest
     */
    public function testEquals(Point $p1, Point $p2, $expected)
    {
        $this->assertEquals($expected, $p1->equals($p2));
        $this->assertEquals($expected, $p2->equals($p1));
    }

    public function getDataForEqualsTest()
    {
        return array(
            array(new Point(14.1, 45.2), new Point(14.1, 45.2), true),
            array(new Point(14.100000001, 45.2), new Point(14.1000000002, 45.2), true),
            array(new Point(14.100000001, 45.2), new Point(14.2, 15.1111), false),
            array(new Point(14.100000001, 45.2), new Point(), false),
        );
    }
}
