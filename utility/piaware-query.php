<?php

/**
    \file piaware-query.php
    \brief This is a general purpose query utility for viewing data
    \ingroup PiAwareTools
*/

include_once('autoload.php');
include_once('autoconfig.php');
include_once('split-flights.php');

use \DigTech\Logging\Logger as Logger;
use \DigTech\Database\MySQL as MyDB;
use \DigTech\Database\Record as Record;

define('IDENT_TYPE_ICAO', 1);
define('IDENT_TYPE_TAIL', 2);

function textOut($data, $title, $skipHidden=true)
{
    $labelLength = 0;
    foreach($data as $column => $value)
    {
        // skip 'hidden' columns
        if($column[0] == '_' && $skipHidden) continue;

        if(strlen($column) > $labelLength)
        {
            $labelLength = strlen($column);
        }
    }
    $labelLength++;

    $formatString = sprintf("%%%ds : %%s\n", $labelLength);

    printf("****************************** %s ******************************\n", $title);

    foreach($data as $column => $value)
    {
        // skip 'hidden' columns
        if($column[0] == '_' && $skipHidden) continue;

        printf($formatString, $column, $value);
    }
}

function tableOut($data, $title, $skipHidden=true)
{
    $labelWidths = [];
    $dataWidths = [];
    $columnWidths = [];
    $dataTypes = [];
    $formatStrings = [];

    // get max label lengths and preset data types to string
    foreach($data[0] as $label => $item)
    {
        // skip hidden columns
        if($label[0] == '_' && $skipHidden) continue;

        if(strlen($label) > $labelWidths[$label])
        {
            $labelWidths[$label] = strlen($label);
        }
        $dataTypes[$label] = 's';
    }
    // get max value lengths
    foreach($data as $record)
    {
        foreach($record as $label => $item)
        {
            // skip hidden columns
            if($label[0] == '_' && $skipHidden) continue;

            if(strlen($item) > $dataWidths[$label])
            {
                $dataWidths[$label] = strlen($item);
            }

            if(is_numeric($item))
            {
                $dataTypes[$label] = 'n';
            }
        }
    }

    // choose the larger size between labels and data
    foreach($labelWidths as $column => $length)
    {
        if($length > $dataWidths[$column])
        {
            $columnWidths[$column] = $labelWidths[$column];
        }
        else
        {
            $columnWidths[$column] = $dataWidths[$column];
        }
    }
    // build the set of format strings based on data type
    foreach($columnWidths as $column => $width)
    {
        if($dataTypes[$column] == 's')
        {
            $width *= -1;
        }
        $formatStrings[$column] = sprintf("%%%ds", $width);
    }

    // print the headers
    $labelString = '';
    $headerString = '';
    foreach($data[0] as $label => $value)
    {
        // skip hidden columns
        if($label[0] == '_' && $skipHidden) continue;

        if(strlen($labelString))
        {
            $labelString .= ' | ';
            $headerString .= '-+-';
        }
        $labelString .= sprintf($formatStrings[$label], $label);
        $headerString .= str_repeat('-', $columnWidths[$label]);
    }

    $separator = str_repeat('*', ((strlen($headerString) - strlen($title)) / 2) + 1);
    printf("%s %s %s\n", $separator, $title, $separator);

    printf("+ %s +\n", $headerString);
    printf("| %s |\n", $labelString);
    printf("+ %s +\n", $headerString);

    // now it's finally time to print the data
    foreach($data as $index => $row)
    {
        $outputString = '';
        foreach($row as $label => $value)
        {
            // skip hidden columns
            if($label[0] == '_') continue;

            if(strlen($outputString))
            {
                $outputString .= ' | ';
            }
            $outputString .= sprintf($formatStrings[$label], $value);
        }
        printf("| %s |\n", $outputString);
    }

    printf("+ %s +\n", $headerString);

}

function getAircraftInformation($db, $ident, $identType)
{
    $ret = [];

    $sql = sprintf("SELECT a.aircraft_seq AS \"_aircraft_seq\", a.icao_hex, a.n_number, a.adsb_category, dv.* FROM aircraft a LEFT OUTER JOIN faa.aircraft_details_view dv ON (a.icao_hex = dv.icao_hex) WHERE %s = '%s'",
                   ($identType == IDENT_TYPE_ICAO) ? 'a.icao_hex' : 'a.n_number',
                   $ident);
printf("%s\n", $sql);
    $res = $db->query($sql);
    if($res)
    {
        $ret[] = $db->fetch($res);
        print_r($ret);
    }
    else
    {
        printf("Query failed retrieving aircraft information (%s)\n", $db->error());
    }
    return $ret;
}

function getFlightInformation($db, $aircraftSeq, $date)
{
    $ret = [];

    $sql = sprintf("SELECT flight_seq AS \"_flight_seq\", flight, first_seen, last_seen, positions, distance FROM flight WHERE aircraft_seq = %d AND DATE(first_seen) = '%s' ORDER BY first_seen", $aircraftSeq, $date);

    $res = $db->query($sql);
    if($res)
    {
        while($row = $db->fetch($res))
        {
            $ret[] = $row;
        }
    }
    return $ret;
}

