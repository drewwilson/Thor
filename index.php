<?php
define('DIRECTSCRIPT', 'TRUE');

// load global functions
require_once('functions.php');

// load global vars and db vars
require_once("globals.php");

session_start();

// load main classes
require_once('api.php');
require_once('model.php');
require_once('output.php');

$api = new Api();
$api->request_uri_ignore = array('api', 'hp');

$api->process_request();

$api->output->send_output('json');

?>