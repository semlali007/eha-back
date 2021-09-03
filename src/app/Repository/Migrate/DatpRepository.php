<?php

namespace App\Repository\Migrate;

use App\Models\Legacy\LegacyDatpa;
use App\Models\Legacy\LegacyDatpb;
use App\Repository\AbstractRepository;

class DatpRepository extends AbstractRepository
{
    public function getList()
    {
        $datpa = new LegacyDatpa($this->db);
        $datpb = new LegacyDatpb($this->db);
        $list = $datpa->getList();
        foreach ($list as $key => $datpaItem) {
            $list[$key]['datpb'] = $datpb->get($datpaItem['id']);
        }

        return $list;
    }
}
