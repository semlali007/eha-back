<?php

namespace App\Repository\Hotline;

use App\Models\HotlineGroup;
use App\Repository\AbstractRepository;

class HotlineGroupRepository extends AbstractRepository
{
    public function getList()
    {
        $group = new HotlineGroup();
        return $group->getAll();
    }
}