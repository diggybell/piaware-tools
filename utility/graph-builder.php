<?php

/**
   \file graph-builder.php
   \brief Utility to generate SVG files
   \ingroup Intel
*/

include_once('autoload.php');
include_once('autoconfig.php');
include_once('../lib/statistics.php');
include_once('../lib/config.php');
include_once('../lib/cardinals.php');
include_once('../lib/polar.php');

use \DigTech\Logging\Logger as Logger;
use \DigTech\Database\MySQL as MyDB;
use \DigTech\Database\Record as Record;

/*
   \brief Load minimum altitude data from the database
   \param $db Database connection
   \param $map Initialized polar map
   \param $date The date to retrieve data for
*/
function loadAltitudeData($db, &$map, $date)
{
   $sql = sprintf(
      "SELECT
         cardinal,
         ring,
         MIN(sort_key) AS sort_key
      FROM
         (SELECT
            cardinal,
            ring,
            CONCAT(LPAD(cardinal, 3, ' '), LPAD(ring, 2, ' '), LPAD(altitude, 6, ' '), LPAD(distance, 4, ' ')) AS sort_key
         FROM
            flight_track
         WHERE
            ValidAltitude(altitude, ring) AND
            DATE(create_date) = '%s') AS altitude_keys
      GROUP BY
         cardinal,
         ring
      ORDER BY
         cardinal,
         ring
      ",
      $date);

   if($db->connect())
   {
      $res = $db->query($sql);
      if($res)
      {
         while($row = $db->fetch($res))
         {
            list($cardinal, $ring, $altitude, $distance) = sscanf($row['sort_key'], "%s %d %d %d");

            $cardinalIndex = getCardinalIndex($cardinal);

            $map[$cardinalIndex][$ring]['altitude'] = $altitude;
            $map[$cardinalIndex][$ring]['distance'] = $distance;
            $map[$cardinalIndex][$ring]['color']    = altitudeColor($altitude);
            $map[$cardinalIndex][$ring]['label']    = sprintf("%d@%d", $altitude, $distance);
         }
      }
      else
      {
         Logger::log("Unable to load altitude data\n");
      }
   }
   else
   {
      Logger::log("Unable to connect to database\n");
   }
}

/*
   \brief Load maximum rssi data from the database
   \param $db Database connection
   \param $map Initialized polar map
   \param $date The date to retrieve data for
*/
function loadRSSIData($db, &$map, $date)
{
   $sql = sprintf(
      "SELECT
           cardinal,
           ring,
           MIN(sort_key) AS sort_key
       FROM
           (SELECT
              cardinal,
              ring,
              CONCAT(LPAD(cardinal, 3, ' '), LPAD(ring, 2, ' '), LPAD(rssi, 7, ' '), LPAD(distance, 4, ' ')) AS sort_key
            FROM
              flight_track
            WHERE
              DATE(create_date) = '%s') AS altitude_keys
       GROUP BY
           cardinal,
           ring
       ORDER BY
           cardinal,
           ring
      ",
      $date);

   if($db->connect())
   {
      $res = $db->query($sql);
      if($res)
      {
         while($row = $db->fetch($res))
         {
            list($cardinal, $ring, $rssi, $distance) = sscanf($row['sort_key'], "%s %d %f %d");

            $cardinalIndex = getCardinalIndex($cardinal);

            $rssiScaled = scaleRangeValue($rssi, -50, 0, 0, 100);

            $map[$cardinalIndex][$ring]['color']    = percentageColor($rssiScaled);
            $map[$cardinalIndex][$ring]['label']    = sprintf("%.1f@%d", $rssi, $distance);
            $map[$cardinalIndex][$ring]['rssi']     = $rssi;
            $map[$cardinalIndex][$ring]['distance'] = $distance;
         }
      }
      else
      {
         Logger::log("Unable to load altitude data\n");
      }
   }
   else
   {
      Logger::log("Unable to connect to database\n");
   }
}

/**
   \brief Output table containing graph values
   \param $map The polar data set to be output
*/
function outputTable($map)
{
   $ret = '';

   $ret .= "<table class=\"table table-striped\" cellspacing=\"0\" cellpadding=\"2\">\n";

   $ret .= "<tr>\n   <th class=\"text-start\">Cardinal</th>\n";
   for($index = 0; $index < count($map[0]); $index++)
   {
      $ret .= sprintf("   <th class=\"text-end\">%s</th>\n", getRangeRingLabel($index));
   }
   $ret .= "</tr>\n";

   foreach($map as $cardinal => $sector)
   {
      $ret .= "<tr>\n";

      $ret .= sprintf("   <td class=\"text-start\">%s</td>\n", getCardinalLabel($cardinal));

      foreach($sector as $band => $coords)
      {
         $ret .= sprintf("   <td class=\"text-end\">%s</td>\n",
                         $coords['label']);
      }
      $ret .= "</tr>\n";
   }
   $ret .= "</table>\n";

   return $ret;
}
function outputPage($content)
{
   $ret = '';

   $ret = <<<HTML
<!doctype html>
<html lang="en-US">
<head>
   <title>PiAware Tools Graphics Generator</title>
   <meta http-equiv="Pragma" content="no-cache">
   <meta http-equiv="Cache-Control" content="no-cache">
   <meta charset="utf-8">
</head>
<body>
{$content}
</body>
</html>
HTML;

   return $ret;
}
/**
   \brief Display usage and help information
*/
function usage()
{
?>
PiAware-Tools - Graph Generation Utility
Copyright 2025 (c) - Diggy Bell

Options
   --graph=<graph>   -  One of the available graphs
   --date=<date>     -  The date to extract graph data for
   --help            -  Display this help

Available Graphs
   altitude          - Minimum Altitude Seen in Range Rings
   rssi              - Maximum Signal Strength Seen in Range Rings

<?php
}

//
// main application code
//
function main($date, $graph)
{
   $cfg = getGlobalConfiguration();
   $db = new MyDB\Connection();
   $config = $cfg->getSection('db-piaware');
   $db->configure($config);
   $config = $cfg->getSection('logging');
   Logger::configure($config);

   $ringWidth = 25;
   $ringCount = 6;

   $map = createPolarMap($ringWidth, $ringCount);

   switch($graph)
   {
      case 'altitude':
         loadAltitudeData($db, $map, $date);
         break;
      case 'rssi':
         loadRSSIData($db, $map, $date);
         break;
      default:
         printf("Invalid graph\n");
         exit;
   }

   $svg = createPolarSVG($map, 200, 200, $ringWidth, $ringCount);
   $output = outputPage($svg);
   file_put_contents('../www/graphs/' . $graph . '-graph.html', $output);

   $table = outputTable($map);
   $output = outputPage($table);
   file_put_contents('../www/graphs/' . $graph . '-table.html', $output);
}

$shortOpts = '';
$longOpts =
[
   'graph:',
   'date:',
   'help'
];
$opts = getopt($shortOpts, $longOpts);

if(isset($opts['help']))
{
   usage();
   exit;
}

$graph = '';
$date = date('Y-m-d');

if(isset($opts['date']))
{
   $date = $opts['date'];
}
if(isset($opts['graph']))
{
   $graph = $opts['graph'];
}
else
{
   printf("ERROR: You must select a graph\n");
   usage();
   exit;
}

main($date, $graph);