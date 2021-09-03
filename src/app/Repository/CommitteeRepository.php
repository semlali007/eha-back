<?php

namespace App\Repository;

use App\Models\Committee;

class CommitteeRepository extends AbstractRepository
{
    public function getList()
    {
        $committee = new Committee($this->db);
        return $committee->getList();
    }
}
