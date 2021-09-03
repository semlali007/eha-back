<?php

namespace App\Repository\Migrate;

use App\Models\Legacy\LegacyDait;
use App\Repository\AbstractRepository;

class DaitRepository extends AbstractRepository{

    public function getList(){
        $dait = new LegacyDait($this->db);
        return $dait->getList();
    }
}
