<?php

namespace app\repository\v1;

use app\models\Article as ArticleModel;

class Article
{

    private $article;

    public function __construct(ArticleModel $article)
    {
        $this->article = $article;
    }

}
