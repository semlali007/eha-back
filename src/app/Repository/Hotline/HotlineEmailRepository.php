<?php

namespace App\Repository\Hotline;

use App\Models\HotlineEmail;
use App\Repository\AbstractRepository;

class HotlineEmailRepository extends AbstractRepository
{
    public function getList()
    {
        $email = new HotlineEmail($this->db);
        return $email->getAll();
    }
}