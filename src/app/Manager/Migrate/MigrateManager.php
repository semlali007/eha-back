<?php

namespace App\Manager\Migrate;

use App\Manager\AbstractManager;
use Exception;

class MigrateManager extends AbstractManager
{
    protected $subject;
    
    public static $MigrateConfig = [
        'source' => CONFIG_MIGRATION_SOURCE,
        'dait' =>
        [
            'contrat' => CONFIG_MIGRATION_SOURCE . 'Contrats_DAIT/',
            'doc_dep' => CONFIG_MIGRATION_SOURCE . 'Doc_Dep_DAIT/',
        ],
        'dapi' =>
        [
            'contrat' => CONFIG_MIGRATION_SOURCE . 'Contrats_DAPI/',
            'doc_dep' => CONFIG_MIGRATION_SOURCE . 'Doc_Dep_DAPI/',
        ],
        'datp' =>
        [
            'contrat' => [
                'edf_enedis' => CONFIG_MIGRATION_SOURCE . 'Contrats_EDF_Enedis/',
                'enedis' => CONFIG_MIGRATION_SOURCE . 'Contrats_Enedis/',
            ],
            'doc_dep' => CONFIG_MIGRATION_SOURCE . 'Doc_Dep_DATP/',
        ]
    ];

    public function updateData($options)
    {
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
                    if (!is_array($data)) {
                        return (string)pg_escape_string($data);
                    } else {

                        $text = "";
                        foreach ($data["richText"] as $key => $c) {

                            if ($key !== 0) {
                                $text .= '. ';
                            }
                            $text .= $c['text'];
                        }

                        return  (string)pg_escape_string($text);
                    }
                }, $line);
                $values  = implode("','", $data);
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
