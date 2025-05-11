<?php

define('BASEPATH', '/home/diggy/source/ads-b');
define('DATAPATH', BASEPATH . '/data/');
define('ALTITUDE_FILE', BASEPATH . '/altitude-stats.json');
define('AIRCRAFT_FILE', BASEPATH . '/aircraft-history.json');
define('RECEIVER_FILE', DATAPATH . 'receiver.json');
define('ICAOHEX_FILE', BASEPATH . '/json/icao-decode.json');
define('ADSBCAT_FILE', BASEPATH . '/json/adsb-category.json');
define('FLIGHT_BOUNDARY', 60 * 30);
