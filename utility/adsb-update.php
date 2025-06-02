<?php

/**
    \file adsb-update.php
    \brief This script manages transfer of aircraft and position data from PiAware to the PiAware Tools database
    \ingroup PiAwareTools
*/

include_once('autoload.php');
include_once('autoconfig.php');
include_once('statistics.php');
include_once('split-flights.php');

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
    global $runtimeStatistics;

    $recAircraft = new Record($db, 'aircraft', [ 'aircraft_seq' => 0 ]);

    $sql = sprintf("SELECT aircraft_seq FROM aircraft WHERE icao_hex = '%s'", $aircraft['icao']);
    $res = $db->query($sql);
    if($res)
    {
        $row = $db->fetch($res);

        $recAircraft->set('aircraft_seq', $row['aircraft_seq']);
        $recAircraft->read();

        if(strlen($aircraft['icao']))     $recAircraft->set('icao_hex',         $aircraft['icao']);
        if(strlen($aircraft['registry'])) $recAircraft->set('n_number',         $aircraft['registry']);
        if(strlen($aircraft['category'])) $recAircraft->set('adsb_category',    $aircraft['category']);
        if(strlen($aircraft['country']))  $recAircraft->set('register_country', $aircraft['country']);

        if($recAircraft->get('aircraft_seq') == 0)
        {
            $ret = $recAircraft->insert();
            $runtimeStatistics['aircraft-insert']++;
        }
        else
        {
            $ret = $recAircraft->update();
            $runtimeStatistics['aircraft-update']++;
        }

        $db->freeResult($res);
    }
    else
    {
        Logger::error("Unable to locate aircraft record for %s[%s]\n", $aircraft['icao'], $aircraft['registry']);
    }

    return $recAircraft->get('aircraft_seq');
}

/**
    \brief Update a track in the database
    \param $db Database connection
    \param $aircraftSeq The sequence number for the aircraft
    \param $timeStamp The timestamp key for this track
    \param $track The track to be updated
*/
function updatePosition($db, $aircraftSeq, $timeStamp, $track)
{
    global $runtimeStatistics;

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

        if(strlen($track['flight']))    $recFlightTrack->set('flight',         $track['flight']);
        if(strlen($track['category']))  $recFlightTrack->set('adsb_category',  $track['category']);
        if(strlen($track['latitude']))  $recFlightTrack->set('latitude',       $track['latitude']);
        if(strlen($track['longitude'])) $recFlightTrack->set('longitude',      $track['longitude']);

        $recFlightTrack->set('altitude',        (is_numeric($track['altitude']))        ? $track['altitude']        : 0);
        $recFlightTrack->set('geo_altitude',    (is_numeric($track['geo_altitude']))    ? $track['geo_altitude']    : 0);
        $recFlightTrack->set('heading',         (is_numeric($track['heading']))         ? $track['heading']         : 0);
        $recFlightTrack->set('climb_rate',      (is_numeric($track['climb_rate']))      ? $track['climb_rate']      : 0);
        $recFlightTrack->set('transponder',     (is_numeric($track['transponder']))     ? $track['transponder']     : 0);
        $recFlightTrack->set('qnh',             (is_numeric($track['qnh']))             ? $track['qnh']             : 0);
        $recFlightTrack->set('groundspeed',     (is_numeric($track['groundspeed']))     ? $track['groundspeed']     : 0);
        $recFlightTrack->set('track',           (is_numeric($track['track']))           ? $track['track']           : 0);
        $recFlightTrack->set('rssi',            (is_numeric($track['rssi']))            ? $track['rssi']            : 0);

        // value of -1 indicates this value was missing
        $recFlightTrack->set('nic',             (is_numeric($track['nic']))             ? $track['nic']             : -1);
        $recFlightTrack->set('rc',              (is_numeric($track['rc']))              ? $track['rc']              : -1);
        $recFlightTrack->set('nac_p',           (is_numeric($track['nac_p']))           ? $track['nac_p']           : -1);
        $recFlightTrack->set('nac_v',           (is_numeric($track['nac_v']))           ? $track['nac_v']           : -1);
        $recFlightTrack->set('sil',             (is_numeric($track['sil']))             ? $track['sil']             : -1);
        $recFlightTrack->set('gva',             (is_numeric($track['gva']))             ? $track['gva']             : -1);
        $recFlightTrack->set('sda',             (is_numeric($track['sda']))             ? $track['sda']             : -1);

        $recFlightTrack->set('distance',        (is_numeric($track['distance']))        ? $track['distance']        : 0);
        $recFlightTrack->set('bearing',         (is_numeric($track['bearing']))         ? $track['bearing']         : 0);

        if(strlen($track['sil_type'])) $recFlightTrack->set('sil_type', $track['sil_type']);
        if(strlen($track['sector']))   $recFlightTrack->set('cardinal', $track['sector']);
        if(strlen($track['zone']))     $recFlightTrack->set('ring',     $track['zone']);

        if($recFlightTrack->get('track_seq') == 0)
        {
            $ret = $recFlightTrack->insert();
            $runtimeStatistics['aircraft-track-insert']++;
        }
        else
        {
            $ret = $recFlightTrack->update();
            $runtimeStatistics['aircraft-track-update']++;
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
    $config = $cfg->getSection('logging');
    Logger::configure($config);
    
    $aircraftList = [];

    if($db->connect())
    {
        $aircraftList = json_decode(file_get_contents($fileName), true);
        foreach($aircraftList as $icao => $aircraft)
        {
            //Logger::log("Processing %s[%s]\n", $aircraft['icao'], $aircraft['registry']);
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
                $runtimeStatistics['aircraft-processed']++;
            }
        }
    }
    else
    {
        Logger::error("Unable to open database connection\n");
    }

}

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
