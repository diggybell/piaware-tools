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


function retrieveResults($db, $sql)
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
function getSystemAircraftTotals($db)
{
    $ret = [];

    $sql = "SELECT
                DATE(create_date) AS label,
                count(*) AS count
            FROM
                aircraft
            GROUP BY
                DATE(create_date)
            ORDER BY
                1";
    $ret['created'] = retrieveResults($db, $sql);

    $sql = "SELECT
                DATE(modify_date) AS label,
                count(*) AS count
            FROM
                aircraft
            WHERE
                modify_date != '0000-00-00 00:00:00'
            GROUP BY
                DATE(modify_date)
            ORDER BY
                1";
    $ret['modified'] = retrieveResults($db, $sql);
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
            GROUP BY
                DATE(create_date)
            ORDER BY
                1";
    $ret['created'] = retrieveResults($db, $sql);

    $sql = "SELECT
                DATE(modify_date) AS label,
                count(*) AS count
            FROM
                flight
            WHERE
                modify_date != '0000-00-00 00:00:00'
            GROUP BY
                DATE(modify_date)
            ORDER BY
                1";
    $ret['modified'] = retrieveResults($db, $sql);
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
            GROUP BY
                DATE(create_date)
            ORDER BY
                1";
    $ret['created'] = retrieveResults($db, $sql);

    $sql = "SELECT
                DATE(modify_date) AS label,
                count(*) AS count
            FROM
                flight_track
            WHERE
                modify_date != '0000-00-00 00:00:00'
            GROUP BY
                DATE(modify_date)
            ORDER BY
                1";
    $ret['modified'] = retrieveResults($db, $sql);
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
    $ret = retrieveResults($db, $sql);

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
    $ret = retrieveResults($db, $sql);

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
