<?php

/**
   \file altitude-range.php
   \brief Creates the Altitude Range graph and HTML table
   \ingroup Intel
*/

include_once('autoload.php');
include_once('autoconfig.php');
include_once('lib/config.php');
include_once('lib/cardinals.php');
include_once('lib/polar.php');

use \DigTech\Logging\Logger as Logger;
use \DigTech\Database\MySQL as MyDB;
use \DigTech\Database\Record as Record;

/**
   \brief Populate the polar map with the altitude/distance data
   \param $map The polar data set to be populated
   \param $dataset The calculated minimum altitudes
*/
function populateAltitudeMap(&$map, $dataset)
{
   foreach($map as $cardinal => $sector)
   {
      $label = getCardinalLabel($cardinal);
      foreach($sector as $band => $coords)
      {
         $map[$cardinal][$band]['color'] = altitudeColor($dataset[$label][$band]['altitude']);
         $map[$cardinal][$band]['label'] = sprintf("%sft(%snm)",
                                                   $dataset[$label][$band]['altitude'],
                                                   $dataset[$label][$band]['distance']);
      }
   }
}

/**
   \brief Output table containing altitude values
   \param $map The polar data set to be output
*/
function altitudeTable($map)
{
   $ret = '';

   $ret .= "<table border=\"1\" cellspacing=\"0\" cellpadding=\"2\">\n";

   $ret .= "<tr>\n   <th width=\"10%%\" align=\"left\">Cardinal</th>\n";
   for($index = 0; $index < count($map[0]); $index++)
   {
      $ret .= sprintf("   <th width=\"10%%\" align=\"right\">%s</th>\n", getRangeRingLabel($index));
   }
   $ret .= "</tr>\n";

   foreach($map as $cardinal => $sector)
   {
      $ret .= "<tr>\n";

      $ret .= sprintf("   <td align=\"left\">%s</td>\n", getCardinalLabel($cardinal));

      foreach($sector as $band => $coords)
      {
         $ret .= sprintf("   <td align=\"right\">%s</td>\n",
                         $coords['label']);
      }
      $ret .= "</tr>\n";
   }
   $ret .= "</table>\n";

   return $ret;
}

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

//
// main application code
//
function main($source, $date)
{
   $cfg = getGlobalConfiguration();
   $db = new MyDB\Connection();
   $config = $cfg->getSection('db-piaware');
   $db->configure($config);
   $config = $cfg->getSection('logging');
   Logger::configure($config);

   $ringWidth = 35;

   $map = createPolarMap($ringWidth, 6);

   switch($source)
   {
      case 'database':
         loadAltitudeData($db, $map, $date);
         break;
      case 'file':
         $dataset = json_decode(file_get_contents(ALTITUDE_FILE), true);
         if(!is_array($dataset))
         {
            printf("Error retriving history data\n");
            exit;
         }
         populateAltitudeMap($map, $dataset);
         break;
      default:
         printf("<h1>Invalid source</h1>\n");
         exit;
   }

   $svg = createPolarSVG($map, 250, 250, $ringWidth, count($map[0]));

   printf("<h3>Minimum Altitude By Bearing/Range<br>Date: %s</h3>\n", date('Y-m-d H:i'));
   printf("<div>%s</div>\n", $svg);

   printf("<h3>Altitude Legend (x 1,000 ft)</h3>\n");
   printf("<div>%s</div>\n", altitudeLegend());

   printf("<h3>Minimum Altitude Data Set (Altitude(Distance))</h3>\n");
   $table = altitudeTable($map);
   printf("<div>%s</div>\n", $table);
}

$date = date('Y-m-d');
if(isset($_GET['date']))
{
   $date = $_GET['date'];
}
$source = 'database';
if(isset($_GET['source']))
{
   $source = $_GET['source'];
}

main($source, $date);