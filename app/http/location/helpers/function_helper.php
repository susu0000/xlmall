<?php
//zend by QQ:2172298892 瑾梦网络
function getLetter($str)
{
	$i = 0;

	while ($i < strlen($str)) {
		$tmp = bin2hex(substr($str, $i, 1));

		if ('B0' <= $tmp) {
			$object = new \ectouch\Pinyin();
			$pyobj = $object->output($str);
			$pinyin = (isset($pyobj[0]) ? $pyobj[0] : '');
			return strtoupper(substr($pinyin, 0, 1));
			$i += 2;
		}
		else {
			return strtoupper(substr($str, $i, 1));
			$i++;
		}
	}
}


?>
