<!doctype html>
<html>
<head lang="en">
<title>PiAware Tools</title>
<meta http-equiv="Pragma" content="no-cache">
<meta http-equiv="Cache-Control" content="no-cache">
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="css/bootstrap.min.css" rel="stylesheet">
<link href="css/piaware-tools.css?<?php print date('Ymd-His'); ?>" rel="stylesheet">
<script src="js/piaware-tools.js?<?php print date('Ymd-His'); ?>"></script>

<style>
</style>
</head>
<body onload="initApplication()">
<div class="container pt-banner">
   <div class="row">
      <div class="col col-md-8">
         <h3>PiAware Tools Dashboard</h3>
         <p class="text-small" id="lastUpdated"></p>
      </div>
      <div class="col col-md-4 float-end">
         <img src="images/piaware-tools-background.png" class="float-end" />
      </div>
   </div>
</div>      
<?php

function getFormMarkup($elementName, $elementLabel, $elementClass='')
{
   $markup = '
<div class="row">            
   <div class="col col-md-4">
      <label for="' . $elementName . '" class="form-control ' . $elementClass . '">' . $elementLabel . '</label>
   </div>
   <div class="col col-md-8">
      <input id="' . $elementName . '" class="form-control fw-bold  ' . $elementClass . '" readonly />
   </div>
</div>   
   ';

   return $markup;
}
/*
include_once('autoload.php');
include_once('autoconfig.php');

use \DigTech\Logging\Logger as Logger;

$cfg = getGlobalConfiguration();

$config = $cfg->getSection('logging');
Logger::configure($config);
Logger::setEnabled(true);
*/

navBar();
dashboardPanel();
aircraftPanel();
tracksPanel();
graphsPanel();
if(file_exists('graphs/dod-dashboard-adsb.html'))
{
   dodAircraftPanel();
}
if(file_exists('graphs/dod-dashboard-fltcat.html'))
{
   dodFlightPanel();
}
aboutPanel();
endPage();

function navBar()
{
?>

<div class="container">
   <div class="row">
      <div class="col col-md-12">
         <ul class="nav nav-tabs">
            <li class="nav-item">
              <button type="button" id="tab_dashboard" class="nav-link active" onclick="tabClicked('dashboard')">Dashboard</button>
            </li>
           <li class="nav-item">
              <button type="button" id="tab_aircraft" class="nav-link" onclick="tabClicked('aircraft')">Aircraft</button>
           </li>
           <li class="nav-item">
              <button type="button" id="tab_tracks" class="nav-link" onclick="tabClicked('tracks')">Flights</button>
           </li>
<?php if(file_exists('graphs/dod-dashboard-adsb.html')) { ?>
           <li class="nav-item">
              <button type="button" id="tab_dod-aircraft" class="nav-link" onclick="tabClicked('dod-aircraft')">DoD Aircraft</button>
           </li>
<?php } ?>
<?php if(file_exists('graphs/dod-dashboard-fltcat.html')) { ?>
           <li class="nav-item">
              <button type="button" id="tab_dod-flight" class="nav-link" onclick="tabClicked('dod-flight')">DoD Flights</button>
           </li>
<?php } ?>
           <li class="nav-item">
              <button type="button" id="tab_graphs" class="nav-link" onclick="tabClicked('graphs')">Graphs</button>
           </li>
           <li class="nav-item">
              <button type="button" id="tab_about" class="nav-link" onclick="tabClicked('about')">About</button>
           </li>
         </ul>
      </div>
   </div>
</div>
&nbsp;
<?php
}

function controlBar()
{
?>

<div id="buttons" class="row">
   <div class="col col-md-7">
         <input type="button" id="metro_start" value=">" class="btn btn-success navbtn" />
         <input type="button" id="metro_stop" value="||" class="btn btn-danger navbtn" />
         <label id="metro_label" for="metro_tempo">BPM</label>
         <input type="text" id="metro_tempo" size="5" max-length="5" value="" class="aux_control" />
         <label id="set-label" for="set-number">Song</label>
         <input type="text" id="set-number" size="8" max-length="10" value="" readonly class="aux_control" />
   </div>
   <div class="col col-md-5">
      <span class="float-end">
         <input type="button" id="btn_prev" value="<<" class="btn btn-primary navbtn" />
         <input type="button" id="btn_next" value=">>" class="btn btn-primary navbtn" />
         <input type="button" id="btn_start" value=">" class="btn btn-success navbtn" />
         <input type="button" id="btn_stop" value="||" class="btn btn-danger navbtn" />
      </span>
   </div>
</div>

<?php  
}

