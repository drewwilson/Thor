<?php if (!defined('DIRECTSCRIPT')) exit('No direct script access allowed');

function plural($str, $force = FALSE) {
	$str = strtolower(trim($str));
	$end = substr($str, -1);
	if ($end == 'y') { // Y preceded by vowel => regular plural
		$vowels = array('a', 'e', 'i', 'o', 'u');
		$str = in_array(substr($str, -2, 1), $vowels) ? $str.'s' : substr($str, 0, -1).'ies';
	} elseif ($end == 's') {
		if ($force == TRUE) { $str .= 'es'; }
	} else {
		$str .= 's';
	}
	return $str;
}

function singular($str) {
	$str = strtolower(trim($str));
	$end = substr($str, -3);
	if ($end == 'ies') {
		$str = substr($str, 0, strlen($str)-3).'y';
	} elseif ($end == 'ses') {
		$str = substr($str, 0, strlen($str)-2);
	} else {
		$end = substr($str, -1);
		if ($end == 's') { $str = substr($str, 0, strlen($str)-1); }
	}
	return $str;
}

function stripslashes_deep(&$value) {
	if (is_array($value)) {
		array_map('stripslashes_deep', $value);
	} else {
		stripslashes($value);
	}
}

function curl_request($type, $url, $query, $json=true){
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $type);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $query);
	$response = curl_exec($ch);
	curl_close($ch);
	return ($json) ? json_decode($response) : $response;
}

?>