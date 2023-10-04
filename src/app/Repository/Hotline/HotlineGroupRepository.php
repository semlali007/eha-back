<?php

namespace App\Repository\Hotline;

use App\Models\HotlineGroup;
use App\Repository\AbstractRepository;

class HotlineGroupRepository extends AbstractRepository
{
    public function getList()
    {
        $group = new HotlineGroup();
        return $group->getAll();
    }
    public function get($id)
    {
        $group = new HotlineGroup();
        return $group->get($id);
    }
    public function delete(mixed $id):mixed {
        $group = new HotlineGroup();
        return $group->delete($id);
    }
}