<?php

namespace app\repository\v1;

use app\models\Activity as ActivityModel;

class Activity
{

    private $activity;

    public function __construct(ActivityModel $activity)
    {
        $this->activity = $activity;
    }

}
