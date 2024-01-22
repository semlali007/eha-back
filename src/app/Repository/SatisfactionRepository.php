<?php

namespace App\Repository;

use App\Models\Satisfaction;

class SatisfactionRepository extends AbstractRepository
{
    public function create($options)
    {
        return (new Satisfaction())->create($options);
    }

    public function get($nni, $cockpitId)
    {
        return (new Satisfaction())->get($nni, $cockpitId);
    }

    public function getList()
    {
        return (new Satisfaction())->getList();;
    }

}
