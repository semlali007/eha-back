<?php

namespace App\Repository;

use App\Models\SubDomain;

class SubDomainRepository extends AbstractRepository
{
    public function autocomplete($word)
    {
        $group = new SubDomain();
        return $group->getList(['name' => $word]);
    }

    public function search($options)
    {
        $domain = new SubDomain();
        return
            $domain->getPaginatedList($options);
    }
}