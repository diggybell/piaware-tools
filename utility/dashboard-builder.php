<?php

/**
    \file dashboard-builder.php
    \brief Build static html segments
    \ingroup Intel
*/

include_once('config.php');

/**
    \brief Normalize dataset to ensure proper table construction
    \param $dataset The dataset to be normalized
    \returns Normalized dataset
*/
function normalizeDataSet(&$dataset)
{
    foreach($dataset as $row => $cols)
    {
        $rowIndex[$row] = 1;
        foreach($cols as $col => $value)
        {
            $colIndex[$col] = 1;
        }
    }
    $rowList = array_keys($rowIndex);
    $colList = array_keys($colIndex);

    ksort($rowList);
    ksort($colList);

    $newDataset = [];
    foreach($rowList as $row)
    {
        foreach($colList as $col)
        {
            if(isset($dataset[$row][$col]))
            {
                $newDataset[$row][$col] = $dataset[$row][$col];
            }
            else
            {
                $newDataset[$row][$col] = '&nbsp;';
            }
        }
    }

    $dataset = $newDataset;
}

/**
    \brief Create table markup for two dimensions
    \param $stats Table data to be output
    \returns HTML string for table
*/
function statsTable($stats)
{
    $ret = "<table class=\"table table-striped\">\n";
    $header = "<tr><th>&nbsp;</th>";
    $body   = "";
    $index = 0;

    normalizeDataSet($stats);
    foreach($stats as $action => $dailyTotals)
    {
        $body .= sprintf("<tr><td>%s</td>", $action);
        foreach($dailyTotals as $date => $count)
        {
            if($index == 0)
            {
                $header .= sprintf("<th class=\"text-end\">%s</th>", $date);
            }
            $body .= sprintf("<td class=\"text-end\">%s</td>", $count);
        }
        $index++;
        $body .= sprintf("</tr>\n");
    }

    $header .= sprintf("</tr>");

    $ret .= $header;
    $ret .= $body;
    $ret .= "</table>\n";

    return $ret;
}

/**
    \brief Create table markup for one dimension
    \param $stats Table data to be output
    \returns HTML string for table
*/
function detailTable($stats)
{
    $ret = "<table class=\"table table-striped\">\n";
    $header = "<tr>";
    $body   = "";

    $index = 0;
    foreach($stats as $level1 => $level1Details)
    {
        $body .= sprintf("<tr>");
        foreach($level1Details as $label => $value)
        {
            if($index == 0)
            {
                $header .= sprintf("<th class=\"text-start\">%s</th>", $label);
            }
            $body .= sprintf("<td class=\"text-start\">%s</td>", $value);
        }
        $index++;
        $body .= sprintf("</tr>\n");
    }

    $header .= sprintf("</tr>\n");

    $ret .= $header;
    $ret .= $body;
    $ret .= "</table>\n";

    return $ret;
}

/**
    \brief Output the table data with full HTML page
    \param $content The content element being output
    \returns HTML content as a string
*/
function outputPage($content)
{
   $ret = '';

   $ret = <<<HTML
<!doctype html>
<html lang="en-US">
<head>
   <title>PiAware Tools Content Generator</title>
   <meta http-equiv="Pragma" content="no-cache">
   <meta http-equiv="Cache-Control" content="no-cache">
   <meta charset="utf-8">
</head>
<body>
{$content}
</body>
</html>

HTML;

   return $ret;
}

/*
    \brief Get the statistics for running processes
    \returns Table market for process status
*/
function getProcessStats()
{
    $processStats = [];
    $fileList = [];

    $dir = opendir(RUNTIME_PATH);
    if($dir)
    {
        while($entry = readdir($dir))
        {
            if(!is_dir(RUNTIME_PATH . $entry))
            {
                $fileList[] = $entry;
            }
        }
    }

    foreach($fileList as $fileName)
    {
        $stats = json_decode(file_get_contents(RUNTIME_PATH . $fileName), true);

        $details = [];
        foreach($stats as $tag => $value)
        {
            if(substr($tag, 0, 6) !== 'system')
            {
                $details[$tag] = $value;
            }
        }

        $detailsString = '';
        if(is_array($details))
        {
            foreach($details as $tag => $name)
            {
                if(strlen($detailsString))
                {
                    $detailsString .= '<br />';
                }
                $detailsString .= sprintf("%s = %d\n", $tag, $name);
            }
        }
        $processStats[] =
        [
            "Application" => $stats['system-app-name'],
            'Start' => $stats['system-start-time'],
            'End' => $stats['system-end-time'],
            'Elapsed' => $stats['system-elapsed-time'],
            'Details' => $detailsString
        ];
    }

    return statsTable($processStats);
}
/**
   \brief Display usage and help information
*/
function usage()
{
?>
PiAware-Tools - Graph Generation Utility
Copyright 2025 (c) - Diggy Bell

Options
   --section=<section>  -  One of the available graphs
   --help               -  Display this help

Available Graphs
   aircraft         - Total aircraft processed by day
   flights          - Total flights processed by day
   tracks           - Total flight tracks proced by day
<?php
}

