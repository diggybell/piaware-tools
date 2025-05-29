<?php
/**
    \file config.php
    \ingroup Lib
    \brief Parameters that are used to control operation of PiAware Tools
 */
define('BASEPATH', '/home/diggy/source/ads-b');                 ///< The base path where PiAware Tools is installed
define('DATAPATH', BASEPATH . '/data/');                        ///< The directory where the PiAware history data is stored
define('ALTITUDE_FILE', BASEPATH . '/altitude-stats.json');     ///< The altitude statistics data used to drive the altitude/range graph
define('AIRCRAFT_FILE', BASEPATH . '/aircraft-history.json');   ///< The aircraft history data that is collected from processing the PiAware history
define('RECEIVER_FILE', DATAPATH . 'receiver.json');            ///< The PiAware receiver status written by PiAware
define('ICAOHEX_FILE', BASEPATH . '/json/icao-decode.json');    ///< The ICAO Hex to country mapping used to determine aircraft registration country
define('ADSBCAT_FILE', BASEPATH . '/json/adsb-category.json');  ///< The aircraft category values used in ADS-B
define('FLIGHT_BOUNDARY', 60 * 30);                             ///< The number of minutes that split a set of tracks for an aircraft into flights
define('RUNTIME_PATH', BASEPATH . '/run/');                     ///< The directory where runtime status for background process will be written
