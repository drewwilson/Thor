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

function curl_request($opts){
	$ch = curl_init();
	if ($opts['type'] == 'POST'){
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $opts['params']);
	} else {
		$opts['url'] .= '?';
		foreach ($opts['params'] as $k => $v){
			$url .= $k.'='.$v.'&';
		}
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $opts['type']);
	}
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_URL, $opts['url']);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
	if (!empty($opts['headers'])){
		curl_setopt($ch, CURLOPT_HTTPHEADER, $opts['headers']);
	}
	$response = curl_exec($ch);
	$info = curl_getinfo($ch);
	curl_close($ch);
	return array('response' => $response, 'info' => $info);
}


?>