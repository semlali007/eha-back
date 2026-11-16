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

    /**
     * Récupère toutes les zones au format simple [{code, label}, ...]
     * @return array
     */
    public function getAll()
    {
        $area = new Area();
        return $area->getAllSimple();
    }

    public function search($options)
    {
        $area = new Area();
        return
            $area->getPaginatedList($options);
    }
}