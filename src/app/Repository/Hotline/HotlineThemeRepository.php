<?php

namespace App\Repository\Hotline;

use App\Models\HotlineGroup;
use App\Repository\AbstractRepository;

class HotlineThemeRepository extends AbstractRepository
{
    public function getList()
    {
        $group = new HotlineGroup();
        return $group->getAll();
    }
}