<?php
//zend by  QQ:2172298892  瑾梦网络
return array(
	'HTML_CACHE_ON'    => true,
	'HTML_CACHE_TIME'  => 60,
	'HTML_FILE_SUFFIX' => '.shtml',
	'HTML_CACHE_RULES' => array(
		'*' => array('{$_SERVER.REQUEST_URI|md5}')
		)
	);

?>
