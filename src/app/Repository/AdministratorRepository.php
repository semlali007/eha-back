<?php

namespace App\Repository;

use App\Models\Administrator;

class AdministratorRepository extends AbstractRepository
{
    public function getList()
    {
        $admin = new Administrator();
        return $admin->getList();
    }
}