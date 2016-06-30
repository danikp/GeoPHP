<?php

/*
 * This file is part of the GeoPHP package.
 * Copyright (c) 2011 - 2016 Patrick Hayes and contributors
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace GeoPHP\Tests;

use GeoPHP\Geo;
use GeoPHP\Geometry\Geometry;

class PostgisTest extends BaseTest
{
    /**
     * @var
     */
    protected $connection;

    public function setUp()
    {
        parent::setUp();

        if (!getenv('GEOPHP_POSTGIS_TEST_ENABLED')) {
            $this->markTestSkipped('Postgis test is not enabled. Set "POSTGIS_TEST_ENABLED" environment variable to enable it.');
        }

        if (!class_exists('PDO')) {
            $this->markTestSkipped('PDO extension is not available.');
        }

        $database = getenv('GEOPHP_PG_DATABASE');
        if (!$database) {
            $database = 'geophp';
        }

        $host = getenv('GEOPHP_PG_HOST');
        if (!$host) {
            $host = 'localhost';
        }

        $user = getenv('GEOPHP_PG_USER');
        if (!$user) {
            $user = 'geophp';
        }

        $password = getenv('GEOPHP_PG_PASSWORD');
        if (!$password) {
            $password = 'geophp';
        }

        $port = getenv('GEOPHP_PG_PORT');
        if (!$port) {
            $port = 5432;
        }

        try {
            $this->connection = new \PDO("pgsql:host=$host;port=$port;dbname=$database;", $user, $password);
        } catch (\PDOException $e) {
            if (strpos($e->getMessage(), 'could not find driver') !== false) {
                $this->markTestSkipped('Postgresql driver for PDO is not installed');
            }
            throw $e;
        }

        $this->connection->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        // create structure
        $this->connection->exec('CREATE EXTENSION IF NOT EXISTS postgis');
        $this->connection->exec('DROP TABLE IF EXISTS test');
        $this->connection->exec('CREATE TABLE test(name text, type text, geom geometry)');
    }

    public function testGis()
    {
        foreach (glob($this->fixturesDir . '/*.*') as $file) {
            $basename = basename($file);
            $this->log('Testing postgis with file :' . $basename);
            $parts = explode('.', $basename);
            $name = $parts[0];
            $format = $parts[1];

            $geometry = Geo::load(file_get_contents($file), $format);
            $geometry->setSRID(4326);

            $this->runTests($name, $format, $geometry, 'wkb');
            $this->runTests($name, $format, $geometry, 'ewkb');
        }
    }

    private function runTests($name, $type, Geometry $geom, $format)
    {
        switch ($format) {
            case 'wkt':
                $from = 'Text';
                break;
            case 'wkb':
                $from = 'Wkb';
                break;
            case 'ewkb':
                $from = 'EWKB';
                break;
            default:
                throw new \UnexpectedValueException();
        }

        $sql = sprintf('INSERT INTO test(name, type, geom) VALUES (:name, :type, ST_GeomFrom%s(:geom))', $from);
        $sth = $this->connection->prepare($sql);
        $out = $geom->out($format);

        $sth->bindParam(':name', $name);
        $sth->bindParam(':type', $type);
        $sth->bindParam(':geom', $out, \PDO::PARAM_LOB);
        $sth->execute();

        $sth = $this->connection->prepare('SELECT ST_asBinary(geom) as geom FROM test WHERE name = :name AND type = :type');
        $sth->bindParam(':name', $name);
        $sth->bindParam(':type', $type);
        $sth->execute();

        $geomBinary = $sth->fetchColumn();
        $data = stream_get_contents($geomBinary);

        $geometry = Geo::load($data);
        $this->assertInstanceOf('GeoPHP\Geometry\Geometry', $geometry);

        // text
        $sth = $this->connection->prepare('SELECT ST_asText(geom) as geom FROM test WHERE name = :name AND type = :type');
        $sth->bindParam(':name', $name);
        $sth->bindParam(':type', $type);
        $sth->execute();

        $geomText = $sth->fetchColumn();
        $geometry = Geo::load($geomText, 'ewkt');
        $this->assertInstanceOf('GeoPHP\Geometry\Geometry', $geometry);
    }
}
