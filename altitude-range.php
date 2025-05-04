<?php

include_once('config.php');
include_once('cardinals.php');
include_once('polar.php');

//
// populate the polar map with the altitude/distance data
//
function populateAltitudeMap(&$map, $dataset)
{
   foreach($map as $cardinal => $sector)
   {
      $label = getCardinalLabel($cardinal);
      foreach($sector as $band => $coords)
      {
         $map[$cardinal][$band]['altitude'] = $dataset[$label][$band]['altitude'];
         $map[$cardinal][$band]['distance'] = $dataset[$label][$band]['distance'];
      }
   }
}

//
// generate the SVG code for the chart
//
function plotAltitudeChart($map, $centerX, $centerY, $width=50)
{
   $ret = '';

   foreach($map as $sector)
   {
      foreach($sector as $band => $coords)
      {
         $start   = polar2cart($coords['start']);
         $radius1 = polar2cart($coords['radius1']);
         $arc     = polar2cart($coords['arc']);
         $radius2 = polar2cart($coords['radius2']);

         $color = altitudeColor($coords['altitude']);

         $ret .= sprintf("<path d=\"M %.3f %.3f 
                                    L %.3f %.3f 
                                    A %d %d 22.5 0 1 %.3f %.3f 
                                    L %.3f %.3f 
                                    A %d %d -22.5 0 0 %.3f %.3f\" stroke=\"white\" stroke-width=\"2\" fill=\"#%06X\"><title>%d ft @ %d nm</title></path>\n",
                         $start['x'] + $centerX, $start['y'] + $centerY,
                         $radius1['x'] + $centerX, $radius1['y'] + $centerY,
                         ($band + 1) * $width, ($band + 1) * $width, $arc['x'] + $centerX, $arc['y'] + $centerY,
                         $radius2['x'] + $centerX, $radius2['y'] + $centerY,
                         $band * $width, $band * $width, $start['x'] + $centerX, $start['y'] + $centerY,
                         $color,
                         $coords['altitude'], $coords['distance']);
      }
   }

   return $ret;
}

//
// output table containing altitude values
//
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
         $ret .= sprintf("   <td align=\"right\">%d (%d)</td>\n",
                         $coords['altitude'],
                         $coords['distance']);
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

$map = createPolarMap(35, 6);
populateAltitudeMap($map, $dataset);
$plot = plotAltitudeChart($map, 250, 250, 35);
$svg = createPolarSVG($plot, 250, 250, 35, count($map[0]));

printf("<h3>Minimum Altitude By Bearing/Range<br>Date: %s</h3>\n", date('Y-m-d H:i'));
printf("<div>%s</div>\n", $svg);

printf("<h3>Altitude Legend (x 1,000 ft)</h3>\n");
printf("<div>%s</div>\n", altitudeLegend());

printf("<h3>Minimum Altitude Data Set (Altitude(Distance))</h3>\n");
$table = altitudeTable($map);
printf("<div>%s</div>\n", $table);
