<?php

include_once('../lib/config.php');
include_once('../lib/Metric.php');
include_once('../lib/cardinals.php');

function getAverageAltitudeRange()
{
    $dataset = json_decode(file_get_contents(ALTITUDE_FILE), true);
    $valueCount = count($dataset['N']);

    $averages = [];
    for($index = 0; $index < getRangeRingCount(); $index++)
    {
        $averages[$index] = new Metric();
    }
    foreach($dataset as $cardinal => $rings)
    {
        foreach($rings as $ring => $stats)
        {
            if($stats['altitude'] > 0)
            {
                $averages[$ring]->update($stats['altitude']);
            }
        }
    }

    return $averages;
}

$averages = getAverageAltitudeRange();
foreach($averages as $index => $ring)
{
    printf("Range: %-6s - Min: %5d - Max: %5d - Avg: %8.2f - ES: %5d\n",
           getRangeRingLabel($index),
           $ring->min(),
           $ring->max(),
           $ring->average(),
           $ring->extremeSpread());
}
