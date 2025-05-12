<?php

/**
   \file adsb-import.php
   \brief This script builds the altitude and aircraft history from the PiAware feeder's JSON data
   \ingroup ADSB
*/

include_once('../lib/config.php');
include_once('../lib/cardinals.php');
include_once('../lib/icao.php');

/**
   \brief Get receiver information from receiver.json
   \returns Object with receiver data including lat/lon for the receiver
*/
function getReceiver()
{
   $receiver = json_decode(file_get_contents(RECEIVER_FILE));
   return $receiver;
}

/**
   \brief Get the list of history files and order them by timestamp before processing
   \returns Array containing a list of the JSON history files sorted by the timestap contained in each file
*/
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

/**
   \brief Validate the minimum altitude for each ring to make sure it is sane
   \param $ring The range ring the altitude was seen in
   \param $altitude The altitude being validated
   \returns Whether or not the altitude is above a 'sane' minimum (see average-altitudes.php)
*/
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

/**
   \brief Create a new dataset for cardinal/zone data
   \returns An initialized Cardinal dataset
*/
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

/**
   \brief Process the json history and extract minimum altitude/distance for each sector/zone into dataset
   \param $receiver Information on the PiAware receiver
   \param $fileList The sorted list of PiAware history files
   \param $dataset An initialized or previously loaded polar dataset
   \returns The polar dataset populated with the minimum altitude values
*/
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

/**
   \brief Process the json history and extract aircraft and position information
   \param $receiver Information on the PiAware receiver
   \param $fileList The sorted list of PiAware history files
   \returns A list of all aircraft containing a list of positions
*/
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

/**
   \brief Output the results of analyzing positions seen by cardinal direction, altitude, and distance
   \param $dataset A polar data set populated with minimum altitude data
*/
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

/**
   \brief Output the aircraft listing
   \brief $aircraftList Array of aircraft and positions
*/
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

/**
   \brief Calculate track length
   \param $aircraftList A list of aircraft and positions
   \returns List of aircraft updated with track lengths for each aircraft
*/
function calculateFullTrackLength($aircraftList)
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

/**
   \brief Display usage and help information
*/
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

/**
    \brief Main entry point
    \param Command line parameters
*/
function main($opts)
{
   // load external data
   $receiver = getReceiver();
   $fileList = getOrderedFileList();
   $reportFlag = false;
   $mode = '';

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
   };

   if(isset($opts['report']))
   {
      $reportFlag = true;
   }

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
         if($reportFlag)
         {
            outputAltitudeResults($dataset);
         }
         file_put_contents(ALTITUDE_FILE, json_encode($dataset, JSON_PRETTY_PRINT));
         break;
      case 'aircraft':
         // load the existing data from previous run
         if(file_exists(AIRCRAFT_FILE))
         {
            $dataset = json_decode(file_get_contents(AIRCRAFT_FILE), true);
         }
         
         $dataset = processAircraftExtract($receiver, $fileList);
         $dataset = calculateFullTrackLength($dataset);

         //
         // This is a large listing
         if($reportFlag)
         {
            outputAircraftResults($dataset);
         }
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
}
// get command line options
$shortOpts = '';                    ///< Short command line parameters (not supported)
$longOpts = [
   'altitude',
   'aircraft',
   'archive',
   'report',
   'help'
];                                  ///< Long command line parameters
$opts = [];                         ///< Options from command line
$opts = getopt($shortOpts, $longOpts);

main($opts);