<?php

/*

1. Positions have flight_linked flag to indicate they have been linked
2. Build list of positions by aircraft in time_stamp order
3. Split the list of positions based on 30 minute gap
4. Check the last flight for the aircraft to see if this is a continuation
5. Create a new flight if required
6. Set the flight_linked flag for all affected positions
7. Set the flight_seq for all affected positions

Position Query

SELECT
	a.aircraft_seq,
    t.track_seq,
    t.time_stamp
FROM
	aircraft a
		INNER JOIN flight_track t ON (a.aircraft_seq = t.aircraft_seq)
WHERE
    t.flight_linked = 0
ORDER BY 
	a.aircraft_seq,
    t.time_stamp
;

Create Position List
$positions[time_stamp] = [ aircraft_seq, track_seq ]

$flights = splitTrack($positions);


*/

/**
    \file flight-builder.php
    \brief This script processes positions and builds virtual flights based on timestamps
    \ingroup PiAwareTools
*/

include_once('autoload.php');
include_once('autoconfig.php');
include_once('../lib/split-flights.php');

use \DigTech\Logging\Logger as Logger;
use \DigTech\Database\MySQL as MyDB;
use \DigTech\Database\Record as Record;

function getPositionList($db)
{
    $ret = [];
    $sql = "SELECT a.aircraft_seq, t.track_seq, t.time_stamp FROM aircraft a INNER JOIN flight_track t ON (a.aircraft_seq = t.aircraft_seq) WHERE t.flight_linked = 0 ORDER BY a.aircraft_seq, t.time_stamp";
    $res = $db->query($sql);
    if($res)
    {
        while($row = $db->fetch($res))
        {
            $ret[$row['aircraft_seq']][$row['time_stamp']] = [ 'track_seq' => $row['track_seq'] ];
        }
        $db->freeResult($res);
    }

    return $ret;
}

function getFlight($db, $aircraftSeq, $timeStamp)
{
    $ret = 0;

    $recFlight = new Record($db, 'flight', [ 'flight_seq' => 0 ]);

    $sql = sprintf("SELECT MAX(flight_seq) AS flight_seq FROM flight WHERE aircraft_seq = %d", $aircraftSeq);
    $res = $db->query($sql);
    if($res)
    {
        $row = $db->fetch($res);
        if($row['flight_seq'] > 0)
        {
            $recFlight->set('flight_seq', $row['flight_seq']);
            $recFlight->read();

            $first = strtotime($timeStamp);
            $second = strtotime($recFlight->get('last_seen'));
            $difference = $first - $second;

            if($first - $second < FLIGHT_BOUNDARY)
            {
                $ret = $recFlight->get('flight_seq');
            }
        }
        $db->freeResult($res);
    }

    return $ret;
}
/**
    \brief Main entry point
*/
function main()
{
    global $statistics;

    $cfg = getGlobalConfiguration();
    $db = new MyDB\Connection();
    $config = $cfg->getSection('db-piaware');
    $db->configure($config);
    $positionList = [];

    if($db->connect())
    {
        $positionList = getPositionList($db);
        printf("Count: %d\n", count($positionList));
        //print_r($positionList[array_key_first($positionList)]);

        foreach($positionList as $aircraftSeq => $positions)
        {
            $flights = splitTrack($positions);
            foreach($flights as $timeStamp => $tracks)
            {
                foreach($tracks as $timeStamp => $track)
                {
                    $flightSeq = getFlight($db, $aircraftSeq, $timeStamp);
                    $recFlight = new Record($db, 'flight', [ 'flight_seq' => $flightSeq ]);
                    $recFlight->read();
    
                    $recFlight->set('aircraft_seq', $aircraftSeq);
                    $recFlight->set('last_seen', $timeStamp);
                    if($flightSeq == 0)
                    {
                        $recFlight->set('first_seen', $timeStamp);
                    }
    
                    if($flightSeq == 0)
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
                        $recTrack = new Record($db, 'flight_track', [ 'track_seq' => 0 ]);
                        $recTrack->set('track_seq', $track['track_seq']);
                        if($recTrack->read())
                        {
                            $recTrack->set('flight_seq', $recFlight->get('flight_seq'));
                            $recTrack->set('flight_linked', 1);
    
                            $ret = $recTrack->update();
                            $statistics['track-update']++;
                        }
                        else
                        {
                            Logger::error("Failed to read flight track\n");
                        }
                    }
                }
            }
        }
    }
    else
    {
        Logger::error("Unable to open database connection\n");
    }

}

$statistics = [];                       ///< Global statistics
$shortOpts = '';                        ///< short command line options (not supported)
$longOpts = [ 'file:' ];                ///< long command line options
$opts = getopt($shortOpts, $longOpts);  ///< command line options

main();

print_r($statistics);