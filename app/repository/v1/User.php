<?php

namespace app\repository\v1;

use app\models\Users as UserModel;

class User
{

    private $user;

    public function __construct(UserModel $user)
    {
        $this->user = $user;
    }

}
