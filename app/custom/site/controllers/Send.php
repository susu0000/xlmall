<?php
//zend by  QQ:2172298892  瑾梦网络
namespace app\custom\site\controllers;

class Send extends \app\http\site\controllers\Index
{
	public function actionTest()
	{
		$message = array('code' => '1234', 'product' => 'sitename');
		$res = send_sms('18219034778', 'sms_signin', $message);

		if ($res !== true) {
			exit($res);
		}

		$res = send_mail('xxx', 'wanglin@ecmoban.com', 'title', 'content');

		if ($res !== true) {
			exit($res);
		}
	}
}

?>
