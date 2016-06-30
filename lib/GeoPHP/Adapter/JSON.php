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
use GeoPHP\Geo;
use GeoPHP\Geometry\Geometry;
use GeoPHP\Geometry\GeometryCollection;
use GeoPHP\Geometry\LineString;
use GeoPHP\Geometry\MultiLineString;
use GeoPHP\Geometry\MultiPoint;
use GeoPHP\Geometry\MultiPolygon;
use GeoPHP\Geometry\Point;
use GeoPHP\Geometry\Polygon;

/**
 * JSON reader/writer.
 *
 * Note that it will always return a GeoJSON geometry. This
 * means that if you pass it a feature, it will return the
 * geometry of that feature strip everything else.
 */
class JSON extends Adapter
{
    /**
     * Given an object or a string, return a Geometry.
     *
     * @param mixed $input The GeoJSON string or object
     *
     * @return object Geometry
     *
     * @throws
     */
    public function read($input)
    {
        if (is_string($input)) {
            $input = json_decode($input);
        }
        if (!is_object($input)) {
            throw new Exception('Invalid JSON');
        }
        if (!is_string($input->type)) {
            throw new Exception('Invalid JSON');
        }

        // Check to see if it's a FeatureCollection
        if ($input->type == 'FeatureCollection') {
            $geoms = array();
            foreach ($input->features as $feature) {
                $geoms[] = $this->read($feature);
            }

            return Geo::geometryReduce($geoms);
        }

        // Check to see if it's a Feature
        if ($input->type == 'Feature') {
            return $this->read($input->geometry);
        }

        // It's a geometry - process it
        return $this->objToGeom($input);
    }

    private function objToGeom($obj)
    {
        $type = $obj->type;

        if ($type == 'GeometryCollection') {
            return $this->objToGeometryCollection($obj);
        }
        $method = 'arrayTo' . $type;

        return $this->$method($obj->coordinates);
    }

    private function arrayToPoint($array)
    {
        if (!empty($array)) {
            return new Point($array[0], $array[1]);
        } else {
            return new Point();
        }
    }

    private function arrayToLineString($array)
    {
        $points = array();
        foreach ($array as $compArray) {
            $points[] = $this->arrayToPoint($compArray);
        }

        return new LineString($points);
    }

    private function arrayToPolygon($array)
    {
        $lines = array();
        foreach ($array as $compArray) {
            $lines[] = $this->arrayToLineString($compArray);
        }

        return new Polygon($lines);
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedPrivateMethod)
     */
    private function arrayToMultiPoint($array)
    {
        $points = array();
        foreach ($array as $compArray) {
            $points[] = $this->arrayToPoint($compArray);
        }

        return new MultiPoint($points);
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedPrivateMethod)
     */
    private function arrayToMultiLineString($array)
    {
        $lines = array();
        foreach ($array as $compArray) {
            $lines[] = $this->arrayToLineString($compArray);
        }

        return new MultiLineString($lines);
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedPrivateMethod)
     */
    private function arrayToMultiPolygon($array)
    {
        $polys = array();
        foreach ($array as $compArray) {
            $polys[] = $this->arrayToPolygon($compArray);
        }

        return new MultiPolygon($polys);
    }

    private function objToGeometryCollection($obj)
    {
        $geoms = array();
        if (empty($obj->geometries)) {
            throw new Exception('Invalid GeoJSON: GeometryCollection with no component geometries');
        }
        foreach ($obj->geometries as $compObject) {
            $geoms[] = $this->objToGeom($compObject);
        }

        return new GeometryCollection($geoms);
    }

    /**
     * Serializes an object into a geojson string.
     *
     *
     * @param Geometry $obj The object to serialize
     *
     * @return string The GeoJSON string
     */
    public function write(Geometry $geometry, $returnArray = false)
    {
        if ($returnArray) {
            return $this->getArray($geometry);
        } else {
            return json_encode($this->getArray($geometry));
        }
    }

    public function getArray($geometry)
    {
        if ($geometry->getGeomType() == 'GeometryCollection') {
            $componentArray = array();
            foreach ($geometry->components as $component) {
                $componentArray[] = array(
                    'type' => $component->geometryType(),
                    'coordinates' => $component->asArray(),
                );
            }

            return array(
                'type' => 'GeometryCollection',
                'geometries' => $componentArray,
            );
        } else {
            return array(
                'type' => $geometry->getGeomType(),
                'coordinates' => $geometry->asArray(),
            );
        }
    }
}
