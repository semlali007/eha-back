<?php

namespace App\Manager\Migrate;

use App\Manager\SheetManager;
use App\Models\Area;
use App\Models\Beneficiary;
use App\Models\Group;
use App\Models\Legacy\LegacyDapi;
use App\Models\Provider;
use Exception;

class DapiManager extends MigrateManager
{
    public function saveData($options)
    {
        $this->subject = new LegacyDapi();
        $this->updateData($options);
    }
    public function migrate($options)
    {

        $migrateConfig = self::$MigrateConfig;
        $sourceContract =  $migrateConfig['dapi']['contrat'] . "Actifs/";
        $sourceDepDoc =  $migrateConfig['dapi']['doc_dep'] . "Actifs/";
        $destinationMigrationPath =  getenv("CONFIG_UPLOAD_PATH");

        try {

            $fiche = new LegacyDapi();
            $provider = new Provider();
            $zone = new Area();
            $group = new Group();
            $filiale = new Beneficiary();
            $sheetManager = new SheetManager();
        
            foreach ($options['items'] as $id) {
                $list = $fiche->get($id);
                try {
                    $message = '';
                    $status =  'success';
                    $data['providers'] = [];
                    $providers = $list['fournisseurs'];
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
        
                    $data['zones'] = [];
                    $zones = $list['zone_geographique'];
                    foreach (explode(';#', $zones) as $item) {
                        if (!is_numeric($item)  && !empty($item)) {
                            $zone->add(['name' => $item]);
                            $p = $zone->get(['name' => $item]);
                            if (count($p) > 0) {
                                $data['zones'][] = ['id' => $p[0]['id']];
                            }
                        }
                    }
        
                    $data['filiales'] = [];
                    $filiales = $list['beneficiaires'];
                    foreach (explode(';#', $filiales) as $item) {
                        if (!is_numeric($item) && !empty($item)) {
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
                            if ($item === 'DAPI') {
                                $item = 'DOA PRODUCTION';
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
                    $dateDebut = str_replace('/', '-', $origDateDebut );
                    $newDateDebut = date("Y-m-d", strtotime($dateDebut));
        
                    $origDateFin = trim($list['date_de_fin']);
                    $dateFin = str_replace('/', '-', $origDateFin );
                    $newDateFin = date("Y-m-d", strtotime($dateFin));
                    $data['start_date'] = $newDateDebut;
                    $data['end_date'] = $newDateFin;
        
        
        
                    $data['dauphin'] = boolval($list['approvisionnement_sous_dauphin']);
                    $data['edf_open'] = boolval($list['marche_ouvert_au_personnel_edf']);
                    $data['showcase'] = false;
                    $data['partnership'] = boolval($list['partenariat_productivite']);
                    $data['protected_sector'] = boolval($list['secteur_protege']);
                    $data['stipulation_for_others'] = boolval($list['stipulation_pour_autrui']);
        
                    $data['status'] = '1';
                    date_default_timezone_set('Europe/Paris');
                    $date = date('Y-m-d H:i:s');
                    $data['created_at'] = $date;
                    $data['updated_at'] = $date;
                    $data['created_by'] ='MIGRATION';
                    $data['updated_by'] = 'MIGRATION';
                    $data['created_by_full_name'] = 'MIGRATION';
                    $data['updated_by_full_name'] = 'MIGRATION';
                    $data['contracts'] =  [];
                    preg_match_all("/C[a-zA-Z_0-9]{9}/", $list['contenu'], $matches, PREG_PATTERN_ORDER);
                    foreach ($matches[0] as $code) {
                        if (preg_match("#\d#", $code)) {
                            $data['contracts'][] = [
                                'contract_code' => $code,
                                'provider' => '',
                                'lots' => [],
                            ];
                        }
                    }
        
                    $data['lots'] = [];
                    $data['files'] = [];
                    if(!empty($list['kit_de_deploiement_dapi']) ){
        
                    if (is_dir($sourceDepDoc . $list['kit_de_deploiement_dapi'])) {
                        $docDep = scandir($sourceDepDoc . $list['kit_de_deploiement_dapi']);
                        if ($docDep !== false) {
                            foreach ($docDep as $fileName) {
                                if ('.' !== $fileName && '..' !== $fileName) {
                                    $ext    =   pathinfo($fileName, PATHINFO_EXTENSION);
                                    $name    =   pathinfo($fileName, PATHINFO_FILENAME);
                                    $destFileName = uniqid('D_' . $name . '_') . '.' . $ext;
                                    $path = $sourceDepDoc . $list['kit_de_deploiement_dapi'] . '/' . basename($fileName);
                                    if (copy($path, $destinationMigrationPath . $destFileName)){
                                        $data['files'][] = ['file_name' => $destFileName, 'file_url' => $destFileName, 'file_size' => 12333, 'node_id' => null, 'type' => 1];
                                    }else{
                                        $message .= 'erreur copy de fichier : '.$path.'||'. $destinationMigrationPath . $destFileName;
                                        $status =  'warning';
                                    }
                                }
                            }
                        }else{
                            $message .= 'erreur lecture de kit_de_deploiement_dapi: '.$sourceDepDoc . $list['kit_de_deploiement_dapi'];
                            $status =  'warning';
                        }
                    } else {
                        $message .= 'kit_de_deploiement_dapi existe pas :' . $list['kit_de_deploiement_dapi'];
                        $status =  'warning';
                    }
                }
        
                    if(!empty($list['contenu_prive_dapi']) ){
                                   if ( is_dir($sourceContract . $list['contenu_prive_dapi'])) {
                        $docContrat = scandir($sourceContract . $list['contenu_prive_dapi']);
                        if ($docContrat !== false) {
                            foreach ($docContrat as $fileName) {
                                if ('.' !== $fileName && '..' !== $fileName) {
                                    $ext    =   pathinfo($fileName, PATHINFO_EXTENSION);
                                    $name    =   pathinfo($fileName, PATHINFO_FILENAME);
                                    $destFileName = uniqid('CP_' . $name . '_') . '.' . $ext;
                                    $path = $sourceContract . $list['contenu_prive_dapi'] . '/' . basename($fileName);
                                    if (copy($path, $destinationMigrationPath . $destFileName)){
                                         $data['files'][] = ['file_name' => $destFileName, 'file_url' => $destFileName, 'file_size' => 12333, 'node_id' => null, 'type' => 2];
                                    }else{
                                    
                                        $message .= 'erreur copy de fichier : '.$path.'||'. $destinationMigrationPath . $destFileName;
                                        $status =  'warning';
                                    }
                                }
                            }
                        }else{
                            $message .= 'erreur lecture de contenu_prive_dapi: '.$sourceContract . $list['contenu_prive_dapi'];
                            $status =  'warning';
                        }
                    } else {
                        $message .= 'contenu_prive_dapi existe pas :' . $list['contenu_prive_dapi'];
                        $status =  'warning';
                    } 
                    }
        
                    $sheetManager->addSheet($data);
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
