<?php

namespace App\Manager\Migrate;

use App\Manager\AbstractManager;
use Exception;

class MigrateManager extends AbstractManager {

    protected $subject;
    public static $MigrateConfig = [
        'source' => '',
        'dait' =>
        [
            'contrat' => 'Contrats_DAIT/',
            'doc_dep' => 'Doc_Dep_DAIT/',
        ],
        'dapi' =>
        [
            'contrat' => 'Contrats_DAPI/',
            'doc_dep' => 'Doc_Dep_DAPI/',
        ],
        'datp' =>
        [
            'contrat' => [
                'edf_enedis' => 'Contrats_EDF_Enedis/',
                'enedis' => 'Contrats_Enedis/',
            ],
            'doc_dep' => 'Doc_Dep_DATP/',
        ]
    ];

    public function updateData($options) {
        try {
            $this->db->getPDO()->beginTransaction();
            $this->subject->initData();
            $i = 0;
            foreach ($options['content'] as $line) {
                if ($i == 0) {
                    $i++;
                    continue;
                }

                $data = array_map(function ($data) {
                    $text = $data;
                    if (is_array($data)) {
                        $text = "";
                        foreach ($data["richText"] as $key => $c) {
                            if ($key !== 0) {
                                $text .= '. ';
                            }
                            $text .= $c['text'];
                        }
                    }
                    try {
                        return (string) ($text);
                    } catch (\Exception $exception) {
                        return $text;
                    }
                }, $line);
                $values = implode("','", $data);
                $this->subject->insertData($values);
            }

            $this->db->getPDO()->commit();
        } catch (Exception $e) {
            if ($this->db->getPDO()->inTransaction()) {
                $this->db->getPDO()->rollback();
            }
            throw new Exception($e->getMessage());
        }
    }

}
