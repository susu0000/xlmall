<?php
//zend by  QQ:2172298892  瑾梦网络
namespace app\http\captcha\controllers;

class Index extends \app\http\base\controllers\Frontend
{
	public function actionIndex()
	{
		$params = array(
			'fontSize' => 14,
			'length'   => 4,
			'useNoise' => false,
			'fontttf'  => '4.ttf',
			'bg'       => array(255, 255, 255)
			);
		$verify = new \Think\Verify($params);
		$verify->entry();
	}
}

?>
