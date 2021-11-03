<?php

namespace App\Repository;

use App\Models\Group;

class GroupRepository extends AbstractRepository
{
    public function autocomplete($word)
    {
        $group = new Group();
        return $group->getList(['name' => $word]);
    }

    public function search($options)
    {
        $group = new Group();
        return
            $group->getPaginatedList($options);
    }
}