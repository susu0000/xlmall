<?php

namespace app\repository\v1;

use app\models\Order as OrderModel;

class Order
{

    private $order;

    public function __construct(OrderModel $order)
    {
        $this->order = $order;
    }

}
