<?php

include_once('../lib/config.php');
include_once('../lib/icao.php');

$icao = '440D9B';

printf("%-10s - %s\n", $icao, getICAOCountry($icao));