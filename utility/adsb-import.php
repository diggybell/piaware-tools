<?php

include_once('../lib/config.php');
include_once('../lib/cardinals.php');
include_once('../lib/icao.php');

//
// get receiver information from receiver.json
//
function getReceiver()
{
   $receiver = json_decode(file_get_contents(RECEIVER_FILE));
   return $receiver;
}

//
// get the list of history files and order them by timestamp before processing
//
function getOrderedFileList()
{
   $fileList = [];

   $dir = opendir(DATAPATH);
   if($dir)
   {
      while($entry = readdir($dir))
      {
         $fileInfo = pathinfo($entry);
         if(strtolower($fileInfo['extension']) == 'json' &&
            substr($entry, 0, 8) == 'history_')
         {
            $header = json_decode(file_get_contents(DATAPATH . $entry));
            $fileList[(string)$header->now] = $entry;
         }
      }
   }
   else
   {
      printf("Unable to open history directory\n");
      exit;
   }
   // sort the list of files by timestamp
   ksort($fileList);

   return $fileList;
}

//
// Validate the minimum altitude for each ring to make sure it is sane
//
function isValidAltitude($ring, $altitude)
{
   $minimums =
   [
      500,     // 50 nm
      1000,    // 100 nm
      5000,    // 150 nm
      10000,   // 200 nm
      15000,   // 250 nm
      20000    // 250+ nm
   ];

   if($altitude < $minimums[$ring])
   {

      return false;
   }

   return true;
}

//
// create a new dataset for cardinal/zone data
//
function initializeCardinalDataset()
{
   $dataset = [];
   $entry = [];
   
   // initialize the data set with zero values
   for($index = 0; $index < getRangeRingCount(); $index++)
   {
      $entry[$index] = [ 'altitude' => 0, 'distance' => 0 ];
   }
   for($index = 0; $index < getCardinalCount(); $index++)
   {
      $dataset[getCardinalLabel($index)] = $entry;
   }

   return $dataset;
}

//
// process the json history and extract minimum altitude/distance for each sector/zone
//
function processCardinalAltitudeExtract($receiver, $fileList, $dataset)
{
   foreach($fileList as $timeStamp => $fileName)
   {
      $content = json_decode(file_get_contents(DATAPATH . $fileName));
      if($content)
      {
         if(isset($content->aircraft) && is_array($content->aircraft))
         {
            foreach($content->aircraft as $aircraft)
            {
               if(isset($aircraft->hex) &&
                  isset($aircraft->lat) &&
                  isset($aircraft->lon))
               {
                  $result = getDistanceAndBearing($receiver->lat, $receiver->lon, $aircraft->lat, $aircraft->lon);
                  if($aircraft->alt_baro > 0)
                  {
                     if($dataset[$result['cardinal']][$result['ring']]['altitude'] == 0 ||
                        $aircraft->alt_baro < $dataset[$result['cardinal']][$result['ring']]['altitude'])
                     {
                        if(isValidAltitude($result['ring'], $aircraft->alt_baro))
                        {
                           $dataset[$result['cardinal']][$result['ring']]['altitude'] = $aircraft->alt_baro;
                           $dataset[$result['cardinal']][$result['ring']]['distance'] = $result['nm'];
                           $dataset[$result['cardinal']][$result['ring']]['icao']     = $aircraft->hex;
                        }
                     }
                  }
               }
               else
               {
               }
            }
         }
         else
         {
         }
      }
      else
      {
         printf("Failed reading JSON (%s)\n", $fileName);
      }
   }

   return $dataset;
}

//
// process the json history and extract aircraft and position information
//
function processAircraftExtract($receiver, $fileList)
{
   foreach($fileList as $timeStamp => $fileName)
   {
      $content = json_decode(file_get_contents(DATAPATH . $fileName));
      if($content)
      {
         if(isset($content->aircraft) && is_array($content->aircraft))
         {
            foreach($content->aircraft as $aircraft)
            {
               if(isset($aircraft->hex) &&
                  isset($aircraft->lat) &&
                  isset($aircraft->lon))
               {
                  $result = getDistanceAndBearing($receiver->lat, $receiver->lon, $aircraft->lat, $aircraft->lon);
          
                  $aircraftKey = strtoupper($aircraft->hex);
                  $timestampKey = date('Ymd-His', $timeStamp);

                  $aircraftList[$aircraftKey]['icao'] = $aircraftKey;
                  $aircraftList[$aircraftKey]['registry'] = icaoTailNumber($aircraftKey);
                  $aircraftList[$aircraftKey]['category'] = $aircraft->category;
                  $aircraftList[$aircraftKey]['country'] = getICAOCountry($aircraftKey);
                  $aircraftList[$aircraftKey]['tracklength'] = 0;
                  $aircraftList[$aircraftKey]['positions'][$timestampKey] =
                  [
                     'latitude'    => $aircraft->lat,
                     'longitude'   => $aircraft->lon,
                     'altitude'    => $aircraft->alt_baro,
                     'groundspeed' => $aircraft->gs,
                     'track'       => $aircraft->track,
                     'distance'    => $result['nm'],
                     'bearing'     => $result['bearing'],
                     'sector'      => $result['cardinal'],
                     'zone'        => $result['ring'],
                     'rssi'        => $aircraft->rssi,
                  ];
                  ksort($aircraftList[$aircraftKey]['positions']);
               }
               else
               {
               }
            }
         }
         else
         {
         }
      }
      else
      {
         printf("Failed reading JSON (%s)\n", $fileName);
      }
   }
   
   return $aircraftList;
}

