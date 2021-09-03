<?php

namespace App\Repository;

use App\Models\Group;

class GroupRepository extends AbstractRepository
{
    public function autocomplete($word)
    {
        $group = new Group($this->db);
        return $group->getList(['name' => $word]);
    }

    public function search($options)
    {
        $group = new Group($this->db);
        return
            $group->getPaginatedList($options);
    }
}