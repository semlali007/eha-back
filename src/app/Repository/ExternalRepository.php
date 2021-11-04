<?php

namespace App\Repository;

use App\Models\External;

class ExternalRepository extends AbstractRepository
{
    public function getList()
    {
        $external = new External();
        return $external->getList();
    }
}