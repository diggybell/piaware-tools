<?php

/**
    \file split-flights.php
    \ingroup Lib
    \brief This file contains functions that can be used to split long aircraft tracks into flights
*/

include_once('../lib/config.php');
include_once('../lib/cardinals.php');

/**
    \brief Expanded compact date/time to formatted ate/time (YYYYMMDD-HHMMSS -> YYYY-MM-DD HH:MM:SS
    \param $key Date/Time without punctuation
    \returns Date/Time formatted with punctuation
*/
function splitPositionKey($key)
{
    $ret = sprintf("%s-%s-%s %s:%s:%s",
                   substr($key, 0, 4),
                   substr($key, 4, 2),
                   substr($key, 6, 2),
                   substr($key, 9, 2),
                   substr($key, 11, 2),
                   substr($key, 13, 2));
    return $ret;
}

/**
    \brief Split aircraft track where there is a difference of FLIGHT_BOUNDARY minutes between positions
    \param $positions The list of pisitions to split if needed
    \param $splitTime The number of minutes between positions to be used to split the aircraft track
    \returns Array containing one or more flights
*/
function splitTrack($positions, $splitTime=FLIGHT_BOUNDARY)
{
    $flightList = [];
    $flightIndex = 0;
    $positionIndex = array_keys($positions);
    for($current = 0, $next = 1;
        $next < count($positionIndex);
        $current++, $next++)
    {
        $flightList[$flightIndex][$positionIndex[$current]] = $positions[$positionIndex[$current]];
        $first  = strtotime(splitPositionKey($positionIndex[$current]));
        $second = strtotime(splitPositionKey($positionIndex[$next]));

        $difference = $second - $first;

        if($difference > $splitTime)
        {
            $flightIndex++;
        }
    }
    // be sure and ad the last (now current) position to the current track
    $flightList[$flightIndex][$positionIndex[$current]] = $positions[$positionIndex[$current]];

    return $flightList;
}

/**
    \brief Split aircraft tracks where there is a difference of FLIGHT_BOUNDARY minutes between positions
    \param $fileName The name of the aircraft history file to process
    \param $splitTime The number of minutes between positions to be used to split the aircraft track
*/
function splitTracks($fileName, $splitTime=FLIGHT_BOUNDARY)
{
    $ret = [];
    
    $history = json_decode(file_get_contents($fileName), true);
    foreach($history as $icao => $aircraft)
    {
        $ret[$icao] = splitTrack($aircraft['positions']);
    }

    return $ret;
}

/**
    \brief Calculate the track length for a flight
    \param $positions The list of positions to be calculated
    \returns The number of miles between the first and last positions in the track
*/
function calculateTrackLength($positions)
{
    // initialize the track length for this aircraft
    $trackLength = 0;
    // get the list of keys so they can be accessed by a numeric index
    $positionKeys = array_keys($positions);

    // scan each pair of positions and calculate length
    for($from = 0, $to = 1;
        $to < count($positionKeys);
        $from++, $to++)
    {
        // get the from/to positions
        $fromPos = $positions[$positionKeys[$from]];
        $toPos   = $positions[$positionKeys[$to]];
        // calculate and accumulate the distance
        $trackLength += (int)(getDistance($fromPos['latitude'], $fromPos['longitude'], $toPos['latitude'], $toPos['longitude']) * 60);
    }

    return $trackLength;
}

/**
    \brief Calculate the minumum altitude for a track
    \param $positions Array of positions from PiAware
    \returns The minimum altitude found in the track
*/
function calculateMinimumTrackAltitude($positions)
{
    $minimumAltitude = 0;

    foreach($positions as $position)
    {
        if($minimumAltitude == 0 || $position['alt_baro'] < $minimumAltitude)
        {
            $minimumAltitude = $position['alt_baro'];
        }
    }

    return $minimumAltitude;
}
