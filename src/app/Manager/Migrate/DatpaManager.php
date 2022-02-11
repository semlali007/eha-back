<?php

namespace App\Manager\Migrate;

use App\Manager\SheetManager;
use App\Models\Area;
use App\Models\Beneficiary;
use App\Models\Group;
use App\Models\Legacy\LegacyDatpa;
use App\Models\Legacy\LegacyDatpb;
use App\Models\Provider;
use Exception;

class DatpaManager extends MigrateManager
{
    public function saveData($options)
    {
        $this->subject = new LegacyDatpa();
        $this->updateData($options);
    }

    public function migrate($options)
    {
        $migrateConfig = self::$MigrateConfig;
        $sourceContractEnedis =  $_ENV["CONFIG_MIGRATION_SOURCE"].$migrateConfig['datp']['contrat']['enedis'] . "Actifs/";
        $sourceContractEdfEnedis =  $_ENV["CONFIG_MIGRATION_SOURCE"].$migrateConfig['datp']['contrat']['edf_enedis'] . "Actifs/";
        $sourceDepDoc =  $_ENV["CONFIG_MIGRATION_SOURCE"].$migrateConfig['datp']['doc_dep'] . "Actifs/";
        $destinationMigrationPath =  getenv("CONFIG_UPLOAD_PATH");
       
        try {
            $fiche = new LegacyDatpa();
            $provider = new Provider();
            $zone = new Area();
            $group = new Group();
            $filiale = new Beneficiary();
            $sheetManager = new SheetManager();
            date_default_timezone_set('Europe/Paris');
            $date = date('Y-m-d H:i:s');
            $code = "" ;
              
            foreach ($options['items'] as $id) {
                $list = $fiche->get($id);
              
                try {
                    $message = '';
                    $status =  'success';
                    $data['providers'] = [];
                    $providers = $list['fournisseurs'];
                    foreach (explode(';#', $providers) as $item) {
                        foreach (explode(';#', $providers) as $item) {
                            $item = trim($item);

                            if (is_numeric($item)  && !empty($item)) {
                                $code = 'NA' . $item;
                            }
                            if (!is_numeric($item)  && !empty($item)) {
                                $provider->add(['provider_label' => substr($item, 0, 50), 'provider_code' => $code]);
                                $p = $provider->get(['provider_label' => $item, 'provider_code' => $code]);
                                if (count($p) > 0) {
                                    $data['providers'][] = ['id' => $p[0]['provider_code']];
                                }
                            }
                        }
                    }

                    $data['filiales'] = [];
                    $filiales = $list['beneficiaires'];
                    foreach (explode(';#', $filiales) as $item) {
                        if (!is_numeric($item)  && !empty($item)) {
                            $filiale->add(['name' => $item]);
                            $p = $filiale->get(['name' => $item]);
                            if (count($p) > 0) {
                                $data['filiales'][] = ['id' => $p[0]['id']];
                            }
                        }
                    }

                    $data['groups'] = [];
                    $groups = $list['da_groupe'];
                    foreach (explode(';#', $groups) as $item) {
                        if (!is_numeric($item)  && !empty($item)) {
                            if ($item === 'DAIT' || $item === 'DATP') {
                                $item = 'DOA TP&IT';
                            }

                            $group->add(['name' => $item]);
                            $p = $group->get(['name' => $item]);
                            if (count($p) > 0) {
                                $data['groups'][] = ['id' => $p[0]['id']];
                            }
                        }
                    }

                    $data['netachat_id'] = $list['id'] ?? '';
                    $data['label'] = $list['titre'] ?? '';
                    $data['description'] = $list['description_du_marche'] ?? '';

                    $origDateDebut = trim($list['date_de_debut']);
                    $dateDebut = str_replace('/', '-', $origDateDebut);
                    $newDateDebut = date("Y-m-d", strtotime($dateDebut));

                    $origDateFin = trim($list['date_de_fin']);
                    $dateFin = str_replace('/', '-', $origDateFin);
                    $newDateFin = date("Y-m-d", strtotime($dateFin));
                    $data['start_date'] = $newDateDebut;
                    $data['end_date'] = $newDateFin;

                    $data['dauphin'] = boolval($list['approvisionnement_sous_dauphin']);

                    $data['status'] = '1';

                    $data['created_at'] = $date;
                    $data['updated_at'] = $date;
                    $data['created_by'] ='MIGRATION';
                    $data['updated_by'] = 'MIGRATION';
                    $data['created_by_full_name'] = 'MIGRATION';
                    $data['updated_by_full_name'] = 'MIGRATION';


                    $data['lots'] = [];
                    $data['files'] = [];

                    if (!empty($list['kit_de_deploiement_datp'])) {
                        if (is_dir($sourceDepDoc . $list['kit_de_deploiement_datp'])) {
                            $docDep = scandir($sourceDepDoc . $list['kit_de_deploiement_datp']);
                            if ($docDep !== false) {
                                foreach ($docDep as $fileName) {
                                    if ('.' !== $fileName && '..' !== $fileName) {
                                        $ext    =   pathinfo($fileName, PATHINFO_EXTENSION);
                                        $name    =   pathinfo($fileName, PATHINFO_FILENAME);
                                        $destFileName = uniqid('D_' . $name . '_') . '.' . $ext;
                                        $path = $sourceDepDoc . $list['kit_de_deploiement_datp'] . '/' . basename($fileName);
                                        if (copy($path, $destinationMigrationPath . $destFileName)) {
                                            $data['files'][] = ['file_name' => $destFileName, 'file_url' => $destFileName, 'file_size' => 12333, 'node_id' => null, 'type' => 1];
                                        } else {
                                            $message .= 'erreur copy de fichier : ' . $path . '||' . $destinationMigrationPath . $destFileName;
                                            $status =  'warning';
                                        }
                                    }
                                }
                            } else {
                                $message .= 'erreur lecture de kit_de_deploiement_datp: ' . $sourceDepDoc . $list['kit_de_deploiement_datp'];
                                $status =  'warning';
                            }
                        } else {
                            $message .= 'kit_de_deploiement_datp existe pas :' . $list['kit_de_deploiement_datp'];
                            $status =  'warning';
                        }
                    }


                    if (!empty($list['contrats_enedis'])) {
                        if (is_dir($sourceContractEnedis . $list['contrats_enedis'])) {
                            $docContrat = scandir($sourceContractEnedis . $list['contrats_enedis']);
                            if ($docContrat !== false) {
                                foreach ($docContrat as $fileName) {
                                    if ('.' !== $fileName && '..' !== $fileName) {
                                        $ext    =   pathinfo($fileName, PATHINFO_EXTENSION);
                                        $name    =   pathinfo($fileName, PATHINFO_FILENAME);
                                        $destFileName = uniqid('CPE_' . $name . '_') . '.' . $ext;
                                        $path = $sourceContractEnedis . $list['contrats_enedis'] . '/' . basename($fileName);
                                        if (copy($path, $destinationMigrationPath . $destFileName)) {
                                            $data['files'][] = ['file_name' => $destFileName, 'file_url' => $destFileName, 'file_size' => 12333, 'node_id' => null, 'type' => 3];
                                        } else {
                                            throw new Exception('erreur copy de fichier : ' . $path . '||' . $destinationMigrationPath . $destFileName);
                                        }
                                    }
                                }
                            } else {
                                $message .= 'erreur lecture de contrats_enedis: ' . $sourceContractEnedis . $list['contrats_enedis'];
                                $status =  'warning';
                            }
                        } else {
                            $message .= 'contrats_enedis existe pas :' . $list['contrats_enedis'];
                            $status =  'warning';
                        }
                    }

                    if (!empty($list['contrats_groupe_edf_enedis'])) {
                        if (is_dir($sourceContractEdfEnedis . $list['contrats_groupe_edf_enedis'])) {
                            $docContrat = scandir($sourceContractEdfEnedis . $list['contrats_groupe_edf_enedis']);
                            if ($docContrat !== false) {
                                foreach ($docContrat as $fileName) {
                                    if ('.' !== $fileName && '..' !== $fileName) {
                                        $ext    =   pathinfo($fileName, PATHINFO_EXTENSION);
                                        $name    =   pathinfo($fileName, PATHINFO_FILENAME);
                                        $destFileName = uniqid('CPEE_' . $name . '_') . '.' . $ext;
                                        $path = $sourceContractEdfEnedis . $list['contrats_groupe_edf_enedis'] . '/' . basename($fileName);
                                        if (copy($path, $destinationMigrationPath . $destFileName)) {
                                            $data['files'][] = ['file_name' => $destFileName, 'file_url' => $destFileName, 'file_size' => 12333, 'node_id' => null, 'type' => 4];
                                        } else {
                                            throw new Exception('erreur copy de fichier : ' . $path . '||' . $destinationMigrationPath . $destFileName);
                                        }
                                    }
                                }
                            } else {
                                $message .= 'erreur lecture de contrats_groupe_edf_enedis: ' . $sourceContractEdfEnedis . $list['contrats_groupe_edf_enedis'];
                                $status =  'warning';
                            }
                        } else {
                            $message .= 'contrats_groupe_edf_enedis existe pas :' . $list['contrats_groupe_edf_enedis'];
                            $status =  'warning';
                        }
                    }


                    $ficheDatpb = new LegacyDatpb();
                    $datpb = $ficheDatpb->get($id);
                    $data['contracts'] =  [];
                    $contenu = isset($datpb['contenu']) ? $datpb['contenu'] : '';
                    preg_match_all("/C[a-zA-Z_0-9]{9}/", $contenu, $matches, PREG_PATTERN_ORDER);
                    foreach ($matches[0] as $code) {
                        if (preg_match("#\d#", $code)) {
                            $data['contracts'][] = [
                                'contract_code' => $code,
                                'provider' => '',
                                'lots' => [],
                            ];
                        }
                    }

                    $data['edf_open'] = boolval($datpb['marche_ouvert_au_personnel_edf']);
                    $data['showcase'] = false;
                    $data['partnership'] = boolval($datpb['partenariat_productivite']);
                    $data['protected_sector'] = boolval($datpb['secteur_protege']);
                    $data['stipulation_for_others'] = boolval($datpb['stipulation_pour_autrui']);
                    $data['zones'] = [];
                    $zones = $datpb['zone_geographique'];
                    foreach (explode(';#', $zones) as $item) {
                        if (!is_numeric($item)  && !empty($item)) {
                            $zone->add(['name' => $item]);
                            $p = $zone->get(['name' => $item]);
                            if (count($p) > 0) {
                                $data['zones'][] = ['id' => $p[0]['id']];
                            }
                        }
                    }
                    $ficheId = $sheetManager->addSheet($data);
                } catch (Exception $e) {
                    $status =  'error';
                    $message = $e->getMessage();
                }

                $fiche->update($id, ['type_d_element' => $status, 'chemin_d_acces' => $message]);
            }
           
            return json_encode([
                'data' => $id,
                'data' => $data
            ]);
        } catch (Exception $ex) {
            http_response_code(500);
            return json_encode([
                'error' => $ex->getCode(),
                'message' => $ex->getMessage(),
            ]);
        }
    }
}
