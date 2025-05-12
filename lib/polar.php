<?php

/**
   \file polar.php
   \ingroup Lib
   \brief A collection of functions and data for working with polar coordinates
*/

include_once('cardinals.php');

/**
   \brief Mapping altitude to colors
   \details Note: each element is limited to 51-255 which results in 204 gradients between each band
*/
$colorMap =
[
  [ 'min' =>     1, 'max' =>  4000, 'operation' => 'g+', 'red' => 255, 'green' =>  51, 'blue' =>  51 ],
  [ 'min' =>  4001, 'max' =>  8000, 'operation' => 'r-', 'red' => 255, 'green' => 255, 'blue' =>  51 ],
  [ 'min' =>  8001, 'max' => 18000, 'operation' => 'b+', 'red' =>  51, 'green' => 255, 'blue' =>  51 ],
  [ 'min' => 18001, 'max' => 30000, 'operation' => 'g-', 'red' =>  51, 'green' => 255, 'blue' => 255 ],
  [ 'min' => 30001, 'max' => 45000, 'operation' => 'r+', 'red' =>  51, 'green' =>  51, 'blue' => 255 ]
];

/**
   \brief Determine the display color based on altitude
   \param $altitude The altitude to use for retrieving color
   \returns The color based on the altitude
*/
function altitudeColor($altitude)
{
   global $colorMap;

   // set the default altitude to the highest altitude
   $red = 255;
   $green = 0;
   $blue = 255;

   // return light grey if the altitude is zerp
   if($altitude == 0)
   {
      return 0xEAEAEA;
   }

   // cap the color scale at 45000 ft
   if($altitude > 45000)
   {
      return ($red << 16 | $green << 8 | $blue);
   }
   
   $startPoint = $altitude;

   // scan the list of altitude ranges
   foreach($colorMap as $rangeSet)
   {
      if($startPoint >= $rangeSet['min'] && $startPoint <= $rangeSet['max'])
      {
         // set the default color value for this altitude range
         $red   = $rangeSet['red'];
         $green = $rangeSet['green'];
         $blue  = $rangeSet['blue'];

         // scale the altitude to the altitude range and color offset
         $offsetAltitude = $altitude - $rangeSet['min'];
         $offsetRange = $rangeSet['max'] - $rangeSet['min'];
         $offsetPoint = scaleRangeValue($offsetAltitude, 0, $offsetRange, 0, 204);

         // adjust the color elements based on the altitude range operation
         switch($rangeSet['operation'])
         {
            case 'r+':
               $red += $offsetPoint;
               break;
            case 'r-':
               $red -= $offsetPoint;
               break;
            case 'g+':
               $green += $offsetPoint;
               break;
            case 'g-':
               $green -= $offsetPoint;
               break;
            case 'b+':
               $blue += $offsetPoint;
               break;
            case 'b-':
               $blue -= $offsetPoint;
               break;
            }
      }
   }

   return ($red << 16 | $green << 8 | $blue);
}

/**
   \brief Create the altitude legend as HTML text
*/
function altitudeLegend()
{
   $row1 = '';
   $row2 = '';

   $row1 .= "<tr>\n";
   $row2 .= "<tr>\n";

   for($index = 0; $index <= 45; $index++)
   {
      $row1 .= sprintf("   <th style=\"width:%dpx\">%s</th>\n",
                       ($index % 5 == 0) ? 15 : 5,
                       ($index % 5 == 0) ? $index : '&nbsp;');
      $row2 .= sprintf("   <td style=\"background:#%X\">&nbsp;</td>\n",
                       altitudeColor($index * 1000));
   }

   $row1 .= "</tr>\n";
   $row2 .= "</tr>\n";

   return sprintf("<table cdllpadding=\"2\" cellspacing=\"1\">\n%s%s</table>\n", $row1, $row2);
}

/**
   \brief convert coordinates from polar to cartesian
   \param $coord Contains theta and distance for polar coordinate
   \returns Cartesian coordinate (x,y) for the polar coordinate
*/
function polar2cart($coord)
{
   // rotate the plot by 101.25 degrees so that North is at the top
   $radians = deg2rad($coord['theta'] - 90 - (22.5 / 2));
   $x = $coord['radius'] * cos($radians);
   $y = $coord['radius'] * sin($radians);

   return [ 'x' => $x, 'y' => $y ];
}

/**
   \brief Scale a value from one range to another
   \param $value The value to scale
   \param $fromLow The beginning of the source range
   \param $fromHigh The end of the source range
   \param $toLow The beginning of the target range
   \param $toLow The end of the target range
   \returns The value scaled from the source range to the target range
*/
function scaleRangeValue($value, $fromLow, $fromHigh, $toLow, $toHigh)
{
   $fromRange = $fromHigh - $fromLow;
   $toRange = $toHigh - $toLow;
   $scaleFactor = $toRange / $fromRange;

   $tmpValue = $value - $fromLow;
   $tmpValue *= $scaleFactor;

   return $tmpValue + $toLow;
}

/**
   \brief Create the polar map with all of the sectors
   \param $width The width of each band (default 50)
   \param $bands The number of bands to create (default 6)
   \returns Initialized polar map
*/
function createPolarMap($width=50, $bands=6)
{
   $result = [];

   for($cardinal = 0; $cardinal < 16; $cardinal++)
   {
      $sectorStart = $cardinal * 22.5;

      for($band = 0, $bandStart = 0;
          $band < $bands;
          $band++, $bandStart+=$width)
      {
         $result[$cardinal][$band]['start']   = [ 'theta' => $sectorStart,        'radius' => $bandStart ];
         $result[$cardinal][$band]['radius1'] = [ 'theta' => $sectorStart,        'radius' => $bandStart + $width ];
         $result[$cardinal][$band]['arc']     = [ 'theta' => $sectorStart + 22.5, 'radius' => $bandStart + $width];
         $result[$cardinal][$band]['radius2'] = [ 'theta' => $sectorStart + 22.5, 'radius' => $bandStart ];
      }
   }
   return $result;
}

/**
   \brief Output the graph as SVG
   \param $content String containing graph-specific content
   \param $centerX The X coordinate for the center of the graph in the viewport
   \param $centerY The Y coordinate for the center of the graph in the viewport
   \param $width The width of each ring (default 50)
   \param $rings The number of rings (default 6)
   \returns String containing markup for the complete SVG
*/
function createPolarSVG($content, $centerX, $centerY, $width=50, $rings=6)
{
   $ret = '';

   $ret .= sprintf("<svg width=\"%d\" height=\"%d\" xmlns=\"http://www.w3.org/2000/svg\">\n", $centerX * 2, $centerY * 2);

   for($index = 0, $radius = $width;
       $index < $rings + 1;
       $index++, $radius += $width)
   {
      $ret .= sprintf("<circle cx=\"%d\", cy=\"%d\" r=\"%d\" stroke=\"black\" stroke-width=\"1\" fill=\"transparent\"/>\n",
                      $centerX,
                      $centerY,
                      $radius);
   }

   $ret .= $content;

   $ret .= sprintf("</svg>\n");

   return $ret;   
}
