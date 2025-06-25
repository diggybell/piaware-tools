#!/bin/bash

#
# Update PiAware Tools output every 5 minutes
cd /home/diggy/source/ads-b

#
# Retrieve the PiAware data from the receiver
scp -q diggy@192.168.1.107:/run/dump1090-fa/* data

#
# Process the PiAware JSON files into aircraft-history.jjson
cd utility
php adsb-import.php --altitude
php adsb-import.php --aircraft

#
# Update the database from aircraft-history.json
php adsb-update.php

#
# Link positions to synthesized flights
php flight-builder.php

#
# Build graphs for the dashboard
php graph-builder.php --graph=altitude
php graph-builder.php --graph=rssi

#
# Generate the system statistics
php statistics-builder.php

#
# Generate the tables for the dashboard
php dashboard-builder.php --section=totals
php dashboard-builder.php --section=process
php dashboard-builder.php --section=aircraft
php dashboard-builder.php --section=flights
php dashboard-builder.php --section=tracks
php dashboard-builder.php --section=adsb
php dashboard-builder.php --section=country
php dashboard-builder.php --section=top10a1
php dashboard-builder.php --section=top10a2
php dashboard-builder.php --section=top10a3
php dashboard-builder.php --section=top10a4
php dashboard-builder.php --section=top10a5
php dashboard-builder.php --section=top10a6
php dashboard-builder.php --section=top10a7
php dashboard-builder.php --section=top10b17
php dashboard-builder.php --section=top10b1
php dashboard-builder.php --section=top10b2
#php dashboard-builder.php --section=top10b3
#php dashboard-builder.php --section=top10b4
php dashboard-builder.php --section=fltcat
php dashboard-builder.php --section=flttop10a1
php dashboard-builder.php --section=flttop10a2
php dashboard-builder.php --section=flttop10a3
php dashboard-builder.php --section=flttop10a4
php dashboard-builder.php --section=flttop10a5
php dashboard-builder.php --section=flttop10a6
php dashboard-builder.php --section=flttop10a7
php dashboard-builder.php --section=flttop10b1
php dashboard-builder.php --section=flttop10b2
#php dashboard-builder.php --section=flttop10b3
#php dashboard-builder.php --section=flttop10b4
