<?php

/**
    \file adsb-update.php
    \brief This script manages transfer of aircraft and position data from PiAware to the PiAware Tools database
    \ingroup PiAwareTools
*/

include_once('autoload.php');
include_once('autoconfig.php');
include_once('../lib/split-flights.php');

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

    return $recAircraft->get('aircraft_seq');
}

/*
    \brief Update a track in the database
    \param $db Database connection
    \param $aircraftSeq The sequence number for the aircraft
    \param $timeStamp The timestamp key for this track
    \param $track The track to be updated
*/
function updatePosition($db, $aircraftSeq, $timeStamp, $track)
{
    global $statistics;

    $recFlightTrack = new Record($db, 'flight_track', [ 'track_seq' => 0]);

    $sql = sprintf("SELECT track_seq FROM flight_track WHERE aircraft_seq = %d AND time_stamp = '%s'", $aircraftSeq, $timeStamp);
    $res = $db->query($sql);
    if($res)
    {
        $row = $db->fetch($res);

        $recFlightTrack->set('track_seq', $row['track_seq']);
        $recFlightTrack->read();

        $recFlightTrack->set('aircraft_seq', $aircraftSeq);
        $recFlightTrack->set('time_stamp', splitPositionKey($timeStamp));
        $recFlightTrack->set('latitude', $track['latitude']);
        $recFlightTrack->set('longitude', $track['longitude']);
        $recFlightTrack->set('altitude', (is_numeric($track['altitude'])) ? $track['altitude'] : 0);
        $recFlightTrack->set('geo_altitude', (is_numeric($track['geo_altitude'])) ? $track['geo_altitude'] : 0);
        $recFlightTrack->set('heading', (is_numeric($track['heading'])) ? $track['heading'] : 0);
        $recFlightTrack->set('climb_rate', (is_numeric($track['climb_rate'])) ? $track['climb_rate'] : 0);
        $recFlightTrack->set('transponder', (is_numeric($track['transponder'])) ? $track['transponder'] : 0);
        $recFlightTrack->set('qnh', (is_numeric($track['qnh'])) ? $track['qnh'] : 0);

        $recFlightTrack->set('groundspeed', (is_numeric($track['groundspeed'])) ? $track['groundspeed'] : 0);
        $recFlightTrack->set('track', (is_numeric($track['track'])) ? $track['track'] : 0);
        $recFlightTrack->set('distance', (is_numeric($track['distance'])) ? $track['distance'] : 0);
        $recFlightTrack->set('bearing', (is_numeric($track['bearing'])) ? $track['bearing'] : 0);
        $recFlightTrack->set('cardinal', $track['sector']);
        $recFlightTrack->set('ring', $track['zone']);
        $recFlightTrack->set('rssi', (is_numeric($track['rssi'])) ? $track['rssi'] : 0);

        if($recFlightTrack->get('track_seq') == 0)
        {
            $ret = $recFlightTrack->insert();
            $statistics['aircraft-track-insert']++;
        }
        else
        {
            $ret = $recFlightTrack->update();
            $statistics['aircraft-track-update']++;
        }
    }
    else
    {
        Logger::log("Unable to retrieve track\n");
    }
}

/**
    \brief Main entry point
*/
function main($fileName)
{
    $cfg = getGlobalConfiguration();
    $db = new MyDB\Connection();
    $config = $cfg->getSection('db-piaware');
    $db->configure($config);
    $aircraftList = [];

    if($db->connect())
    {
        $aircraftList = json_decode(file_get_contents($fileName), true);
        foreach($aircraftList as $icao => $aircraft)
        {
            Logger::log("Processing %s[%s]\n", $aircraft['icao'], $aircraft['registry']);
            if(strlen($aircraft['icao']) && $aircraft['icao'][0] != '~')
            {
                $aircraftSeq = updateAircraft($db, $aircraft);
                if($aircraftSeq)
                {
                    foreach($aircraft['positions'] AS $key => $position)
                    {
                        updatePosition($db, $aircraftSeq, splitPositionKey($key), $position);
                    }
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

$statistics = [];                       ///< Global statistics
$fileName = '../aircraft-history.json'; ///< name of tile to process
$shortOpts = '';                        ///< short command line options (not supported)
$longOpts = [ 'file:' ];                ///< long command line options
$opts = getopt($shortOpts, $longOpts);  ///< command line options

if(isset($opts['file']))
{
    $fileName = $opts['file'];
}

if(file_exists($fileName))
{
    main($fileName);
}
else
{
    Logger::error("File does not exist (%s)\n", $fileName);
}

print_r($statistics);