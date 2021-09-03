<?php

namespace App\Manager\Migrate;

use App\Models\Legacy\LegacyDatpb;

class DatpbManager extends MigrateManager
{
    public function saveData($options)
    {
        $this->subject = new LegacyDatpb($this->db);
        $this->updateData($options);
    }
}
