<?php

$path = '/home/diggy/source/digtech/DigTech/lib';

include_once($path . '/autoload.php');
include_once($path . '/autoconfig.php');

use \DigTech\Logging\Logger as Logger;
use \DigTech\REST\APIInterface as APIInterface;

$cfg = getGlobalConfiguration();

$config = $cfg->getSection('logging');
Logger::configure($config);

APIInterface::serviceRequest('/api/v1/');
