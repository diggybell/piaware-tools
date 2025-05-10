<?php

include_once('../lib/split-flights.php');

$ret = splitFlights('../aircraft-history.json');
foreach($ret as $icao => $flights)
{
    foreach($flights as $index => $flight)
    {
        $length = calculateTrackLength($flight);
        printf("%s - Flight: %d - Length: %4d - Positions: %3d\n", $icao, $index, $length, count($flight));
    }
}
