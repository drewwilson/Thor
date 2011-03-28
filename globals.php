<?php if (!defined('DIRECTSCRIPT')) exit('No direct script access allowed');

mysql_connect("localhost", "root", "");
mysql_select_db("quixly") or error(mysql_error());

define('local', true);

$GLOBALS['date'] = gmdate('Y-m-d G:i:s');
?>