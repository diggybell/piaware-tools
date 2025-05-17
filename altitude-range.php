<?php

/**
   \file altitude-range.php
   \brief Creates the Altitude Range graph and HTML table
   \ingroup Intel
*/

include_once('lib/config.php');
include_once('lib/cardinals.php');
include_once('lib/polar.php');

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

//
// main application code
//

$dataset = json_decode(file_get_contents(ALTITUDE_FILE), true);
if(!is_array($dataset))
{
   printf("Error retriving history data\n");
   exit;
}

$map = createPolarMap(35, 6);
populateAltitudeMap($map, $dataset);
$svg = createPolarSVG($map, 250, 250, 35, count($map[0]));

printf("<h3>Minimum Altitude By Bearing/Range<br>Date: %s</h3>\n", date('Y-m-d H:i'));
printf("<div>%s</div>\n", $svg);

printf("<h3>Altitude Legend (x 1,000 ft)</h3>\n");
printf("<div>%s</div>\n", altitudeLegend());

printf("<h3>Minimum Altitude Data Set (Altitude(Distance))</h3>\n");
$table = altitudeTable($map);
printf("<div>%s</div>\n", $table);