function dashboardPanel()
{
?>

<div id="panel_dashboard" class="container d-block sm_panel"> <!-- start dashboard panel -->

<div class="row">
   <div class="col col-md-12 align-middle">
      <h3>System Totals</h3>
      <hr>
      <div class="pt-dynamic-refresh" pt-external-content="graphs/dashboard-totals.html"></div>
   </div>
</div>
<div class="row">
   <div class="col col-md-12 align-middle">
   <h3>Aircraft</h3>
   <hr>
   <div class="pt-dynamic-refresh" pt-external-content="graphs/dashboard-aircraft.html"></div>
   </div>
</div>
<div class="row">
   <div class="col col-md-12 align-middle">
      <h3>Flights</h3>
      <hr>
      <div class="pt-dynamic-refresh" pt-external-content="graphs/dashboard-flights.html"></div>
   </div>
</div>
<div class="row">
   <div class="col col-md-12 align-middle">
      <h3>Flight Tracks</h3>
      <hr>
      <div class="pt-dynamic-refresh" pt-external-content="graphs/dashboard-tracks.html"></div>
   </div>
</div>
<div class="row">
   <div class="col col-md-12 align-middle">
      <h3>Process Status</h3>
      <hr>
      <div class="pt-dynamic-refresh" pt-external-content="graphs/dashboard-process.html"></div>
   </div>
</div>

</div> <!-- end of dashboard panel -->

<?php
}


function graphsPanel()
{
?>

<div id="panel_graphs" class="container d-none sm_panel"> <!-- start details panel -->

<div class="row">
   <h3>Minimum Altitude for Range Rings</h3>
   <hr>
   <div class="col col-md-5 align-middle pt-dynamic-refresh" pt-external-content="graphs/altitude-graph.html"></div>
   <div class="col col-md-7 pt-dynamic-refresh" pt-external-content="graphs/altitude-table.html"></div>
</div>

<div class="row">
   <h3>Maximum RSSI for Range Rings</h3>
   <hr>
   <div class="col col-md-5 align-middle pt-dynamic-refresh" pt-external-content="graphs/rssi-graph.html"></div>
   <div class="col col-md-7 pt-dynamic-refresh" pt-external-content="graphs/rssi-table.html"></div>
</div>
</div> <!-- end of details panel -->

<?php
}


function aircraftPanel()
{
?>

<div id="panel_aircraft" class="container d-none">

<div class="row">
   <h3>ADS-B Category</h3>
   <hr>
   <div class="col col-md-12 align-middle pt-dynamic-refresh" pt-external-content="graphs/dashboard-adsb.html"></div>
</div>

<div class="row">
   <h3>Registration Country</h3>
   <hr>
   <div class="col col-md-12 align-middle pt-dynamic-refresh" pt-external-content="graphs/dashboard-country.html"></div>
</div>

<div class="row">
   <h3>Top 10 Aircraft</h3>
   <hr>
   <div class="col col-md-4 text-start">
      Light (&lt; 15,500lb)
      <hr>
      <div class="pt-dynamic-refresh" pt-external-content="graphs/dashboard-top10a1.html"></div>
   </div>
   <div class="col col-md-4 text-start">
      Small (15,500-75,000lb)
      <hr>
      <div class="pt-dynamic-refresh" pt-external-content="graphs/dashboard-top10a2.html"></div>
   </div>
   <div class="col col-md-4 text-start">
      High Performance &gt; 5g and 400kt
      <hr>
      <div class="pt-dynamic-refresh" pt-external-content="graphs/dashboard-top10a6.html"></div>
   </div>
</div>

<div class="row">
   <hr>
   <div class="col col-md-4 text-start">
      Large (75,000-300,000lb)
      <hr>
      <div class="pt-dynamic-refresh" pt-external-content="graphs/dashboard-top10a3.html"></div>
   </div>
   <div class="col col-md-4 text-start">
      High Vortex
      <hr>
      <div class="pt-dynamic-refresh" pt-external-content="graphs/dashboard-top10a4.html"></div>
   </div>
   <div class="col col-md-4 text-start">
      Heavy (Over 300,000lb)
      <hr>
      <div class="pt-dynamic-refresh" pt-external-content="graphs/dashboard-top10a5.html"></div>
   </div>
</div>

<div class="row">
   <hr>
   <div class="col col-md-4 text-start">
      Rotorcraft
      <hr>
      <div class="pt-dynamic-refresh" pt-external-content="graphs/dashboard-top10a7.html"></div>
   </div>
   <div class="col col-md-4 text-start">
      Glider/Sailplane
      <hr>
      <div class="pt-dynamic-refresh" pt-external-content="graphs/dashboard-top10b1.html"></div>
   </div>
   <div class="col col-md-4 text-start">
      Airship/Balloon
      <hr>
      <div class="pt-dynamic-refresh" pt-external-content="graphs/dashboard-top10b2.html"></div>
   </div>
</div>

</div>

<?php
}