//
// output the results of analyzing positions seen by cardinal direction, altitude, and distance
//
function outputAltitudeResults($dataset)
{
   $table = [];

   printf("Minimum Altitude in Sector/Ring\n");
   printf("Date: %s\n", date('Y-m-d'));

   printf('Cardinal  ');
   for($index = 0; $index < 6; $index++)
   {
      printf("%8s      ", trim(getRangeRingLabel($index)));
   }
   printf("\n");

   printf("--------      ----------   -----------   -----------   -----------   -----------  ------------\n");

   foreach($dataset as $cardinal => $altitudeList)
   {
      printf("%-10s", $cardinal);
      foreach($altitudeList as $altitude)
      {
         if($altitude['altitude'] > 0)
         {
            printf("%8d [%3d]", $altitude['altitude'], $altitude['distance']);
         }
         else
         {
            printf("%14s", '');
         }
      }
      printf("\n");
   }
}

//
// output the aircraft listing
//
function outputAircraftResults($aircraftList)
{
   foreach($aircraftList as $aircraft)
   {
      $firstIndex = array_key_first($aircraft['positions']);
      $lastIndex = array_key_last($aircraft['positions']);
      $distanceTracked = getDistance($aircraft['positions'][$firstIndex]['latitude'],
                                     $aircraft['positions'][$firstIndex]['longitude'],
                                     $aircraft['positions'][$lastIndex]['latitude'],
                                     $aircraft['positions'][$lastIndex]['longitude']);
      $distanceTracked *= 60;
      $firstItem = sprintf("%-8s - %-6s - %3s - %5d - ", $aircraft['icao'], $aircraft['registry'], $aircraft['category'], $distanceTracked);
      foreach($aircraft['positions'] as $position)
      {
         printf("%-35s %9.4f,%9.4f %5d %3d %3d %3s\n",
                $firstItem,
                $position['latitude'],
                $position['longitude'],
                $position['altitude'],
                $position['distance'],
                $position['bearing'],
                $position['sector']);
         $firstItem = '';
      }
   }
}

//
// calculate track length
//
function calculateTrackLength($aircraftList)
{
   foreach($aircraftList as $icao => $aircraft)
   {
      // initialize the track length for this aircraft
      $trackLength = 0;
      // get the list of keys so they can be accessed by a numeric index
      $positionKeys = array_keys($aircraft['positions']);

      // scan each pair of positions and calculate length
      for($from = 0, $to = 1;
          $to < count($positionKeys);
          $from++, $to++)
      {
         // get the from/to positions
         $fromPos = $aircraft['positions'][$positionKeys[$from]];
         $toPos   = $aircraft['positions'][$positionKeys[$to]];
         // calculate and accumulate the distance
         $trackLength += (int)(getDistance($fromPos['latitude'], $fromPos['longitude'], $toPos['latitude'], $toPos['longitude']) * 60);
      }
      // save the tracklength in the aircraftList
      $aircraftList[$icao]['tracklength'] = $trackLength;
   }

   return $aircraftList;
}

function usage()
{
?>
PiAware-Tools - Data Management Utility
Copyright 2025 (c) - Diggy Bell

Options
   --altitude  -  Process PiAware history files for lowest altitude in Cardinal/Range
   --aircraft  -  Process PiAware history files to build aircraft history
   --archive   -  Move current altitude and aircraft history to archive
   --help      -  Display this help

<?php
}
//
// main application code
//

// get command line options
$shortOpts = '';
$longOpts = [ 'altitude', 'aircraft', 'archive'];
$opts = getopt($shortOpts, $longOpts);
if(isset($opts['altitude']))
{
   $mode = 'altitude';
}
elseif(isset($opts['aircraft']))
{
   $mode = 'aircraft';
}
elseif(isset($opts['archive']))
{
   $mode = 'archive';
}
elseif(isset($opts['help']))
{
   usage();
   exit;
}
else
{
   printf("Error: Invalid option\n");
   usage();
   exit;
}

// setup initial data
$receiver = getReceiver();
$fileList = getOrderedFileList();

switch($mode)
{
   case 'altitude': 
      // load the existing data from previous run
      if(file_exists(ALTITUDE_FILE))
      {
         $dataset = json_decode(file_get_contents(ALTITUDE_FILE), true);
      }
      else
      {
         $dataset = initializeCardinalDataset();
      }
      
      $dataset = processCardinalAltitudeExtract($receiver, $fileList, $dataset);
      outputAltitudeResults($dataset);
      file_put_contents(ALTITUDE_FILE, json_encode($dataset, JSON_PRETTY_PRINT));
      break;
   case 'aircraft':
      // load the existing data from previous run
      if(file_exists(AIRCRAFT_FILE))
      {
         $dataset = json_decode(file_get_contents(AIRCRAFT_FILE), true);
      }
      
      $dataset = processAircraftExtract($receiver, $fileList);
      $dataset = calculateTrackLength($dataset);

      //
      // This is a large listing
      //outputAircraftResults($dataset);
      file_put_contents(AIRCRAFT_FILE, json_encode($dataset, JSON_PRETTY_PRINT));
      break;
   case 'archive':
      $fileName = str_replace('.json', '', AIRCRAFT_FILE);
      $fileName = $fileName . '-' . date('Y-m-d') . '.json';
      rename(AIRCRAFT_FILE, $fileName);

      $fileName = str_replace('.json', '', ALTITUDE_FILE);
      $fileName = $fileName . '-' . date('Y-m-d') . '.json';
      rename(ALTITUDE_FILE, $fileName);
      break;
}
