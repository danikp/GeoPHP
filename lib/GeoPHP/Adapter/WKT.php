<?php

/*
 * This file is part of the GeoPHP package.
 * Copyright (c) 2011 - 2016 Patrick Hayes and contributors
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace GeoPHP\Adapter;

use GeoPHP\Config;
use GeoPHP\Geo;
use GeoPHP\Geometry\Geometry;
use GeoPHP\Geometry\GeometryCollection;
use GeoPHP\Geometry\LineString;
use GeoPHP\Geometry\MultiLineString;
use GeoPHP\Geometry\MultiPoint;
use GeoPHP\Geometry\MultiPolygon;
use GeoPHP\Geometry\Point;
use GeoPHP\Geometry\Polygon;
use GeoPHP\Util\TrimUnneededDecimalsFunction;

/**
 * WKT (Well Known Text) Adapter.
 */
class WKT extends Adapter
{
    /**
     * Read WKT string into geometry objects.
     *
     * @param string $WKT A WKT string
     *
     * @return Geometry
     */
    public function read($wkt)
    {
        $wkt = trim($wkt);

        // If it contains a ';', then it contains additional SRID data
        if (strpos($wkt, ';')) {
            $parts = explode(';', $wkt);
            $wkt = $parts[1];
            $eparts = explode('=', $parts[0]);
            $srid = $eparts[1];
        } else {
            $srid = null;
        }

        // If geos is installed, then we take a shortcut and let it parse the WKT
        if (Geo::geosInstalled()) {
            $reader = new \GEOSWKTReader();
            if ($srid) {
                $geom = Geo::geosToGeometry($reader->read($wkt));
                $geom->setSRID($srid);

                return $geom;
            } else {
                return Geo::geosToGeometry($reader->read($wkt));
            }
        }
        $wkt = str_replace(', ', ',', $wkt);

        // For each geometry type, check to see if we have a match at the
        // beginning of the string. If we do, then parse using that type
        foreach (Geo::geometryList() as $geomType) {
            $wktGeom = strtoupper($geomType);
            if (strtoupper(substr($wkt, 0, strlen($wktGeom))) == $wktGeom) {
                $dataString = $this->getDataString($wkt);
                $method = 'parse' . $geomType;

                if ($srid) {
                    $geom = $this->$method($dataString);
                    $geom->setSRID($srid);

                    return $geom;
                } else {
                    return $this->$method($dataString);
                }
            }
        }
    }

    private function parsePoint($dataString)
    {
        $dataString = $this->trimParens($dataString);

        // If it's marked as empty, then return an empty point
        if ($dataString == 'EMPTY') {
            return new Point();
        }

        $parts = explode(' ', $dataString);

        return new Point($parts[0], $parts[1]);
    }

    private function parseLineString($dataString)
    {
        $dataString = $this->trimParens($dataString);

        // If it's marked as empty, then return an empty line
        if ($dataString == 'EMPTY') {
            return new LineString();
        }

        $parts = explode(',', $dataString);
        $points = array();
        foreach ($parts as $part) {
            $points[] = $this->parsePoint($part);
        }

        return new LineString($points);
    }

    private function parsePolygon($dataString)
    {
        $dataString = $this->trimParens($dataString);

        // If it's marked as empty, then return an empty polygon
        if ($dataString == 'EMPTY') {
            return new Polygon();
        }

        $parts = explode('),(', $dataString);
        $lines = array();
        foreach ($parts as $part) {
            if (!$this->beginsWith($part, '(')) {
                $part = '(' . $part;
            }
            if (!$this->endsWith($part, ')')) {
                $part = $part . ')';
            }
            $lines[] = $this->parseLineString($part);
        }

        return new Polygon($lines);
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedPrivateMethod)
     */
    private function parseMultiPoint($dataString)
    {
        $dataString = $this->trimParens($dataString);

        // If it's marked as empty, then return an empty MutiPoint
        if ($dataString == 'EMPTY') {
            return new MultiPoint();
        }

        $parts = explode(',', $dataString);
        $points = array();
        foreach ($parts as $part) {
            $points[] = $this->parsePoint($part);
        }

        return new MultiPoint($points);
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedPrivateMethod)
     */
    private function parseMultiLineString($dataString)
    {
        $dataString = $this->trimParens($dataString);

        // If it's marked as empty, then return an empty multi-linestring
        if ($dataString == 'EMPTY') {
            return new MultiLineString();
        }

        $parts = explode('),(', $dataString);
        $lines = array();
        foreach ($parts as $part) {
            // Repair the string if the explode broke it
            if (!$this->beginsWith($part, '(')) {
                $part = '(' . $part;
            }
            if (!$this->endsWith($part, ')')) {
                $part = $part . ')';
            }
            $lines[] = $this->parseLineString($part);
        }

        return new MultiLineString($lines);
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedPrivateMethod)
     */
    private function parseMultiPolygon($dataString)
    {
        $dataString = $this->trimParens($dataString);

        // If it's marked as empty, then return an empty multi-polygon
        if ($dataString == 'EMPTY') {
            return new MultiPolygon();
        }

        $parts = explode(')),((', $dataString);
        $polys = array();
        foreach ($parts as $part) {
            // Repair the string if the explode broke it
            if (!$this->beginsWith($part, '((')) {
                $part = '((' . $part;
            }
            if (!$this->endsWith($part, '))')) {
                $part = $part . '))';
            }
            $polys[] = $this->parsePolygon($part);
        }

        return new MultiPolygon($polys);
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedPrivateMethod)
     */
    private function parseGeometryCollection($dataString)
    {
        $dataString = $this->trimParens($dataString);

        // If it's marked as empty, then return an empty geom-collection
        if ($dataString == 'EMPTY') {
            return new GeometryCollection();
        }

        $geometries = array();
        $str = preg_replace('/,\s*([A-Za-z])/', '|$1', $dataString);
        $components = explode('|', trim($str));

        foreach ($components as $component) {
            $geometries[] = $this->read($component);
        }

        return new GeometryCollection($geometries);
    }

