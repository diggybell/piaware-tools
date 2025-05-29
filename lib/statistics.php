<?php

/**
    \file statistics.php
    \brief Supports statistics collection/capture for automated scripts
    \ingroup Lib
*/

include_once('autoload.php');
include_once('autoconfig.php');
include_once('config.php');

use \DigTech\Logging\Logger as Logger;

$runtimeStatistics =
[
    'system-app-name'   => $argv[0],
    'system-app-path'   => getcwd(),
    'system-start-time' => date('Y-m-d H:i:s'),
    'system-end-time'   => '',
]; ///< Global runtime statistics array
register_shutdown_function('outputStatistics');

// update runtime statistics
$globalRuntimeFile = sprintf("%ssystem-stats-%s.json", RUNTIME_PATH, $argv[0]);
file_put_contents($globalRuntimeFile, json_encode($runtimeStatistics, JSON_PRETTY_PRINT));

function outputStatistics()
{
    global $runtimeStatistics;
    global $globalRuntimeFile;

    $runtimeStatistics['system-end-time'] = date('Y-m-d H:i:s');

    $elapsed = strtotime($runtimeStatistics['system-end-time']) - strtotime($runtimeStatistics['system-start-time']);
    
    $hour = $elapsed / 3600;
    $minute = ($elapsed - ($hour * 3600)) / 60;
    $second = $elapsed % 60;
    $str = sprintf("%02d:%02d:%02d", $hour, $minute, $second);
    
    $runtimeStatistics['system-elapsed-time'] = $str;

    ksort($runtimeStatistics);

    file_put_contents($globalRuntimeFile, json_encode($runtimeStatistics, JSON_PRETTY_PRINT));

    Logger::log("Execution Statistics for %s\n", $runtimeStatistics['system-app-name']);
    foreach($runtimeStatistics as $name => $value)
    {
        Logger::log("    %-25s - %s\n", $name, $value);
    }
}