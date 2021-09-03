<?php

namespace App\Repository;

use App\Models\Area;

class AreaRepository extends AbstractRepository
{
    public function autocomplete($word)
    {
        $area = new Area($this->db);
        return $area->getList(['name' => $word]);
    }

    public function search($options)
    {
        $area = new Area($this->db);
        return
            $area->getPaginatedList($options);
    }
}