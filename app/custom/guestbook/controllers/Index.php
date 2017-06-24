<?php
//zend by  QQ:2172298892  瑾梦网络
namespace app\custom\guestbook\controllers;

class Index extends \app\http\base\controllers\Frontend
{
	public function actionIndex()
	{
		echo 'this guestbook list. ';
		echo '<a href="' . url('add') . '">Goto Add</a>';
	}

	public function actionAdd()
	{
		$this->display();
	}

	public function actionSave()
	{
		$post = array('title' => i('title'), 'content' => i('content'));
		$this->redirect('index');
	}
}

?>