function dodAircraftPanel()
{
?>

<div id="panel_dod-aircraft" class="container d-none">

<div class="row">
   <h3>Aircraft Category</h3>
   <hr>
   <div class="col col-md-12 align-middle pt-dynamic-refresh" pt-external-content="graphs/dod-dashboard-adsb.html"></div>
</div>

<div class="row">
   <h3>Top 10 Aircraft</h3>
   <hr>
   <div class="col col-md-4 text-start">
      Light
      <hr>
      <div class="pt-dynamic-refresh" pt-external-content="graphs/dod-dashboard-top10a1.html"></div>
   </div>
   <div class="col col-md-4 text-start">
      Small
      <hr>
      <div class="pt-dynamic-refresh" pt-external-content="graphs/dod-dashboard-top10a2.html"></div>
   </div>
   <div class="col col-md-4 text-start">
      High Performance
      <hr>
      <div class="pt-dynamic-refresh" pt-external-content="graphs/dod-dashboard-top10a6.html"></div>
   </div>
</div>

<div class="row">
   <hr>
   <div class="col col-md-4 text-start">
      Medium
      <hr>
      <div class="pt-dynamic-refresh" pt-external-content="graphs/dod-dashboard-top10a3.html"></div>
   </div>
   <div class="col col-md-4 text-start">
      Large
      <hr>
      <div class="pt-dynamic-refresh" pt-external-content="graphs/dod-dashboard-top10a4.html"></div>
   </div>
   <div class="col col-md-4 text-start">
      Heavy
      <hr>
      <div class="pt-dynamic-refresh" pt-external-content="graphs/dod-dashboard-top10a5.html"></div>
   </div>
</div>

<div class="row">
   <hr>
   <div class="col col-md-4 text-start">
      Rotorcraft
      <hr>
      <div class="pt-dynamic-refresh" pt-external-content="graphs/dod-dashboard-top10a7.html"></div>
   </div>
   <div class="col col-md-4 text-start">
      VTOL
      <hr>
      <div class="pt-dynamic-refresh" pt-external-content="graphs/dod-dashboard-top10b1.html"></div>
   </div>
   <div class="col col-md-4 text-start">
      Unmanned
      <hr>
      <div class="pt-dynamic-refresh" pt-external-content="graphs/dod-dashboard-top10b2.html"></div>
   </div>
</div>
<div class="row">
</div>

</div>

<?php
}

function tracksPanel()
{
?>

<div id="panel_tracks" class="container d-none">

<div class="row">
   <h3>Flights By Category</h3>
   <p>7-day History</p>
   <hr>
   <div class="col col-md-12 align-middle pt-dynamic-refresh" pt-external-content="graphs/dashboard-fltcat.html"></div>
</div>

<div class="row">
   <h3>Top 10 by Flight Count</h3>
   <p>24-hour Window</p>
   <hr>
   <div class="col col-md-4 text-start">
      Light (&lt; 15,500lb)
      <hr>
      <div class="pt-dynamic-refresh" pt-external-content="graphs/dashboard-flttop10a1.html"></div>
   </div>
   <div class="col col-md-4 text-start">
      Small (15,500-75,000lb)
      <hr>
      <div class="pt-dynamic-refresh" pt-external-content="graphs/dashboard-flttop10a2.html"></div>
   </div>
   <div class="col col-md-4 text-start">
      High Performance &gt; 5g and 400kt
      <hr>
      <div class="pt-dynamic-refresh" pt-external-content="graphs/dashboard-flttop10a6.html"></div>
   </div>
</div>

<div class="row">
   <hr>
   <div class="col col-md-4 text-start">
      Large (75,000-300,000lb)
      <hr>
      <div class="pt-dynamic-refresh" pt-external-content="graphs/dashboard-flttop10a3.html"></div>
   </div>
   <div class="col col-md-4 text-start">
      High Vortex
      <hr>
      <div class="pt-dynamic-refresh" pt-external-content="graphs/dashboard-flttop10a4.html"></div>
   </div>
   <div class="col col-md-4 text-start">
      Heavy (Over 300,000lb)
      <hr>
      <div class="pt-dynamic-refresh" pt-external-content="graphs/dashboard-flttop10a5.html"></div>
   </div>
</div>

<div class="row">
   <hr>
   <div class="col col-md-4 text-start">
      Rotorcraft
      <hr>
      <div class="pt-dynamic-refresh" pt-external-content="graphs/dashboard-flttop10a7.html"></div>
   </div>
   <div class="col col-md-4 text-start">
      Glider/Sailplane
      <hr>
      <div class="pt-dynamic-refresh" pt-external-content="graphs/dashboard-flttop10b1.html"></div>
   </div>
   <div class="col col-md-4 text-start">
      Airship/Balloon
      <hr>
      <div class="pt-dynamic-refresh" pt-external-content="graphs/dashboard-flttop10b2.html"></div>
   </div>
</div>

</div>

<?php
}

