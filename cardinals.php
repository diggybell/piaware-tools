<?php

// global reference data for cardinal directions
$cardinalLabels = [
    'N','NNE','NE', 'ENE',
    'E', 'ESE', 'SE', 'SSE',
    'S', 'SSW', 'SW', 'WSW',
    'W', 'WNW', 'NW', 'NNW'
];
// global reference data for range rings
$rangeRingLabels = [ '50nm', '100nm', '150nm', '200nm', '250nm', '250nm+' ];

//
// get the number of cardinal sectors
//
function getCardinalCount()
{
    global $cardinalLabels;
    return count($cardinalLabels);
}

//
// get the number of range rings
//
function getRangeRingCount()
{
    global $rangeRingLabels;
    return count($rangeRingLabels);
}

//
// find the distance and bearing from the receiver to the aircraft
//
function getDistanceAndBearing($lat1, $lon1, $lat2, $lon2)
{ 
    // get the values from the coordinates
    $distance = getDistance($lat1, $lon1, $lat2, $lon2);
    $bearingDeg = getBearing($lat1, $lon1, $lat2, $lon2);
	
    // convert units as required
    $miles = $distance * 60;
    $km = round($miles * 1.609344); 
	$miles = round($miles);
    
    // get bearing and range classifiers
	$bearingWR = getCardinal($bearingDeg);
    $rangeRing = getRangeRing($miles);

    return [ 'nm' => $miles, 'km' => $km, 'bearing' => $bearingDeg, 'cardinal' => $bearingWR, 'ring' => $rangeRing];
}

//
// get the distance between two points using haversine formula
//
function getDistance($lat1, $lon1, $lat2, $lon2)
{
    $theta = $lon1 - $lon2; 
    $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta)); 
    $dist = acos($dist); 
    $dist = rad2deg($dist); 

    return $dist;
}

//
// get the bearing from the receiver to the aircraft
//
function getBearing($lat1, $lon1, $lat2, $lon2)
{
	$bearing = (rad2deg(atan2(sin(deg2rad($lon2) - deg2rad($lon1)) * cos(deg2rad($lat2)), cos(deg2rad($lat1)) *
               sin(deg2rad($lat2)) - sin(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($lon2) - deg2rad($lon1)))) + 360) % 360;
    return $bearing;
}

//
// get the cardinal sector from the degrees
//
function getCardinal($degrees)
{
    global $cardinalLabels;

    $index = fmod((($degrees) / (360 / getCardinalCount())), getCardinalCount());

    return $cardinalLabels[$index];
}

//
// convert a cardinal index to the cardinal name
//
function getCardinalLabel($index)
{
    global $cardinalLabels;

    return $cardinalLabels[$index];
}

//
// get the range ring index based on distance from receiver and ring width (default 50)
//
function getRangeRing($nauticalMiles, $width=50)
{
    $ring = (int)($nauticalMiles / $width);

    // make sure 'width' distance are in proper range for the last mile of each ring
    if(($ring * $width == $nauticalMiles) &&
       $ring > 0)
    {
        $ring--;
    }
    // if the calculated range ring is too high, use the last range ring.
    if($ring > getRangeRingCount())
    {
        $ring = getRangeRingCount() - 1;
    }

    return $ring;
}

//
// get the range ring label from the range ring index
//
function getRangeRingLabel($ring)
{
    global $rangeRingLabels;

    // if the calculated range ring is too high, use the last range ring.
    if($ring > getRangeRingCount())
    {
        $ring = getRangeRingCount() - 1;
    }

    return $rangeRingLabels[$ring];
}
