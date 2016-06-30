<?php

/*
 * This file is part of the GeoPHP package.
 * Copyright (c) 2011 - 2016 Patrick Hayes and contributors
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace GeoPHP\Tests;

abstract class BaseTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var string
     */
    public $fixturesDir;

    public $remoteAdapters = array(
        'google_geocode',
    );

    protected $allowedEmptyOutput = array(
        // allowed empty output from adapter
        // geohash adapter return empty values for:
        // Postgis 2.2 running on my machine also outputs empty hash
        'geohash' => array(
            'line.georss',
            'barret_spur.gpx',
            '20120702.gpx',
        ),
    );

    public function setUp()
    {
        $this->fixturesDir = __DIR__ . '/input';
    }

    /**
     * Logs a message.
     *
     * @param $message
     */
    protected function log($message)
    {
        echo $message . "\n";
    }
}
