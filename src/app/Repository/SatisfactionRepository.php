<?php

namespace App\Repository;

use App\Models\Satisfaction;

class SatisfactionRepository extends AbstractRepository
{
    public function create($options)
    {
        return (new Satisfaction())->create($options);
    }

    public function get($nni, $cockpitId)
    {
        return (new Satisfaction())->get($nni, $cockpitId);
    }

    public function getList()
    {
        $status = [
            'special' => "Mention spéciale",
            'good_job' => "Bon travail, professionnalisme à la hauteur ",
            'complicated' => "Dossier compliqué à faire aboutir ",
            'not_expected' => "Ce n'est pas à l'attendu",
        ];

        $list =  (new Satisfaction())->getList();

        foreach ($list as $k => $l) {
            $list[$k]['accompaniement'] =  $status[$l['accompaniement']];
            $list[$k]['contractual'] =  $status[$l['contractual']];
            $list[$k]['timing'] =  $status[$l['timing']];
        }

        return $list;
    }

}
