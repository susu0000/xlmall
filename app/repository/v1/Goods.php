<?php

namespace app\repository\v1;

use app\models\Goods as GoodsModel;

class Goods
{

    private $goods;

    public function __construct(GoodsModel $goods)
    {
        $this->goods = $goods;
    }

}
