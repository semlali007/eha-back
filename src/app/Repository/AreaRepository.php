<?php

namespace App\Repository;

use App\Models\Area;

class AreaRepository extends AbstractRepository
{
    public function autocomplete($word)
    {
        $area = new Area();
        return $area->getList(['name' => $word]);
    }

    public function search($options)
    {
        $area = new Area();
        return
            $area->getPaginatedList($options);
    }
}