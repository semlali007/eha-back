<?php

namespace App\Repository;

use App\Models\Domain;

class DomainRepository extends AbstractRepository
{
    public function autocomplete($word)
    {
        $group = new Domain();
        return $group->getList(['name' => $word]);
    }

    public function search($options)
    {
        $domain = new Domain();
        return
            $domain->getPaginatedList($options);
    }
}