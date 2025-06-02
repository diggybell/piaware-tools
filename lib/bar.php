<?php

/*
Bar Chart Notes

Data is structured in 1..n groups with each group containing data for the bars

Dimensions
1. Plot will be normalized into MaxX, MaxY coordinate system
2. Calculate GroupCount based on number of data elements at top level
3. Divide MaxX (minus 5% borders) by number of bars to get GroupWidth
4. Divide GroupWidth by GroupCount to get BarWidth
5. Determine maximum data value and scale to get MaxBarHeight
6. Scale values to MaxBarHeight (MaxY minus 5% borders)
*/

/**
   \brief Scale a value from one range to another
   \param $value The value to scale
   \param $fromLow The beginning of the source range
   \param $fromHigh The end of the source range
   \param $toLow The beginning of the target range
   \param $toHigh The end of the target range
   \returns The value scaled from the source range to the target range
*/
function scaleRangeValue($value, $fromLow, $fromHigh, $toLow, $toHigh)
{
   $fromRange = $fromHigh - $fromLow;
   $toRange = $toHigh - $toLow;
   $scaleFactor = $toRange / $fromRange;

   $tmpValue = $value - $fromLow;
   $tmpValue *= $scaleFactor;
//printf("Value: %.2f From: %.2f,%.2f To: %.2f,%.2f Scaled: %.2f (factor %.2f\n", $value, $fromLow, $fromHigh, $toLow, $toHigh, $tmpValue + $toLow, $scaleFactor);
   return $tmpValue + $toLow;
}

/**
    \brief Analyze the data set and build graph parameters array
    \param $data The dataset to be analyzed
    \param $maxX The maximum X value
    \param $maxY The maxinum Y value
    \param The margin to be allowed
    \returns Array containing graph parameters
*/
function getGraphParameters($data, $maxX, $maxY, $margin)
{
    $ret = [];

    $ret['max-x'] = $maxX;
    $ret['max-y'] = $maxY;
    $ret['margin'] = $margin;

    $ret['maxBarHeight'] = (int)($maxY - ($maxY * $margin));

    foreach($data as $action => $metric)
    {
        $ret['groupCount']++;
        $ret['y-labels'][$action] = $action;
        $barCount = 0;
        foreach($metric as $tag => $value)
        {
            $barCount++;
            $ret['x-labels'][$tag] = $tag;
            if($value > $ret['maxValue'])
            {
                $ret['maxValue'] = $value;
            }
        }
        if($barCount > $ret['barCount'])
        {
            $ret['barCount'] = $barCount;
        }
    }
    
    $ret['groupWidth'] = (int)(($maxX - ($maxX * $margin)) / $ret['barCount']);
    $ret['barWidth'] = (int)($ret['groupWidth'] / 2);

    return $ret;
}

/**
    \brief Create the bar graph
    \param $parameters The bar graph parameters array
    \param $data The dataset to be graphed
    \returns HTML string containing graph
*/
function createBarGraph($parameters, $data)
{
    $ret = '';
    $curX = 0;
    $xIndex = 0;
    $yIndex = 0;
    $groupX = 0;
    $groupY = 0;
    $startX = 0;
    $startY = 0;
    $colors = ['red', 'green', 'blue', 'purple'];

    $ret .= sprintf("<svg width=\"400\" height=\"400\" viewbox=\"0 -300 300 300\" preserveAspectRatio=\"xMaxYMin meet\" xmlns=\"http://www.w3.org/2000/svg\">\n", $parameters['max-x'] * 2, $parameters['max-y'] * 2);

    foreach($parameters['y-labels'] as $yLabel)
    {
        $startX = $yIndex * $parameters['barWidth'];
        foreach($parameters['x-labels'] as $xLabel)
        {
            $endX = $startX + $parameters['barWidth'];
            $startY = 0;
            $endY = scaleRangeValue($data[$yLabel][$xLabel], 0, $parameters['maxValue'], 0, $parameters['maxBarHeight']);

            $ret .= sprintf("<path d=\"M %.2f %.2f " .
                                      "L %.2f %.2f " .
                                      "L %.2f %.2f " .
                                      "L %.2f %.2f " .
                                      "L %.2f %.2f\" " .
                                      "fill=\"%s\"></path>\n",
                            $startX, $startY * -1,
                            $startX, $endY * -1,
                            $endX, $endY * -1,
                            $endX, $startY * -1,
                            $startX, $startY * -1,
                            $colors[$yIndex]);

            $xIndex++;
            $startX += $parameters['groupWidth'];
        }
        $groupX = $xIndex * $parameters['groupWidth'] * $parameters['barCount'];
        $yIndex++;
    }

   $ret .= sprintf("</svg>\n");

   return $ret;
}

/**
    \brief Output the full HTML page containing the graph
    \param $svg The graph to be output
    \returns String containing full HTML page with graph
*/
function outputHTML($svg)
{
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
<div class="graph-content">
{$svg}
</div>
</body>
</html>
HTML;

    return $ret;
}

$data = json_decode(file_get_contents('../piaware-statistics.json'), true);
$parameters = getGraphParameters($data['system-totals']['aircraft'], 300, 200, 0.05);
$svg = createBarGraph($parameters, $data['system-totals']['aircraft']);
$html = outputHTML($svg);
file_put_contents('bargraph.html', $html);