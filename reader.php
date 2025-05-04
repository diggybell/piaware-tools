<?php

include_once('config.php');
include_once('cardinals.php');
include_once('icao.php');

//
// get receiver information from receiver.json
//
function getReceiver()
{
   $receiver = json_decode(file_get_contents(DATAPATH . 'receiver.json'));
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
                  //printf("Adding aircraft data (%s)\n", $aircraft->hex);
                  $result = getDistanceAndBearing($receiver->lat, $receiver->lon, $aircraft->lat, $aircraft->lon);
                  if($aircraft->alt_baro > 0)
                  {
                     if($dataset[$result['cardinal']][$result['ring']]['altitude'] == 0 ||
                        $aircraft->alt_baro < $dataset[$result['cardinal']][$result['ring']]['altitude'])
                     {
                        $dataset[$result['cardinal']][$result['ring']]['altitude'] = $aircraft->alt_baro;
                        $dataset[$result['cardinal']][$result['ring']]['distance'] = $result['nm'];
                     }
                  }
               }
               else
               {
                  //printf("Skipping empty coordinates for %s\n", $aircraft->hex);
               }
            }
         }
         else
         {
            //printf("Aircraft not found or not an array\n");
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
   $aircraftList = [];

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
                  //printf("Adding aircraft data (%s)\n", $aircraft->hex);
                  $result = getDistanceAndBearing($receiver->lat, $receiver->lon, $aircraft->lat, $aircraft->lon);
                  $aircraftList[$aircraft->hex]['icao'] = $aircraft->hex;
                  $aircraftList[$aircraft->hex]['registry'] = icaoTailNumber($aircraft->hex);
                  $aircraftList[$aircraft->hex]['category'] = $aircraft->category;
                  $aircraftList[$aircraft->hex]['positions'][$timeStamp] =
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
                  ];
               }
               else
               {
                  //printf("Skipping empty coordinates for %s\n", $aircraft->hex);
               }
            }
         }
         else
         {
            //printf("Aircraft not found or not an array\n");
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
// main application code
//

// get command line options
$shortOpts = '';
$longOpts = [ 'altitude', 'aircraft'];
$opts = getopt($shortOpts, $longOpts);
if(isset($opts['altitude']))
{
   $mode = 'altitude';
}
elseif(isset($opts['aircraft']))
{
   $mode = 'aircraft';
}
else
{
   printf("Invalid option. --altitude or --aircraft required.\n");
   exit;
}

// setup initial data
$receiver = getReceiver();
$fileList = getOrderedFileList();

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

// pre-load the existing data from previous run
if(file_exists(ALTITUDE_FILE))
{
   $dataset = json_decode(file_get_contents(ALTITUDE_FILE), true);
}

switch($mode)
{
   case 'altitude': 
      $dataset = processCardinalAltitudeExtract($receiver, $fileList, $dataset);
      outputAltitudeResults($dataset);
      file_put_contents(ALTITUDE_FILE, json_encode($dataset, JSON_PRETTY_PRINT));
      break;
   case 'aircraft':
      $dataset = processAircraftExtract($receiver, $fileList);
      //
      // This is a large listing
      //outputAircraftResults($dataset);
      file_put_contents(AIRCRAFT_FILE, json_encode($dataset, JSON_PRETTY_PRINT));
}
