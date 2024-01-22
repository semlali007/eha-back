<?php

namespace App\Repository;

use App\Models\Stipulation;

class StipulationRepository extends AbstractRepository
{
    public function autocomplete($word)
    {
        $stipulation = new Stipulation();
        return $stipulation->getList(['name' => $word]);
    }

    public function search($options)
    {
        $stipulation = new Stipulation();

        return
            $stipulation->getPaginatedList($options);
    }
}
