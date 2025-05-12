<?php

/**
    \file cardinals.php
    \ingroup Lib
    \brief A collections of functions for working in Cardinal bearings
 */

/**
    \brief Global reference data for cardinal directions
*/
$cardinalLabels = [
    'N','NNE','NE', 'ENE',
    'E', 'ESE', 'SE', 'SSE',
    'S', 'SSW', 'SW', 'WSW',
    'W', 'WNW', 'NW', 'NNW'
];

/**
    \brief Global reference data for range rings
*/
$rangeRingLabels = [
    '50nm',
    '100nm',
    '150nm',
    '200nm',
    '250nm',
    '250nm+'
];

/**
    \brief Get the number of cardinal sectors
    \returns The number of cardinal directions
*/
function getCardinalCount()
{
    global $cardinalLabels;
    return count($cardinalLabels);
}

/**
    \brief Get the number of range rings
    \returns The number of range rings
*/
function getRangeRingCount()
{
    global $rangeRingLabels;
    return count($rangeRingLabels);
}


/**
    \brief Find the distance and bearing from the receiver to the aircraft
    \param $lat1 Latitude of first position
    \param $lon1 Longitude of first position
    \param $lat2 Latitude of second position
    \param $lon2 Longitude of second position
    \returns Array
    \details This function will provide multiple pieces of data related to two points. The results are returned in an array
    with the keys as defined below.
    \retval nm Distance in nautical miles
    \retval km Distance in kilometers
    \retval bearing The bearing in degrees from the ADSB receiver
    \retval cardinal The Cardinal direction from the ADSB receiver
    \retval ring The range ring the position is in
*/
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

/**
    \brief Find the distance between two points
    \param $lat1 Latitude of first position
    \param $lon1 Longitude of first position
    \param $lat2 Latitude of second position
    \param $lon2 Longitude of second position
    \returns Distance between the two points
*/
function getDistance($lat1, $lon1, $lat2, $lon2)
{
    $theta = $lon1 - $lon2; 
    $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta)); 
    $dist = acos($dist); 
    $dist = rad2deg($dist); 

    return $dist;
}

/**
    \brief Get the bearing from the receiver to the aircraft
    \param $lat1 Latitude of first position
    \param $lon1 Longitude of first position
    \param $lat2 Latitude of second position
    \param $lon2 Longitude of second position
    \returns Distance between the two points
*/
function getBearing($lat1, $lon1, $lat2, $lon2)
{
	$bearing = (rad2deg(atan2(sin(deg2rad($lon2) - deg2rad($lon1)) * cos(deg2rad($lat2)), cos(deg2rad($lat1)) *
               sin(deg2rad($lat2)) - sin(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($lon2) - deg2rad($lon1)))) + 360) % 360;
    return $bearing;
}

// 
/**
    \brief Get the Cardinal sector from the degrees
    \param $degrees The number of degrees
    \returns The Cardinal direction
*/
function getCardinal($degrees)
{
    global $cardinalLabels;

    $index = fmod((($degrees) / (360 / getCardinalCount())), getCardinalCount());

    return $cardinalLabels[$index];
}

/**
    \brief Convert a cardinal index to the cardinal name
    \param $index The Cardinal index to convert
    \returns The label for the Cardinal index (N,S,E,W, etc...)
*/
function getCardinalLabel($index)
{
    global $cardinalLabels;

    return $cardinalLabels[$index];
}


/**
    \brief Get the range ring index based on distance from receiver and ring width (default 50)
    \param $nauticalMiles The distance being compared
    \param $width The width of each rang ring (default 50)
    \returns The index of the range ring for the distance
*/
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

/**
    \brief Get the range ring label from the range ring index
    \param $ring The index of the range ring to retrieve label for
    \returns Label for range ring based on $ring (50nm, 100nm, ...)
*/
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
