<?php
//zend by  QQ:2172298892  瑾梦网络
function encrypt($str, $key = AUTH_KEY)
{
	$coded = '';
	$keylength = strlen($key);
	$i = 0;

	for ($count = strlen($str); $i < $count; $i += $keylength) {
		$coded .= substr($str, $i, $keylength) ^ $key;
	}

	return str_replace('=', '', base64_encode($coded));
}

function decrypt($str, $key = AUTH_KEY)
{
	$coded = '';
	$keylength = strlen($key);
	$str = base64_decode($str);
	$i = 0;

	for ($count = strlen($str); $i < $count; $i += $keylength) {
		$coded .= substr($str, $i, $keylength) ^ $key;
	}

	return $coded;
}

defined('IN_ECTOUCH') || exit('Deny Access');

?>
