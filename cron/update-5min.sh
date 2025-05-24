#!/bin/bash

#
# Update PiAware Tools output every 5 minutes
cd /home/diggy/source/ads-b
scp -q diggy@192.168.1.107:/run/dump1090-fa/* data
cd utility
php adsb-import.php --altitude
php adsb-import.php --aircraft
php adsb-update.php
php flight-builder.php
php graph-builder.php --graph=altitude
php graph-builder.php --graph=rssi
php statistics-builder.php
php dashboard-builder.php --section=aircraft
php dashboard-builder.php --section=flights
php dashboard-builder.php --section=tracks
php dashboard-builder.php --section=adsb
php dashboard-builder.php --section=country