$shortOpts = '';
$longOpts =
[
    'section:',
    'help'
];
$opts = getopt($shortOpts, $longOpts);

if(!isset($opts['section']))
{
    printf("ERROR: --section is required\n");
    usage();
    exit;
}
if(isset($opts['help']))
{
    usage();
    exit;
}
$section = $opts['section'];

$stats = json_decode(file_get_contents('../piaware-statistics.json'), true);
switch($section)
{
    case 'totals':
        $content = statsTable($stats['system-totals']['totals']);
        break;
    case 'process':
        $content = getProcessStats();
        break;
    case 'aircraft':
        $content = statsTable($stats['system-totals']['aircraft']);
        break;
    case 'flights':
        $content = statsTable($stats['system-totals']['flights']);
        break;
    case 'tracks':
        $content = statsTable($stats['system-totals']['tracks']);
        break;
    case 'adsb':
        $content = statsTable([ 'Number of Aircraft' => $stats['aircraft-category']]);
        break;
    case 'country':
        $content = statsTable([ 'Number of Aircraft' => $stats['register-country']]);
        break;
    case 'top10a1':
        $content = detailTable($stats['aircraft-top-10']['A1']);
        break;
    case 'top10a2':
        $content = detailTable($stats['aircraft-top-10']['A2']);
        break;
    case 'top10a3':
        $content = detailTable($stats['aircraft-top-10']['A3']);
        break;
    case 'top10a4':
        $content = detailTable($stats['aircraft-top-10']['A4']);
        break;
    case 'top10a5':
        $content = detailTable($stats['aircraft-top-10']['A5']);
        break;
    case 'top10a6':
        $content = detailTable($stats['aircraft-top-10']['A6']);
        break;
    case 'top10a7':
        $content = detailTable($stats['aircraft-top-10']['A7']);
        break;
    case 'top10b1':
        $content = detailTable($stats['aircraft-top-10']['B1']);
        break;
    case 'top10b2':
        $content = detailTable($stats['aircraft-top-10']['B2']);
        break;
    case 'top10b3':
        $content = detailTable($stats['aircraft-top-10']['B3']);
        break;
    case 'top10b4':
        $content = detailTable($stats['aircraft-top-10']['B4']);
        break;
    case 'fltcat':
        $content = statsTable($stats['flight-category']);
        break;
    case 'flttop10a1':
        $content = detailTable($stats['flight-top-10']['A1']);
        break;
    case 'flttop10a2':
        $content = detailTable($stats['flight-top-10']['A2']);
        break;
    case 'flttop10a3':
        $content = detailTable($stats['flight-top-10']['A3']);
        break;
    case 'flttop10a4':
        $content = detailTable($stats['flight-top-10']['A4']);
        break;
    case 'flttop10a5':
        $content = detailTable($stats['flight-top-10']['A5']);
        break;
    case 'flttop10a6':
        $content = detailTable($stats['flight-top-10']['A6']);
        break;
    case 'flttop10a7':
        $content = detailTable($stats['flight-top-10']['A7']);
        break;
    case 'flttop10b1':
        $content = detailTable($stats['flight-top-10']['B1']);
        break;
    case 'flttop10b2':
        $content = detailTable($stats['flight-top-10']['B2']);
        break;
    case 'flttop10b3':
        $content = detailTable($stats['flight-top-10']['B3']);
        break;
    case 'flttop10b4':
        $content = detailTable($stats['flight-top-10']['B4']);
        break;
    default:
        break;
}

$fileName = sprintf("../www/graphs/dashboard-%s.html", $section);
file_put_contents($fileName, outputPage($content));
