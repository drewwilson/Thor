<?php
define('DIRECTSCRIPT', 'TRUE');

// load global functions
require_once('functions.php');
require_once('security.php');

// load config (db connection / security global)
require_once("config.php");

// global variables
$GLOBALS['date'] = gmdate('Y-m-d G:i:s');


session_start();

// load main classes
require_once('api.php');
require_once('model.php');
require_once('output.php');

$api = new Api();

$api->process_request();

$api->output->send_output('json');

?>