<?php

include_once('../lib/config.php');
include_once('../lib/icao.php');

$tail = 'N99999';
$tgtCode = 0xAE29DD;

$icao = icaoHexCode($tail);
$first = $icao + 1;

$diff = $tgtCode - $icao;

printf("Last GA: %06X (%s) - First: %06X - Target: %-6X - Diff: %06X (%d)", $icao, $tail, $first, $tgtCode, $diff, $diff);
