<?php

/**
    \file flight-builder.php
    \brief This script processes positions and builds virtual flights based on timestamps
    \ingroup PiAwareTools
*/

include_once('autoload.php');
include_once('autoconfig.php');
include_once('../lib/statistics.php');
include_once('../lib/split-flights.php');

use \DigTech\Logging\Logger as Logger;
use \DigTech\Database\MySQL as MyDB;
use \DigTech\Database\Record as Record;


function retrieveSummaryResults($db, $sql)
{
    $ret = [];

    $res = $db->query($sql);
    if($res)
    {
        while($row = $db->fetch($res))
        {
            $ret[$row['label']] = $row['count'];
        }
        $db->freeResult($res);
    }

    return $ret;
}

function retrieveDetailResults($db, $sql)
{
    $ret = [];

    $res = $db->query($sql);
    if($res)
    {
        while($row = $db->fetch($res))
        {
            $ret[] = $row;
        }
        $db->freeResult($res);
    }

    return $ret;
}

function getSystemAircraftTotals($db)
{
    $ret = [];

    $sql = "SELECT
                DATE(create_date) AS label,
                count(*) AS count
            FROM
                aircraft
            WHERE
                DATE(create_date) > DATE(DATE_SUB(NOW(), INTERVAL 7 DAY))
            GROUP BY
                DATE(create_date)
            ORDER BY
                1";
    $ret['Created'] = retrieveSummaryResults($db, $sql);

    $sql = "SELECT
                DATE(modify_date) AS label,
                count(*) AS count
            FROM
                aircraft
            WHERE
                modify_date != '0000-00-00 00:00:00' AND
                DATE(create_date) > DATE(DATE_SUB(NOW(), INTERVAL 7 DAY))
            GROUP BY
                DATE(modify_date)
            ORDER BY
                1";
    $ret['Modified'] = retrieveSummaryResults($db, $sql);
    return $ret;
}

function getSystemFlightTotals($db)
{
    $ret = [];

    $sql = "SELECT
                DATE(create_date) AS label,
                count(*) AS count
            FROM
                flight
            WHERE
                DATE(create_date) > DATE(DATE_SUB(NOW(), INTERVAL 7 DAY))
            GROUP BY
                DATE(create_date)
            ORDER BY
                1";
    $ret['Created'] = retrieveSummaryResults($db, $sql);

    $sql = "SELECT
                DATE(modify_date) AS label,
                count(*) AS count
            FROM
                flight
            WHERE
                modify_date != '0000-00-00 00:00:00' AND
                DATE(create_date) > DATE(DATE_SUB(NOW(), INTERVAL 7 DAY))
            GROUP BY
                DATE(modify_date)
            ORDER BY
                1";
    $ret['Modified'] = retrieveSummaryResults($db, $sql);
    return $ret;
}

function getSystemFlightTrackTotals($db)
{
    $ret = [];

    $sql = "SELECT
                DATE(create_date) AS label,
                count(*) AS count
            FROM
                flight_track
            WHERE
                DATE(create_date) > DATE(DATE_SUB(NOW(), INTERVAL 7 DAY))
            GROUP BY
                DATE(create_date)
            ORDER BY
                1";
    $ret['Created'] = retrieveSummaryResults($db, $sql);

    $sql = "SELECT
                DATE(modify_date) AS label,
                count(*) AS count
            FROM
                flight_track
            WHERE
                modify_date != '0000-00-00 00:00:00' AND
                DATE(create_date) > DATE(DATE_SUB(NOW(), INTERVAL 7 DAY))

            GROUP BY
                DATE(modify_date)
            ORDER BY
                1";
    $ret['Modified'] = retrieveSummaryResults($db, $sql);
    return $ret;
}

function getADSBCategoryTotals($db)
{
    $ret = [];

    $sql = "SELECT
                IFNULL(a.adsb_category, 'empty') as label,
                count(*) AS count
            FROM
                aircraft a
            GROUP BY
                a.adsb_category
            ORDER BY
                1";
    $ret = retrieveSummaryResults($db, $sql);

    return $ret;
}

function getTopCountryTotals($db)
{
    $ret = [];

    $sql = "SELECT
                a.register_country as label,
                count(*) AS count
            FROM
                aircraft a
            GROUP BY
                a.register_country
            ORDER BY
                2 DESC
            LIMIT 10";
    $ret = retrieveSummaryResults($db, $sql);

    return $ret;
}

