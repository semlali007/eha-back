<?php

namespace App\Repository;

use App\Models\Information;

class InformationRepository extends AbstractRepository
{
    public function getContent($type)
    {
        $information = new Information();
        return $information->getContent($type);
    }

    public function getList($type)
    {
        $information = new Information();
        return $information->getList($type);
    }
    public function getElement($id)
    {
        $information = new Information();
        return $information->getElement($id);
    }

    public function delete($id) {

        $information = new Information();
        return $information->delete($id);
    }

}