function getPositionInformation($db, $flightSeq)
{
    $ret = [];

    $sql = sprintf("SELECT track_seq AS \"_track_seq\", time_stamp, latitude, longitude, altitude, geo_altitude, heading, climb_rate, transponder, qnh, groundspeed, track, distance, bearing, cardinal, ring, rssi FROM flight_track WHERE flight_seq = %d", $flightSeq);

    $res = $db->query($sql);
    if($res)
    {
        while($row = $db->fetch($res))
        {
            $ret[] = $row;
        }
    }
    return $ret;
}
/**
   \brief Display usage and help information
*/
function usage()
{
    global $queryList;

?>
PiAware Tools - PiAware Query Utility
Copyright 2025 (c) - Diggy Bell

    --query=<query>        - The query to execute
    --icao=<hex>           - ICAO Hex Code (Mode-S Hex)
    --reg=<registration>   - Registration/Tail Number
    --date=<date>          - Query data for a specific date (default = current day)
    --json                 - Output results as JSON
    --help                 - Display this help

    Available Queries

<?php
    foreach($queryList as $option => $query)
    {
        printf("    %-22s - %s\n", $option, $query['title']);
    }
}

/**
    \brief Main entry point
*/
function main($ident, $identType, $query, $date, $jsonOut)
{
    $cfg = getGlobalConfiguration();
    $db = new MyDB\Connection();
    $config = $cfg->getSection('db-piaware');
    $db->configure($config);
    $config = $cfg->getSection('logging');
    Logger::configure($config);

    $jsonData = [];

    if(!$jsonOut)
    {
        printf("PiAware-Query Searching for %s = %s\n\n",
               ($identType == IDENT_TYPE_ICAO) ? 'ICAO Hex' : 'Registration',
               $ident);       
    }

    if($db->connect())
    {
        $aircraft = getAircraftInformation($db, $ident, $identType);
        if(count($aircraft))
        {
            $jsonData['aircraft'] = $aircraft[0];

            if(!$jsonOut)
            {
                textOut($aircraft[0], 'Aircraft');
            }
            $flights = getFlightInformation($db, $aircraft[0]['_aircraft_seq'], $date);
            if(count($flights))
            {
                foreach($flights as $flight)
                {
                    if(!$jsonOut)
                    {
                        textOut($flight, 'Flight');
                    }

                    $jsonData['flights'][$flight['_flight_seq']] = $flight;

                    $positions = getPositionInformation($db, $flight['_flight_seq']);
                    if(count($positions))
                    {
                        if(!$jsonOut)
                        {                        
                            tableOut($positions, 'Tracks');
                        }
                        foreach($positions as $position)
                        {
                            $jsonData['flights'][$flight['_flight_seq']]['tracks'][$position['_track_seq']] = $position;
                        }
                    }
                }
            }
            else
            {
                Logger::error("Unable to retrieve flight information\n");
            }
        }
        else
        {
            Logger::error("Unable to retrieve aircraft information\n");
        }

        if($jsonOut)
        {
            printf("%s\n", json_encode($jsonData, JSON_PRETTY_PRINT));
        }

    }
    else
    {
        Logger::error("Unable to open database connection\n");
    }

}

$queryList =
[
    'Aircraft' => [ 'title' => 'Aircraft Information', 'script' => 'somefile.sql' ],
    'Flights'  => [ 'title' => 'Flight Information',   'script' => 'somefile.sql' ],
    'Receiver' => [ 'title' => 'Receiver Information', 'script' => 'somefile.sql' ]
];                                      ///< available queries
$shortOpts = '';                        ///< short command line options (not supported)
$longOpts = 
[
    'query:',
    'icao:',
    'reg:',
    'date:',
    'json',
    'help'
];                                      ///< long command line options
$opts = getopt($shortOpts, $longOpts);  ///< command line options
$ident = '';                            ///< aircraft identifier (icao hex or registration)
$identType = 0;                         ///< type of identifier (icao hex or registration)
$query = '';                            ///< the query option from the user
$date = date('Y-m-d');                  ///< date to retrieve date for
$jsonOut = false;                       ///< flag to indicate json output

if(isset($opts['help']))
{
    usage();
    exit;
}

if(isset($opts['icao']))
{
    $ident = $opts['icao'];
    $identType = IDENT_TYPE_ICAO;
}
elseif(isset($opts['reg']))
{
    $ident = $opts['reg'];
    $identType = IDENT_TYPE_TAIL;
}
else
{
    printf("You must enter a valid ICAO Hex Code or Registration/Tail Number\n");
}

$jsonOut = isset($opts['json']);

main($ident, $identType, $query, $date, $jsonOut);
