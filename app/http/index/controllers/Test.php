<?php

namespace app\http\index\controllers;

class Test extends \app\http\base\controllers\Frontend
{
	private $article;

	public function __construct(\app\repository\Article $articles)
	{
		parent::__construct();
		$this->article = $articles;
	}

	public function actionIndex()
	{
		$res = $this->article->find(58);
		dump($res);
	}
}

?>
