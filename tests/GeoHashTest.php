<?php

/*
 * This file is part of the GeoPHP package.
 * Copyright (c) 2011 - 2016 Patrick Hayes and contributors
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace GeoPHP\Tests;

use GeoPHP\Adapter\GeoHash;

class GeoHashTest extends BaseTest
{
    /**
     * test cases for adjacent geohashes.
     */
    public function testAdjacent()
    {
        $geohash = new GeoHash();
        $this->assertEquals('xne', $geohash->adjacent('xn7', 'top'), 'Did not find correct top adjacent geohash for xn7');
        $this->assertEquals('xnk', $geohash->adjacent('xn7', 'right'), 'Did not find correct right adjacent geohash for xn7');
        $this->assertEquals('xn5', $geohash->adjacent('xn7', 'bottom'), 'Did not find correct bottom adjacent geohash for xn7');
        $this->assertEquals('xn6', $geohash->adjacent('xn7', 'left'), 'Did not find correct left adjacent geohash for xn7');
        $this->assertEquals('xnd', $geohash->adjacent($geohash->adjacent('xn7', 'left'), 'top'), 'Did not find correct top-left adjacent geohash for xn7');
    }
}
