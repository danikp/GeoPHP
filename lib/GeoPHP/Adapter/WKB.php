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
use GeoPHP\Geometry\MultiLineString;
use GeoPHP\Geometry\MultiPoint;
use GeoPHP\Geometry\MultiPolygon;
use GeoPHP\Geometry\Point;
use GeoPHP\Geometry\Polygon;

/**
 * PHP Geometry/WKB encoder/decoder.
 */
class WKB extends Adapter
{
    private $dimension = 2;
    private $z = false;
    private $m = false;

    /**
     * Read WKB into geometry objects.
     *
     * @param string $wkb Well-known-binary string
     * @param bool $isHexString If this is a hexedecimal string that is in need of packing
     *
     * @return Geometry
     *
     * @throws
     */
    public function read($wkb, $isHexString = false)
    {
        if ($isHexString) {
            $wkb = pack('H*', $wkb);
        }

        if (empty($wkb)) {
            throw new Exception\ParseException('Cannot read empty WKB geometry. Found ' . gettype($wkb));
        }

        $mem = fopen('php://memory', 'r+');
        fwrite($mem, $wkb);
        fseek($mem, 0);

        $geometry = $this->getGeometry($mem);
        fclose($mem);

        return $geometry;
    }

    protected function getGeometry(&$mem)
    {
        $baseInfo = unpack('corder/ctype/cz/cm/cs', fread($mem, 5));
        if ($baseInfo['order'] !== 1) {
            fclose($mem); // restore
            throw new Exception\UnsupportedException('Only NDR (little endian) SKB format is supported at the moment');
        }

        if ($baseInfo['z']) {
            ++$this->dimension;
            $this->z = true;
        }
        if ($baseInfo['m']) {
            ++$this->dimension;
            $this->m = true;
        }

        // If there is SRID information, ignore it - use EWKB Adapter to get SRID support
        if ($baseInfo['s']) {
            fread($mem, 4);
        }

        switch ($baseInfo['type']) {
            case 1:
                return $this->getPoint($mem);
            case 2:
                return $this->getLinstring($mem);
            case 3:
                return $this->getPolygon($mem);
            case 4:
                return $this->getMulti($mem, 'point');
            case 5:
                return $this->getMulti($mem, 'line');
            case 6:
                return $this->getMulti($mem, 'polygon');
            case 7:
                return $this->getMulti($mem, 'geometry');
        }
    }

    protected function getPoint(&$mem)
    {
        $pointCoords = unpack('d*', fread($mem, $this->dimension * 8));
        if (!empty($pointCoords)) {
            return new Point($pointCoords[1], $pointCoords[2]);
        } else {
            return new Point(); // EMPTY point
        }
    }

    protected function getLinstring(&$mem)
    {
        // Get the number of points expected in this string out of the first 4 bytes
        $lineLength = unpack('L', fread($mem, 4));

        // Return an empty linestring if there is no line-length
        if (!$lineLength[1]) {
            return new LineString();
        }

        // Read the nubmer of points x2 (each point is two coords) into decimal-floats
        $lineCoords = unpack('d*', fread($mem, $lineLength[1] * $this->dimension * 8));

        // We have our coords, build up the linestring
        $components = array();
        $i = 1;
        $numCoords = count($lineCoords);
        while ($i <= $numCoords) {
            $components[] = new Point($lineCoords[$i], $lineCoords[$i + 1]);
            $i += 2;
        }

        return new LineString($components);
    }

    protected function getPolygon(&$mem)
    {
        // Get the number of linestring expected in this poly out of the first 4 bytes
        $polyLength = unpack('L', fread($mem, 4));

        $components = array();
        $i = 1;
        while ($i <= $polyLength[1]) {
            $components[] = $this->getLinstring($mem);
            ++$i;
        }

        return new Polygon($components);
    }

    protected function getMulti(&$mem, $type)
    {
        // Get the number of items expected in this multi out of the first 4 bytes
        $multiLength = unpack('L', fread($mem, 4));

        $components = array();
        $i = 1;
        while ($i <= $multiLength[1]) {
            $components[] = $this->getGeometry($mem);
            ++$i;
        }

        switch ($type) {
            case 'point':
                return new MultiPoint($components);
            case 'line':
                return new MultiLineString($components);
            case 'polygon':
                return new MultiPolygon($components);
            case 'geometry':
                return new GeometryCollection($components);
        }
    }

    /**
     * Serialize geometries into WKB string.
     *
     * @param Geometry $geometry
     *
     * @return string The WKB string representation of the input geometries
     */
    public function write(Geometry $geometry, $writeAsHex = false)
    {
        // We always write into NDR (little endian)
        $wkb = pack('c', 1);

        switch ($geometry->getGeomType()) {
            case 'Point';
                $wkb .= pack('L', 1);
                $wkb .= $this->writePoint($geometry);
                break;
            case 'LineString';
                $wkb .= pack('L', 2);
                $wkb .= $this->writeLineString($geometry);
                break;
            case 'Polygon';
                $wkb .= pack('L', 3);
                $wkb .= $this->writePolygon($geometry);
                break;
            case 'MultiPoint';
                $wkb .= pack('L', 4);
                $wkb .= $this->writeMulti($geometry);
                break;
            case 'MultiLineString';
                $wkb .= pack('L', 5);
                $wkb .= $this->writeMulti($geometry);
                break;
            case 'MultiPolygon';
                $wkb .= pack('L', 6);
                $wkb .= $this->writeMulti($geometry);
                break;
            case 'GeometryCollection';
                $wkb .= pack('L', 7);
                $wkb .= $this->writeMulti($geometry);
                break;
        }

        if ($writeAsHex) {
            $unpacked = unpack('H*', $wkb);

            return $unpacked[1];
        } else {
            return $wkb;
        }
    }

    protected function writePoint(Point $point)
    {
        if (!$point->isEmpty()) {
            $wkb = pack('dd', $point->x(), $point->y());
        } else {
            // see: https://trac.osgeo.org/postgis/ticket/3181
            $wkb = pack('dd', 'nan', 'nan');
        }

        return $wkb;
    }

    protected function writeLineString($line)
    {
        // Set the number of points in this line
        $wkb = pack('L', $line->numPoints());

        // Set the coords
        foreach ($line->getComponents() as $point) {
            $wkb .= pack('dd', $point->x(), $point->y());
        }

        return $wkb;
    }

    protected function writePolygon($poly)
    {
        // Set the number of lines in this poly
        $wkb = pack('L', $poly->numGeometries());

        // Write the lines
        foreach ($poly->getComponents() as $line) {
            $wkb .= $this->writeLineString($line);
        }

        return $wkb;
    }

    protected function writeMulti($geometry)
    {
        // Set the number of components
        $wkb = pack('L', $geometry->numGeometries());

        // Write the components
        foreach ($geometry->getComponents() as $component) {
            $wkb .= $this->write($component);
        }

        return $wkb;
    }
}