function getTopAircraftModels($db)
{
    $ret = [];
    $categoryList = [ 'A1', 'A2', 'A3', 'A4', 'A5', 'A7' ];

    foreach($categoryList as $category)
    {
        $sql = "SELECT
                    dv.aircraft_manufacturer AS Manufacturer,
                    dv.aircraft_model AS Model,
                    COUNT(*) AS Count
                FROM
                    aircraft a
                        INNER JOIN faa.aircraft_details_view dv ON (a.icao_hex = dv.icao_hex)
                WHERE
                    a.adsb_category = '$category'
                GROUP BY
                    dv.aircraft_manufacturer,
                    dv.aircraft_model
                ORDER BY
                    COUNT(*) DESC
                LIMIT
                    10";
        $ret[$category] = retrieveDetailResults($db, $sql);
    }
    return $ret;
}

function getCategoryFlights($db)
{
    $ret = [];
    $sql = "SELECT
	            DATE(f.first_seen) \"Flight Date\",
                a.adsb_category AS Category,
                faa.GetADSBCategory(a.adsb_category) AS Description,
                COUNT(f.flight_seq) AS Flights
            FROM
            	aircraft a
            		INNER JOIN flight f ON (a.aircraft_seq = f.aircraft_seq)
            		INNER JOIN faa.aircraft_details_view dv ON (a.icao_hex = dv.icao_hex)
            WHERE
	            a.adsb_category IS NOT NULL AND
                DATE(f.first_seen) > DATE(DATE_SUB(NOW(), INTERVAL 7 DAY))

            GROUP BY
	            DATE(f.first_seen),
	            faa.GetADSBCategory(a.adsb_category)
            ORDER BY
	            1,
                2";
    $dataset = retrieveDetailResults($db, $sql);

    // spin through the data to build out an unpopulated map to ensure table completeness
    $dates = [];
    $categories = [];
    $descriptions = [];
    foreach($dataset as $row)
    {
        $dates[$row['Flight Date']]++;
        $categories[$row['Category']]++;
        $descriptions[$row['Category']] = $row['Description'];
    }
    ksort($dates);
    ksort($categories);
    ksort($descriptions);

    foreach($dates as $date => $dateCount)
    {
        foreach($categories as $category => $categoryCount)
        {
            $ret[$date][$descriptions[$category]] = 0;
        }
    }

    foreach($dataset as $row)
    {
        $ret[$row['Flight Date']][$row['Description']] = $row['Flights'];
    }

    return $ret;
}

function getTopFlightModels($db)
{
    $ret = [];
    $categoryList = [ 'A1', 'A2', 'A3', 'A4', 'A5', 'A7' ];

    foreach($categoryList as $category)
    {
        $sql = "SELECT
                    dv.aircraft_manufacturer AS Manufacturer,
                    dv.aircraft_model AS Model,
                    COUNT(f.flight_seq) AS Flights
                FROM
                    aircraft a
                        INNER JOIN flight f ON (a.aircraft_seq = f.aircraft_seq)
                        INNER JOIN faa.aircraft_details_view dv ON (a.icao_hex = dv.icao_hex)
                WHERE
                    a.adsb_category = '$category'
                GROUP BY
                    dv.aircraft_manufacturer,
                    dv.aircraft_model
                ORDER BY
                    COUNT(f.flight_seq) DESC
                LIMIT
                    10";
        $ret[$category] = retrieveDetailResults($db, $sql);
    }
    return $ret;
}

/**
    \brief Main entry point
*/
function main()
{
    global $runtimeStatistics;

    $cfg = getGlobalConfiguration();
    $db = new MyDB\Connection();
    $config = $cfg->getSection('db-piaware');
    $db->configure($config);
    $config = $cfg->getSection('logging');
    Logger::configure($config) ;
    
    $stats = [];

    if($db->connect())
    {
        $stats['generated'] = date('Y-m-d H:i:s');
        $stats['system-totals']['aircraft'] = getSystemAircraftTotals($db);
        $stats['system-totals']['flights'] = getSystemFlightTotals($db);
        $stats['system-totals']['tracks'] = getSystemFlightTrackTotals($db);
        $stats['aircraft-category'] = getADSBCategoryTotals($db);
        $stats['register-country'] = getTopCountryTotals($db);
        $stats['aircraft-top-10'] = getTopAircraftModels($db);
        $stats['flight-category'] = getCategoryFlights($db);
        $stats['flight-top-10'] = getTopFlightModels($db);
    }
    else
    {
        Logger::error("Unable to open database connection\n");
    }

    file_put_contents('../piaware-statistics.json', json_encode($stats, JSON_PRETTY_PRINT));
}

$shortOpts = '';                        ///< short command line options (not supported)
$longOpts = [];                         ///< long command line options
$opts = getopt($shortOpts, $longOpts);  ///< command line options

main();
