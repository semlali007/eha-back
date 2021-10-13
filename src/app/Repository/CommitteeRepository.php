<?php

namespace App\Repository;

use App\Models\Committee;

class CommitteeRepository extends AbstractRepository
{
    public function getList()
    {
        return (new Committee($this->db))->getList();
    }
}
