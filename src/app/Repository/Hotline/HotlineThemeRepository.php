<?php

namespace App\Repository\Hotline;

use App\Models\HotlineGroup;
use App\Repository\AbstractRepository;

class HotlineThemeRepository extends AbstractRepository
{
    public function getList()
    {
        $group = new HotlineGroup($this->db);
        return $group->getAll();
    }
}