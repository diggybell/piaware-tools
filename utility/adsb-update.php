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
    \brief Update a flight and tracks in the database
    \param $db Database connection
    \param $aircraftSeq The sequence number for the aircraft
    \param $track The track to be updated
*/
function updateFlight($db, $aircraftSeq, $positions)
{
    global $statistics;

    $recFlight = new Record($db, 'flight', [ 'flight_seq' => 0]);

    $sql = sprintf("SELECT flight_seq FROM flight WHERE aircraft_seq = %d AND first_seen = '%s'", $aircraftSeq, splitPositionKey(array_key_first($positions)));
    $res = $db->query($sql);
    if($res)
    {
        $row = $db->fetch($res);

        $recFlight->set('flight_seq', $row['flight_seq']);
        $recFlight->read();

        $recFlight->set('aircraft_seq', $aircraftSeq);
        $recFlight->set('first_seen', splitPositionKey(array_key_first($positions)));
        $recFlight->set('last_seen', splitPositionKey(array_key_last($positions)));

        if($recFlight->get('flight_seq') == 0)
        {
            $ret = $recFlight->insert();
            $statistics['flight-insert']++;
        }
        else
        {
            $ret = $recFlight->update();
            $statistics['flight-update']++;
        }

        if($ret)
        {
            foreach($positions as $key => $position)
            {
                updateTrack($db, $recFlight->get('flight_seq'), splitPositionKey($key), $position);
            }
        }
    }
    else
    {
        Logger::log("Unable to retrieve flight\n");
    }
}

/*
    \brief Update a track in the database
    \param $db Database connection
    \param $FlightSeq The sequence number for the flight
    \param $timeStamp The timestamp key for this track
    \param $track The track to be updated
*/
function updateTrack($db, $flightSeq, $timeStamp, $track)
{
    global $statistics;

    $recFlightTrack = new Record($db, 'flight_track', [ 'track_seq' => 0]);

    $sql = sprintf("SELECT track_seq FROM flight_track WHERE flight_seq = %d AND time_stamp = '%s'", $flightSeq, $timeStamp);
    $res = $db->query($sql);
    if($res)
    {
        $row = $db->fetch($res);

        $recFlightTrack->set('track_seq', $row['track_seq']);
        $recFlightTrack->read();

        $recFlightTrack->set('flight_seq', $flightSeq);
        $recFlightTrack->set('time_stamp', $timeStamp);
        $recFlightTrack->set('latitude', $track['latitude']);
        $recFlightTrack->set('longitude', $track['longitude']);
        $recFlightTrack->set('altitude', (is_numeric($track['altitude'])) ? $track['altitude'] : 0);
        $recFlightTrack->set('groundspeed', $track['groundspeed']);
        $recFlightTrack->set('track', $track['track']);
        $recFlightTrack->set('distance', $track['distance']);
        $recFlightTrack->set('bearing', $track['bearing']);
        $recFlightTrack->set('cardinal', $track['cardinal']);
        $recFlightTrack->set('ring', $track['ring']);
        $recFlightTrack->set('rssi', $track['rssi']);

        if($recFlightTrack->get('track_seq') == 0)
        {
            $ret = $recFlightTrack->insert();
            $statistics['flight-track-insert']++;
        }
        else
        {
            $ret = $recFlightTrack->update();
            $statistics['flight-track-update']++;
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
                $aircraftSeq = updateAircraft($db, $aircraft);
                if($aircraftSeq)
                {
                    $tracks = splitTrack($aircraft['positions']);
                    foreach($tracks AS $key => $track)
                    {
                        updateFlight($db, $aircraftSeq, $track);
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

$statistics = [];       ///< Global statistics

main();

print_r($statistics);