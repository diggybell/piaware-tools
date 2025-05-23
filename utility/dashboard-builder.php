<?php

function statsTable($stats)
{
    $ret = "<table class=\"table table-striped\">\n";
    $header = "<tr><th>&nbsp;</th>";
    $body   = "";

    $index = 0;
    foreach($stats as $action => $dailyTotals)
    {
        $body .= sprintf("<tr><td>%s</td>", $action);
        foreach($dailyTotals as $date => $count)
        {
            if($index == 0)
            {
                $header .= sprintf("<th class=\"text_end\">%s</th>", $date);
            }
            $body .= sprintf("<td class=\"text_end\">%s</td>", $count);
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
    case 'aircraft':
        $content = statsTable($stats['system-totals']['aircraft']);
        break;
    case 'flights':
        $content = statsTable($stats['system-totals']['flights']);
        break;
    case 'tracks':
        $content = statsTable($stats['system-totals']['tracks']);
        break;
    default:
        break;
}

$fileName = sprintf("../www/graphs/dashboard-%s.html", $section);
file_put_contents($fileName, outputPage($content));

?>