    protected function getDataString($wkt)
    {
        $firstParen = strpos($wkt, '(');

        if ($firstParen !== false) {
            return substr($wkt, $firstParen);
        } elseif (strstr($wkt, 'EMPTY')) {
            return 'EMPTY';
        } else {
            return false;
        }
    }

    /**
     * Trim the parenthesis and spaces.
     */
    protected function trimParens($str)
    {
        $str = trim($str);

        // We want to only strip off one set of parenthesis
        if ($this->beginsWith($str, '(')) {
            return substr($str, 1, -1);
        } else {
            return $str;
        }
    }

    protected function beginsWith($str, $char)
    {
        if (substr($str, 0, strlen($char)) == $char) {
            return true;
        } else {
            return false;
        }
    }

    protected function endsWith($str, $char)
    {
        if (substr($str, (0 - strlen($char))) == $char) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Serialize geometries into a WKT string.
     *
     * @param Geometry $geometry
     *
     * @return string The WKT string representation of the input geometries
     */
    public function write(Geometry $geometry)
    {
        if ($geometry->isEmpty()) {
            return strtoupper($geometry->geometryType()) . ' EMPTY';
        } else {
            // If geos is installed, then we take a shortcut and let it write the WKT
            if (Geo::geosInstalled() && $geometry->geos()) {
                $writer = new \GEOSWKTWriter();
                $writer->setTrim(Config::$trimUnnecessaryDecimals);
                $writer->setRoundingPrecision(Config::$roundingPrecision);

                return $writer->write($geometry->geos());
            } elseif ($data = $this->extractData($geometry)) {
                return strtoupper($geometry->geometryType()) . ' (' . $data . ')';
            }
        }
    }

    /**
     * Extract geometry to a WKT string.
     *
     * @param Geometry $geometry A Geometry object
     *
     * @return string
     */
    public function extractData($geometry)
    {
        $parts = array();
        switch ($geometry->geometryType()) {
            case 'Point':
                ini_set('precision', 16);
                if (Config::$trimUnnecessaryDecimals) {
                    $func = new TrimUnneededDecimalsFunction();
                    if (Config::$roundingPrecision == -1) {
                        $precision = 16;
                    } else {
                        $precision = Config::$roundingPrecision;
                    }
                    $result = $func->__invoke(
                        sprintf('%.' . ($precision - 2) . 'f', $geometry->getX())
                    ) . ' ' . $func->__invoke(
                        sprintf('%.' . ($precision - 2) . 'f', $geometry->getY())
                    );
                } else {
                    $result = sprintf(Config::$roundingPrecisionFormat, $geometry->getX()) . ' ' . sprintf(Config::$roundingPrecisionFormat, $geometry->getY());
                }
                ini_restore('precision');

                return $result;

            case 'LineString':
                foreach ($geometry->getComponents() as $component) {
                    $parts[] = $this->extractData($component);
                }

                return implode(', ', $parts);
            case 'Polygon':
            case 'MultiPoint':
            case 'MultiLineString':
            case 'MultiPolygon':
                foreach ($geometry->getComponents() as $component) {
                    $parts[] = '(' . $this->extractData($component) . ')';
                }

                return implode(', ', $parts);
            case 'GeometryCollection':
                foreach ($geometry->getComponents() as $component) {
                    $parts[] = strtoupper($component->geometryType()) . ' (' . $this->extractData($component) . ')';
                }

                return implode(', ', $parts);
        }
    }
}
