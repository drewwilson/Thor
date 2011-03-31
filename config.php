<?php if (!defined('DIRECTSCRIPT')) exit('No direct script access allowed');

mysql_connect("localhost", "root", "");
mysql_select_db("quixly") or error(mysql_error());

$GLOBALS['security'] = array(
	'mcrypt' => array('cipher' => MCRYPT_RIJNDAEL_256, 'mode' => MCRYPT_MODE_ECB),
	'key' => 'thorapi'
);
?>