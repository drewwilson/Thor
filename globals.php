<?php if (!defined('DIRECTSCRIPT')) exit('No direct script access allowed');

mysql_connect("localhost", "", "");
mysql_select_db("db_name") or error(mysql_error());

$GLOBALS['date'] = gmdate('Y-m-d G:i:s');
?>