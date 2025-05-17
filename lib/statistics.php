<?php

/**
    \file statistics.php
    \brief Supports statistics collection/capture for automated scripts
    \ingroup Lib
*/

include_once('autoload.php');
include_once('autoconfig.php');

use \DigTech\Logging\Logger as Logger;

$runtimeStatistics =
[
    'system-app-name'   => $argv[0],
    'system-app-path'   => getcwd(),
    'system-start-time' => date('Y-m-d H:i:s'),
    'system-end-time'   => '',
]; ///< Global runtime statistics array
register_shutdown_function('outputStatistics');

function outputStatistics()
{
    global $runtimeStatistics;

    $runtimeStatistics['system-end-time'] = date('Y-m-d H:i:s');

    ksort($runtimeStatistics);

    Logger::log("Execution Statistics for %s\n", $runtimeStatistics['system-app-name']);
    foreach($runtimeStatistics as $name => $value)
    {
        Logger::log("%-20s - %s\n", $name, $value);
    }
}