<?php

include_once('../lib/config.php');
include_once('../lib/cardinals.php');

//
// expanded compact date/time to formatted ate/time (YYYYMMDD-HHMMSS -> YYYY-MM-DD HH:MM:SS
//
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

//
// split flight tracks where there is a difference of FLIGHT_BOUNDARY minutes between positions
//
function splitFlights($fileName, $splitTime=FLIGHT_BOUNDARY)
{
    $ret = [];
    
    $history = json_decode(file_get_contents($fileName), true);
    foreach($history as $icao => $aircraft)
    {
        $flightList = [];
        $flightIndex = 0;
        $positionIndex = array_keys($aircraft['positions']);
        for($current = 0, $next = 1;
            $next < count($positionIndex);
            $current++, $next++)
        {
            $flightList[$flightIndex][$positionIndex[$current]] = $aircraft['positions'][$positionIndex[$current]];
            $first  = strtotime(splitPositionKey($positionIndex[$current]));
            $second = strtotime(splitPositionKey($positionIndex[$next]));

            $difference = $second - $first;

            if($difference > $splitTime)
            {
                $flightIndex++;
            }
        }
        // be sure and ad the last (now current) position to the current track
        $flightList[$flightIndex][$positionIndex[$current]] = $aircraft['positions'][$positionIndex[$current]];

        $ret[$icao] = $flightList;
    }

    return $ret;
}

//
// calculate the track length for a flight
//
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
