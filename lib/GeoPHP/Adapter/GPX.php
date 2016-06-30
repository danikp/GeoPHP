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
use GeoPHP\Geometry\Point;

/**
 * GPX encoder/decoder.
 */
class GPX extends Adapter
{
    /**
     * @var bool
     */
    private $namespace = false;

    /**
     * Name-space string. eg 'georss:'.
     *
     * @var string
     */
    private $nss = '';

    /**
     * Read GPX string into geometry objects.
     *
     * @param string $gpx A GPX string
     *
     * @return Geometry|GeometryCollection
     */
    public function read($gpx)
    {
        return $this->geomFromText($gpx);
    }

    /**
     * Serialize geometries into a GPX string.
     *
     * @param Geometry $geometry
     *
     * @return string The GPX string representation of the input geometries
     */
    public function write(Geometry $geometry, $namespace = false)
    {
        if ($geometry->isEmpty()) {
            return ''; // we have to return string
        }

        if ($namespace) {
            $this->namespace = $namespace;
            $this->nss = $namespace . ':';
        }

        return '<' . $this->nss . 'gpx creator="GeoPHP" version="' . Geo::version() . '">' . $this->geometryToGPX($geometry) . '</' . $this->nss . 'gpx>';
    }

    public function geomFromText($text)
    {
        // Change to lower-case and strip all CDATA
        $text = strtolower($text);
        $text = preg_replace('/<!\[cdata\[(.*?)\]\]>/s', '', $text);

        // Load into DOMDocument
        $xmlobj = new \DOMDocument();
        $xmlobj->loadXML($text);

        if ($xmlobj === false) {
            throw new Exception\ParseException('Invalid GPX: ' . $text);
        }

        $this->xmlobj = $xmlobj;
        try {
            $geom = $this->geomFromXML();
        } catch (\InvalidText $e) {
            throw new Exception\ParseException('Cannot read geometry from GPX' . $text, 0, $e);
        } catch (\Exception $e) {
            throw new Exception\ParseException(null, 0, $e);
        }

        return $geom;
    }

    protected function geomFromXML()
    {
        $geometries = array();
        $geometries = array_merge($geometries, $this->parseWaypoints());
        $geometries = array_merge($geometries, $this->parseTracks());
        $geometries = array_merge($geometries, $this->parseRoutes());

        if (empty($geometries)) {
            throw new Exception\ParseException('Invalid / Empty GPX');
        }

        return Geo::geometryReduce($geometries);
    }

    protected function childElements($xml, $nodename = '')
    {
        $children = array();
        foreach ($xml->childNodes as $child) {
            if ($child->nodeName == $nodename) {
                $children[] = $child;
            }
        }

        return $children;
    }

    protected function parseWaypoints()
    {
        $points = array();
        $wptElements = $this->xmlobj->getElementsByTagName('wpt');
        foreach ($wptElements as $wpt) {
            $lat = $wpt->attributes->getNamedItem('lat')->nodeValue;
            $lon = $wpt->attributes->getNamedItem('lon')->nodeValue;
            $points[] = new Point($lon, $lat);
        }

        return $points;
    }

    protected function parseTracks()
    {
        $lines = array();
        $trkElements = $this->xmlobj->getElementsByTagName('trk');
        foreach ($trkElements as $trk) {
            $components = array();
            foreach ($this->childElements($trk, 'trkseg') as $trkseg) {
                foreach ($this->childElements($trkseg, 'trkpt') as $trkpt) {
                    $lat = $trkpt->attributes->getNamedItem('lat')->nodeValue;
                    $lon = $trkpt->attributes->getNamedItem('lon')->nodeValue;
                    $components[] = new Point($lon, $lat);
                }
            }
            if ($components) {
                $lines[] = new LineString($components);
            }
        }

        return $lines;
    }

    protected function parseRoutes()
    {
        $lines = array();
        $rteElements = $this->xmlobj->getElementsByTagName('rte');
        foreach ($rteElements as $rte) {
            $components = array();
            foreach ($this->childElements($rte, 'rtept') as $rtept) {
                $lat = $rtept->attributes->getNamedItem('lat')->nodeValue;
                $lon = $rtept->attributes->getNamedItem('lon')->nodeValue;
                $components[] = new Point($lon, $lat);
            }
            $lines[] = new LineString($components);
        }

        return $lines;
    }

    protected function geometryToGPX($geom)
    {
        $type = strtolower($geom->getGeomType());
        switch ($type) {
            case 'point':
                return $this->pointToGPX($geom);
            case 'linestring':
                return $this->linestringToGPX($geom);
            case 'polygon':
            case 'multipoint':
            case 'multilinestring':
            case 'multipolygon':
            case 'geometrycollection':
                return $this->collectionToGPX($geom);
        }
    }

    private function pointToGPX($geom)
    {
        return '<' . $this->nss . 'wpt lat="' . $geom->getY() . '" lon="' . $geom->getX() . '" />';
    }

    private function linestringToGPX($geom)
    {
        $gpx = '<' . $this->nss . 'trk><' . $this->nss . 'trkseg>';

        foreach ($geom->getComponents() as $comp) {
            $gpx .= '<' . $this->nss . 'trkpt lat="' . $comp->getY() . '" lon="' . $comp->getX() . '" />';
        }

        $gpx .= '</' . $this->nss . 'trkseg></' . $this->nss . 'trk>';

        return $gpx;
    }

    public function collectionToGPX($geom)
    {
        $gpx = '';
        foreach ($geom->getComponents() as $comp) {
            $gpx .= $this->geometryToGPX($comp);
        }

        return $gpx;
    }
}
