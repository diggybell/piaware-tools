<?php

include_once('../lib/split-flights.php');

$ret = splitTracks('../aircraft-history.json');
foreach($ret as $icao => $flights)
{
    foreach($flights as $index => $flight)
    {
        $length = calculateTrackLength($flight);
        printf("%s - Flight: %d - Length: %4d - Positions: %3d From: %-19s %-19s\n", $icao, $index, $length, count($flight), array_key_first($flight), array_key_last($flight));
    }
}
