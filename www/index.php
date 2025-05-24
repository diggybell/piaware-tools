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
      <div class="md-col-12">
         <h2>PiAware Tools Dashboard</h2>
      </div>
   </div>
</div>      
<?php

function getFormMarkup($elementName, $elementLabel, $elementClass='')
{
   $markup = '
<div class="row">            
   <div class="col-md-4">
      <label for="' . $elementName . '" class="form-control ' . $elementClass . '">' . $elementLabel . '</label>
   </div>
   <div class="col-md-8">
      <input id="' . $elementName . '" class="form-control fw-bold  ' . $elementClass . '" readonly />
   </div>
</div>   
   ';

   return $markup;
}

include_once('autoload.php');
include_once('autoconfig.php');

use \DigTech\Logging\Logger as Logger;

$cfg = getGlobalConfiguration();

$config = $cfg->getSection('logging');
Logger::configure($config);
Logger::setEnabled(true);

navBar();
graphsPanel();
dashboardPanel();
aircraftPanel();
tracksPanel();
reportsPanel();
endPage();

function navBar()
{
?>

<div class="container">
   <div class="row">
      <div class="col-md-12">
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
           <li class="nav-item">
             <button type="button" id="tab_graphs" class="nav-link" onclick="tabClicked('graphs')">Graphs</button>
           </li>
           <li class="nav-item">
             <button type="button" id="tab_reports" class="nav-link" onclick="tabClicked('reports')">Reports</button>
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
   <div class="col-md-7">
         <input type="button" id="metro_start" value=">" class="btn btn-success navbtn" />
         <input type="button" id="metro_stop" value="||" class="btn btn-danger navbtn" />
         <label id="metro_label" for="metro_tempo">BPM</label>
         <input type="text" id="metro_tempo" size="5" max-length="5" value="" class="aux_control" />
         <label id="set-label" for="set-number">Song</label>
         <input type="text" id="set-number" size="8" max-length="10" value="" readonly class="aux_control" />
   </div>
   <div class="col-md-5">
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
   <h3>Aircraft</h3>
   <hr>
   <div class="col-md-12 align-middle pt-dynamic-refresh" pt-external-content="graphs/dashboard-aircraft.html"></div>
</div>
<div class="row">
   <h3>Flights</h3>
   <hr>
   <div class="col-md-12 align-middle pt-dynamic-refresh" pt-external-content="graphs/dashboard-flights.html"></div>
</div>
<div class="row">
   <h3>Flight Tracks</h3>
   <hr>
   <div class="col-md-12 align-middle pt-dynamic-refresh" pt-external-content="graphs/dashboard-tracks.html"></div>
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
   <div class="col-md-5 align-middle pt-dynamic-refresh" pt-external-content="graphs/altitude-graph.html"></div>
   <div class="col-md-7 pt-dynamic-refresh" pt-external-content="graphs/altitude-table.html"></div>
</div>

<div class="row">
   <h3>Maximum RSSI for Range Rings</h3>
   <hr>
   <div class="col-md-5 align-middle pt-dynamic-refresh" pt-external-content="graphs/rssi-graph.html"></div>
   <div class="col-md-7 pt-dynamic-refresh" pt-external-content="graphs/rssi-table.html"></div>
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
   <div class="col-md-12 align-middle pt-dynamic-refresh" pt-external-content="graphs/dashboard-adsb.html"></div>
</div>

<div class="row">
   <h3>Registration Country</h3>
   <hr>
   <div class="col-md-12 align-middle pt-dynamic-refresh" pt-external-content="graphs/dashboard-country.html"></div>
</div>

<div class="row">
   <h3> Top 10 </h3>
   <hr>
   <div class="col-md-4 text-start">
      Light (< 15,500lb)
      <hr>
   </div>
   <div class="col-md-4 text-start">
      Small (15,500-75,000lb)
      <hr>
   </div>
   <div class="col-md-4 text-start">
      Rotorcraft
      <hr>
   </div>
</div>
<div class="row">
   <div class="col-md-4 align-middle pt-dynamic-refresh" pt-external-content="graphs/dashboard-top10a1.html"></div>
   <div class="col-md-4 align-middle pt-dynamic-refresh" pt-external-content="graphs/dashboard-top10a2.html"></div>
   <div class="col-md-4 align-middle pt-dynamic-refresh" pt-external-content="graphs/dashboard-top10a7.html"></div>
</div>

<div class="row">
   <hr>
   <div class="col-md-4 text-start">
      Large (75,000-300,000lb)
      <hr>
   </div>
   <div class="col-md-4 text-start">
      High Vortex
      <hr>
   </div>
   <div class="col-md-4 text-start">
      Heavy (Over 300,000lb)
      <hr>
   </div>
</div>
<div class="row">
   <div class="col-md-4 align-middle pt-dynamic-refresh" pt-external-content="graphs/dashboard-top10a3.html"></div>
   <div class="col-md-4 align-middle pt-dynamic-refresh" pt-external-content="graphs/dashboard-top10a4.html"></div>
   <div class="col-md-4 align-middle pt-dynamic-refresh" pt-external-content="graphs/dashboard-top10a5.html"></div>
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
   <hr>
   <div class="col-md-12 align-middle pt-dynamic-refresh" pt-external-content="graphs/dashboard-fltcat.html"></div>
</div>

<div class="row">
   <h3> Top 10 </h3>
   <hr>
   <div class="col-md-4 text-start">
      Light (< 15,500lb)
      <hr>
   </div>
   <div class="col-md-4 text-start">
      Small (15,500-75,000lb)
      <hr>
   </div>
   <div class="col-md-4 text-start">
      Rotorcraft
      <hr>
   </div>
</div>
<div class="row">
   <div class="col-md-4 align-middle pt-dynamic-refresh" pt-external-content="graphs/dashboard-flttop10a1.html"></div>
   <div class="col-md-4 align-middle pt-dynamic-refresh" pt-external-content="graphs/dashboard-flttop10a2.html"></div>
   <div class="col-md-4 align-middle pt-dynamic-refresh" pt-external-content="graphs/dashboard-flttop10a7.html"></div>
</div>

<div class="row">
   <hr>
   <div class="col-md-4 text-start">
      Large (75,000-300,000lb)
      <hr>
   </div>
   <div class="col-md-4 text-start">
      High Vortex
      <hr>
   </div>
   <div class="col-md-4 text-start">
      Heavy (Over 300,000lb)
      <hr>
   </div>
</div>
<div class="row">
   <div class="col-md-4 align-middle pt-dynamic-refresh" pt-external-content="graphs/dashboard-flttop10a3.html"></div>
   <div class="col-md-4 align-middle pt-dynamic-refresh" pt-external-content="graphs/dashboard-flttop10a4.html"></div>
   <div class="col-md-4 align-middle pt-dynamic-refresh" pt-external-content="graphs/dashboard-flttop10a5.html"></div>
</div>

</div>

<?php
}

function reportsPanel()
{
?>

<div id="panel_reports" class="container d-none">
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
<div class="md-col-12">
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