<?php

/*
 * This file is part of the GeoPHP package.
 * Copyright (c) 2011 - 2016 Patrick Hayes and contributors
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace GeoPHP\Adapter;

use GeoPHP\Exception;
use GeoPHP\Geometry\Geometry;
use GeoPHP\Geometry\GeometryCollection;
use GeoPHP\Geometry\LineString;
use GeoPHP\Geometry\MultiPoint;
use GeoPHP\Geometry\MultiPolygon;
use GeoPHP\Geometry\Point;
use GeoPHP\Geometry\Polygon;

/**
 * Google geocoder adapter.
 */
class GoogleGeocode extends Adapter
{
    private $result;

    /**
     * Read an address string or array geometry objects.
     *
     * @param string $address Address to geocode
     * @param string $returnType Type of Geometry to return. Can either be 'points' or 'bounds' (polygon)
     * @param Geometry|array|bool $bounds array - Limit the search area to within this region. For example
     *                                by default geocoding "Cairo" will return the location of Cairo Egypt.
     *                                If you pass a polygon of illinois, it will return Cairo IL.
     * @param bool $returnMultiple - Return all results in a multipoint or multipolygon
     *
     * @return Geometry|GeometryCollection
     *
     * @throws
     */
    public function read($address, $returnType = 'point', $bounds = false, $returnMultiple = false)
    {
        if (is_array($address)) {
            $address = implode(',', $address);
        }

        if (is_object($bounds)) {
            $bounds = $bounds->getBBox();
        }

        if (is_array($bounds)) {
            $boundsString = '&bounds=' . $bounds['miny'] . ',' . $bounds['minx'] . '|' . $bounds['maxy'] . ',' . $bounds['maxx'];
        } else {
            $boundsString = '';
        }

        $url = 'http://maps.googleapis.com/maps/api/geocode/json';
        $url .= '?address=' . urlencode($address);
        $url .= $boundsString;
        $url .= '&sensor=false';
        $this->result = json_decode(@file_get_contents($url));

        if ($this->result->status == 'OK') {
            if ($returnMultiple == false) {
                if ($returnType == 'point') {
                    return $this->getPoint();
                }
                if ($returnType == 'bounds' || $returnType == 'polygon') {
                    return $this->getPolygon();
                }
            }
            if ($returnMultiple == true) {
                if ($returnType == 'point') {
                    $points = array();
                    foreach ($this->result->results as $delta => $item) {
                        $points[] = $this->getPoint($delta);
                    }

                    return new MultiPoint($points);
                }
                if ($returnType == 'bounds' || $returnType == 'polygon') {
                    $polygons = array();
                    foreach ($this->result->results as $delta => $item) {
                        $polygons[] = $this->getPolygon($delta);
                    }

                    return new MultiPolygon($polygons);
                }
            }
        } else {
            if ($this->result->status) {
                throw new Exception('Error in Google Geocoder: ' . $this->result->status);
            } else {
                throw new Exception('Unknown error in Google Geocoder');
            }
        }
    }

    /**
     * Serialize geometries into a WKT string.
     *
     * @param Geometry $geometry
     * @param string $returnType Should be either 'string' or 'array'
     *
     * @return string Does a reverse geocode of the geometry
     *
     * @throws
     */
    public function write(Geometry $geometry, $returnType = 'string')
    {
        if ($geometry->isEmpty()) {
            return '';
        }

        $centroid = $geometry->getCentroid();
        $lat = $centroid->getY();
        $lon = $centroid->getX();

        $url = 'http://maps.googleapis.com/maps/api/geocode/json';
        $url .= '?latlng=' . $lat . ',' . $lon;
        $url .= '&sensor=false';

        $this->result = json_decode(@file_get_contents($url));

        if ($this->result->status == 'OK') {
            if ($returnType == 'string') {
                return $this->result->results[0]->formatted_address;
            }
            if ($returnType == 'array') {
                return $this->result->results[0]->address_components;
            }
        } else {
            if ($this->result->status) {
                throw new Exception('Error in Google Reverse Geocoder: ' . $this->result->status);
            } else {
                throw new Exception('Unknown error in Google Reverse Geocoder');
            }
        }
    }

    private function getPoint($delta = 0)
    {
        $lat = $this->result->results[$delta]->geometry->location->lat;
        $lon = $this->result->results[$delta]->geometry->location->lng;

        return new Point($lon, $lat);
    }

    private function getPolygon($delta = 0)
    {
        $points = array(
            $this->getTopLeft($delta),
            $this->getTopRight($delta),
            $this->getBottomRight($delta),
            $this->getBottomLeft($delta),
            $this->getTopLeft($delta),
        );

        $outerRing = new LineString($points);

        return new Polygon(array($outerRing));
    }

    private function getTopLeft($delta = 0)
    {
        $lat = $this->result->results[$delta]->geometry->bounds->northeast->lat;
        $lon = $this->result->results[$delta]->geometry->bounds->southwest->lng;

        return new Point($lon, $lat);
    }

    private function getTopRight($delta = 0)
    {
        $lat = $this->result->results[$delta]->geometry->bounds->northeast->lat;
        $lon = $this->result->results[$delta]->geometry->bounds->northeast->lng;

        return new Point($lon, $lat);
    }

    private function getBottomLeft($delta = 0)
    {
        $lat = $this->result->results[$delta]->geometry->bounds->southwest->lat;
        $lon = $this->result->results[$delta]->geometry->bounds->southwest->lng;

        return new Point($lon, $lat);
    }

    private function getBottomRight($delta = 0)
    {
        $lat = $this->result->results[$delta]->geometry->bounds->southwest->lat;
        $lon = $this->result->results[$delta]->geometry->bounds->northeast->lng;

        return new Point($lon, $lat);
    }
}
