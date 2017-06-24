<?php

namespace app\repository\v1;

use app\models\Cart as CartModel;

class Cart
{

    private $cart;

    public function __construct(CartModel $cart)
    {
        $this->cart = $cart;
    }

}
