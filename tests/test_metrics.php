<?php

include_once('../Metric.php');

$m = new MetricSet();

$m->addMetric('altitude');

$m->updateMetric('altitude', 1000);
$m->updateMetric('altitude', 7000);
$m->updateMetric('altitude', 5000);
$m->updateMetric('altitude', 2000);
$m->updateMetric('altitude', 9000);
$m->updateMetric('altitude', 2000);
$m->updateMetric('altitude', 8000);

$m->addMetric('speed');

$m->updateMetric('speed', 150);
$m->updateMetric('speed', 150);
$m->updateMetric('speed', 150);
$m->updateMetric('speed', 150);
$m->updateMetric('speed', 150);
$m->updateMetric('speed', 160);
$m->updateMetric('speed', 170);

printf("Sum: %5d - Cnt: %5d - Min: %5d - Max: %5d - Avg: %8.2f - ES: %5d - SD: %8.2f\n",
       $m->getMetric('altitude')->total(),
       $m->getMetric('altitude')->count(),
       $m->getMetric('altitude')->min(),
       $m->getMetric('altitude')->max(),
       $m->getMetric('altitude')->average(),
       $m->getMetric('altitude')->extremeSpread(),
       $m->getMetric('altitude')->standardDeviation());

printf("Sum: %5d - Cnt: %5d - Min: %5d - Max: %5d - Avg: %8.2f - ES: %5d - SD: %8.2f\n",
       $m->getMetric('speed')->total(),
       $m->getMetric('speed')->count(),
       $m->getMetric('speed')->min(),
       $m->getMetric('speed')->max(),
       $m->getMetric('speed')->average(),
       $m->getMetric('speed')->extremeSpread(),
       $m->getMetric('speed')->standardDeviation());

printf("%s\n", $m->toJSON());