<?php

namespace App\Repository;

use App\Models\Information;
use App\Models\UserNews;

class InformationRepository extends AbstractRepository
{
    public function getContent($type, $nni)
    {
        $information = new Information();
        $informations = $information->getActiveInformations($type);

        $userNews = new UserNews();
        foreach ($informations as $info) {
            if ($userNews->checkNni($info['id'], $nni, 'home_page') <= 0) {
                return $info;
            }
        }
        
        return null;
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