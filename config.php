<?php if (!defined('DIRECTSCRIPT')) exit('No direct script access allowed');

/*
mysql_connect("localhost", "", "");
mysql_select_db("db_name") or error(mysql_error());
*/

$GLOBALS['security'] = array(
	'mcrypt' => array('cipher' => MCRYPT_RIJNDAEL_256, 'mode' => MCRYPT_MODE_ECB),
	'key' => 'thorapi',
	'whitelist_ip' => array()
);

$GLOBALS['request_uri_ignore'] = array('api');

$GLOBALS['date'] = gmdate('Y-m-d G:i:s');

?>