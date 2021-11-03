<?php

namespace App\Repository\Migrate;

use App\Models\Legacy\LegacyDapi;
use App\Repository\AbstractRepository;

class DapiRepository extends AbstractRepository{
    public function getList(){
        $dapi = new LegacyDapi();
        return $dapi->getList();
    }
}