function dodFlightPanel()
{
?>

<div id="panel_dod-flight" class="container d-none">
<div class="row">
   <h3>Flights By Category</h3>
   <p>7-day History</p>
   <hr>
   <div class="col col-md-12 align-middle pt-dynamic-refresh" pt-external-content="graphs/dod-dashboard-fltcat.html"></div>
</div>

<div class="row">
   <h3>Top 10 by Flight Count</h3>
   <p>24-hour Window</p>
   <hr>
   <div class="col col-md-4 text-start">
      Light
      <hr>
      <div class="pt-dynamic-refresh" pt-external-content="graphs/dod-dashboard-flttop10a1.html"></div>
   </div>
   <div class="col col-md-4 text-start">
      Small
      <hr>
      <div class="pt-dynamic-refresh" pt-external-content="graphs/dod-dashboard-flttop10a2.html"></div>
   </div>
   <div class="col col-md-4 text-start">
      High Performance
      <hr>
      <div class="pt-dynamic-refresh" pt-external-content="graphs/dod-dashboard-flttop10a6.html"></div>
   </div>
</div>

<div class="row">
   <hr>
   <div class="col col-md-4 text-start">
      Medium
      <hr>
      <div class="pt-dynamic-refresh" pt-external-content="graphs/dod-dashboard-flttop10a3.html"></div>
   </div>
   <div class="col col-md-4 text-start">
      Large
      <hr>
      <div class="pt-dynamic-refresh" pt-external-content="graphs/dod-dashboard-flttop10a4.html"></div>
   </div>
   <div class="col col-md-4 text-start">
      Heavy
      <hr>
      <div class="pt-dynamic-refresh" pt-external-content="graphs/dod-dashboard-flttop10a5.html"></div>
   </div>
</div>

<div class="row">
   <hr>
   <div class="col col-md-4 text-start">
      Rotorcraft
      <hr>
      <div class="pt-dynamic-refresh" pt-external-content="graphs/dod-dashboard-flttop10a7.html"></div>
   </div>
   <div class="col col-md-4 text-start">
      VTOL
      <hr>
      <div class="pt-dynamic-refresh" pt-external-content="graphs/dod-dashboard-flttop10b1.html"></div>
   </div>
   <div class="col col-md-4 text-start">
      UAV
      <hr>
      <div class="pt-dynamic-refresh" pt-external-content="graphs/dod-dashboard-flttop10b2.html"></div>
   </div>
</div>

</div>

<?php
}

function aboutPanel()
{
?>

<div id="panel_about" class="container d-none">
<div>
<h2>About PiAware Tools</h2>
<p>
PiAware Tools is a project that was born when three areas of interest merged. As a professional I have been in software and data technologies since 1988.
In 1989 I got my private pilot's license but wasn't able to stay active due to family concerns. But my passion for aircraft never waned. Going way back
to my 8-year old self I discovered radio and have spent way too much time tinkering with them. So what happens when they come together?
</p>
<p>
Given the PiAware Tools name, I started playing with a FlightAware.com PiAware ADS-B receiver. Through out the month of May of 2025 I built the engine that is gathering
all of the data that is presented on this page. This page is displaying statistics and status information for my PiAware receiver.
</p>
<p>
If you would like to know more about FlightAware.com, PiAware, or PiAware Tools, here are some useful links.

<ul>
   <li><a href="https://www.flightaware.com">FlightAware.com</a></li>
   <li><a href="https://www.flightaware.com/adsb/piaware/">PiAware ADS-B Receiver</a></li>
   <li><a href="https://github.com/diggybell/piaware-tools">PiAware Tools at GitHub</a></li>
</ul>
</p>
</div>
<div>
<h3>License</h3>
<p>
BSD 2-Clause License
</p>
<p>
Copyright (c) 2025, Diggy Bell
</p>
<p>
Redistribution and use in source and binary forms, with or without
modification, are permitted provided that the following conditions are met:
</p>
<p>
1. Redistributions of source code must retain the above copyright notice, this
   list of conditions and the following disclaimer.
</p>
<p>
2. Redistributions in binary form must reproduce the above copyright notice,
   this list of conditions and the following disclaimer in the documentation
   and/or other materials provided with the distribution.
</p>
<p>
THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE
FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL
DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY,
OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.      
</p>
</div>
</div>

<?php
}

function diagnosticsPanel()
{
?>

<div id="diagnostics" class="container d-none">
</div>

<?php
}

function endPage()
{
?>
<div class="container">
<div class="row">
<div class="col col-md-12">
   <hr>
   <center><p>Copyright &copy; <?php print(date('Y')); ?> - Diggy Bell</p></center>
</div>
</div>
</div>
<script src="js/bootstrap.bundle.min.js"></script>
</body>
</html>

<?php
}
