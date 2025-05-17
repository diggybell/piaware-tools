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

    $elapsed = strtotime($runtimeStatistics['system-end-time']) - strtotime($runtimeStatistics['system-start-time']);
    
    $hour = $elapsed / 3600;
    $minute = ($elapsed - ($hour * 3600)) / 60;
    $second = $elapsed % 60;
    $str = sprintf("%02d:%02d:%02d", $hour, $minute, $second);
    
    $runtimeStatistics['system-elapsed-time'] = $str;

    ksort($runtimeStatistics);

    Logger::log("Execution Statistics for %s\n", $runtimeStatistics['system-app-name']);
    foreach($runtimeStatistics as $name => $value)
    {
        Logger::log("    %-25s - %s\n", $name, $value);
    }
}