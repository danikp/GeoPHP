<?php

/*
 * This file is part of the GeoPHP package.
 * Copyright (c) 2011 - 2016 Patrick Hayes and contributors
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace GeoPHP\Geometry;

/**
 * LineString. A collection of Points representing a line. A line can have more than one segment.
 */
class LineString extends Collection
{
    /**
     * @var string
     */
    protected $geomType = self::TYPE_LINE_STRING;

    /**
     * Constructor.
     *
     * @param array $points An array of at least two points with which to build the LineString
     *
     * @throws \InvalidArgumentException
     */
    public function __construct($points = array())
    {
        if (count($points) == 1) {
            throw new \InvalidArgumentException('Cannot construct a LineString with a single point');
        }

        parent::__construct($points);
    }

    // The boundary of a linestring is itself
    public function boundary()
    {
        return $this;
    }

    public function startPoint()
    {
        return $this->pointN(1);
    }

    public function endPoint()
    {
        $lastN = $this->numPoints();

        return $this->pointN($lastN);
    }

    public function isClosed()
    {
        return $this->startPoint()->equals($this->endPoint());
    }

    public function isRing()
    {
        return $this->isClosed() && $this->isSimple();
    }

    public function numPoints()
    {
        return $this->numGeometries();
    }

    public function pointN($n)
    {
        return $this->geometryN($n);
    }

    public function dimension()
    {
        if ($this->isEmpty()) {
            return 0;
        }

        return 1;
    }

    public function area()
    {
        return 0;
    }

    public function length()
    {
        if ($this->geos()) {
            return $this->geos()->length();
        }

        $length = 0;
        foreach ($this->getPoints() as $delta => $point) {
            $previousPoint = $this->geometryN($delta);
            if ($previousPoint) {
                $length += sqrt(pow(($previousPoint->getX() - $point->getX()), 2) + pow(($previousPoint->getY() - $point->getY()), 2));
            }
        }

        return $length;
    }

    public function greatCircleLength($radius = 6378137)
    {
        $length = 0;
        $points = $this->getPoints();
        for ($i = 0; $i < $this->numPoints() - 1; ++$i) {
            $point = $points[$i];
            $nextPoint = $points[$i + 1];
            if (!is_object($nextPoint)) {
                continue;
            }
            // Great circle method
            $lat1 = deg2rad($point->getY());
            $lat2 = deg2rad($nextPoint->getY());
            $lon1 = deg2rad($point->getX());
            $lon2 = deg2rad($nextPoint->getX());
            $dlon = $lon2 - $lon1;
            $length +=
                $radius *
                atan2(
                    sqrt(
                        pow(cos($lat2) * sin($dlon), 2) +
                        pow(cos($lat1) * sin($lat2) - sin($lat1) * cos($lat2) * cos($dlon), 2)
                    ),
                    sin($lat1) * sin($lat2) +
                    cos($lat1) * cos($lat2) * cos($dlon)
                );
        }

        // Returns length in meters.
        return $length;
    }

    public function haversineLength()
    {
        $degrees = 0;
        $points = $this->getPoints();
        for ($i = 0; $i < $this->numPoints() - 1; ++$i) {
            $point = $points[$i];
            $nextPoint = $points[$i + 1];
            if (!is_object($nextPoint)) {
                continue;
            }
            $degree = rad2deg(
                acos(
                    sin(deg2rad($point->getY())) * sin(deg2rad($nextPoint->getY())) +
                    cos(deg2rad($point->getY())) * cos(deg2rad($nextPoint->getY())) *
                    cos(deg2rad(abs($point->getX() - $nextPoint->getX())))
                )
            );
            $degrees += $degree;
        }
        // Returns degrees
        return $degrees;
    }

    public function explode()
    {
        $parts = array();
        $points = $this->getPoints();

        foreach ($points as $i => $point) {
            if (isset($points[$i + 1])) {
                $parts[] = new self(array($point, $points[$i + 1]));
            }
        }

        return $parts;
    }

    public function isSimple()
    {
        if ($this->geos()) {
            return $this->geos()->isSimple();
        }

        $segments = $this->explode();

        foreach ($segments as $i => $segment) {
            foreach ($segments as $j => $checkSegment) {
                if ($i != $j) {
                    if ($segment->lineSegmentIntersect($checkSegment)) {
                        return false;
                    }
                }
            }
        }

        return true;
    }

    // Utility function to check if any line segments intersect
    // Derived from http://stackoverflow.com/questions/563198/how-do-you-detect-where-two-line-segments-intersect
    public function lineSegmentIntersect($segment)
    {
        $p0X = $this->startPoint()->x();
        $p0Y = $this->startPoint()->y();
        $p1X = $this->endPoint()->x();
        $p1Y = $this->endPoint()->y();
        $p2X = $segment->startPoint()->x();
        $p2Y = $segment->startPoint()->y();
        $p3X = $segment->endPoint()->x();
        $p3Y = $segment->endPoint()->y();

        $s1X = $p1X - $p0X;
        $s1Y = $p1Y - $p0Y;
        $s2X = $p3X - $p2X;
        $s2Y = $p3Y - $p2Y;

        $fps = (-$s2X * $s1Y) + ($s1X * $s2Y);
        $fpt = (-$s2X * $s1Y) + ($s1X * $s2Y);

        if ($fps == 0 || $fpt == 0) {
            return false;
        }

        $s = (-$s1Y * ($p0X - $p2X) + $s1X * ($p0Y - $p2Y)) / $fps;
        $t = ($s2X * ($p0Y - $p2Y) - $s2Y * ($p0X - $p2X)) / $fpt;

        if ($s > 0 && $s < 1 && $t > 0 && $t < 1) {
            // Collision detected
            return true;
        }

        return false;
    }
}
