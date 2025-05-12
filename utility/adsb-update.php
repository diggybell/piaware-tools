<?php

/**
    \file adsb-update.php
    \brief This script manages transfer of aircraft and position data from PiAware to the PiAware Tools database
    \ingroup PiAwareTools
*/

include_once('autoload.php');
include_once('autoconfig.php');

use \DigTech\Logging\Logger as Logger;
use \DigTech\Database\MySQL as MyDB;
use \DigTech\Database\Record as Record;

/**
    \brief Insert or update aircraft data
    \param $db Database connection
    \param $aircraft Aircraft information to update
    \returns Status of update
    \retval true Update was successful
    \retval false Update failed
*/
function updateAircraft($db, $aircraft)
{
    global $statistics;

    $ret = false;

    $recAircraft = new Record($db, 'aircraft', [ 'aircraft_seq' => 0 ]);

    $sql = sprintf("SELECT aircraft_seq FROM aircraft WHERE icao_hex = '%s'", $aircraft['icao']);
    $res = $db->query($sql);
    if($res)
    {
        $row = $db->fetch($res);

        $recAircraft->set('aircraft_seq', $row['aircraft_seq']);
        $recAircraft->read();

        $recAircraft->set('icao_hex', $aircraft['icao']);
        $recAircraft->set('n_number', $aircraft['registry']);
        $recAircraft->set('adsb_category', $aircraft['category']);
        $recAircraft->set('register_country', $aircraft['country']);

        if($recAircraft->get('aircraft_seq') == 0)
        {
            $ret = $recAircraft->insert();
            $statistics['aircraft-insert']++;
            //Logger::log("Inserted aircraft %s[%s]\n", $aircraft['icao'], $aircraft['registry']);
        }
        else
        {
            $ret = $recAircraft->update();
            $statistics['aircraft-update']++;
            //Logger::log("Updated aircraft %s[%s]\n", $aircraft['icao'], $aircraft['registry']);
        }

        $db->freeResult($res);
    }
    else
    {
        Logger::error("Unable to locate aircraft record for %s[%s]\n", $aircraft['icao'], $aircraft['registry']);
    }
    return $ret;
}

/**
    \brief Main entry point
*/
function main()
{
    $cfg = getGlobalConfiguration();
    $db = new MyDB\Connection();
    $config = $cfg->getSection('db-piaware');
    $db->configure($config);
    $aircraftList = [];

    if($db->connect())
    {
        $aircraftList = json_decode(file_get_contents('../aircraft-history.json'), true);
        foreach($aircraftList as $icao => $aircraft)
        {
            Logger::log("Processing %s[%s]\n", $aircraft['icao'], $aircraft['registry']);
            if(strlen($aircraft['icao']) && $aircraft['icao'][0] != '~')
            {
                $ret = updateAircraft($db, $aircraft);
                if($ret)
                {

                }
                $statistics['aircraft-processed']++;
            }
        }
    }
    else
    {
        Logger::error("Unable to open database connection\n");
    }

}

$statistics = [];       ///< Global statistics

main();

print_r($statistics);