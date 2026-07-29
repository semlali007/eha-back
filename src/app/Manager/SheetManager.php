<?php

namespace App\Manager;

use App\Exceptions\SheetException;
use App\Models\Sheet;
use App\Models\SheetArea;
use App\Models\SheetBeneficiary;
use App\Models\SheetStipulation;
use App\Models\SheetContract;
use App\Models\SheetFile;
use App\Models\SheetGroup;
use App\Models\SheetLot;
use App\Models\SheetOption;
use App\Models\SheetProvider;
use App\Models\Subscription;
use App\Services\FileUpload;
use App\Services\UserService;
use Exception;
use App\Security\User;
use App\Models\Provider;
use App\Models\Beneficiary;
use App\Models\Stipulation;
use App\Models\Area;
use App\Models\Category;
use App\Models\Committee;
use App\Models\Group;
use phpDocumentor\Reflection\Types\Null_;
use App\Models\Domain;
use App\Models\SheetDomain;
use App\Models\SheetLogs;
use App\Models\SheetSubDomain;
use App\Models\SubDomain;
use Core\Util\Arrays;
use PhpParser\Comment;

class SheetManager extends AbstractManager
{


    public function update($user, $sheetData)
    {
        $dirPath = getenv("CONFIG_UPLOAD_PATH");
        try {
            
            // Ensure contrat_groupe has a default value
            if (!isset($sheetData['contrat_groupe'])) {
                $sheetData['contrat_groupe'] = 'false';
            }
            
            $data['id'] = $sheetData['id'];
            $data['label'] = $sheetData['label'] ?? '';
            $data['description'] = $sheetData['description'] ?? '';
            $data['content'] = $sheetData['content'] ?? '';
            $data['words'] = $sheetData['words'] ?? '';
            $data['start_date'] = ($sheetData['start_date'] && $sheetData['start_date'] != '')? $sheetData['start_date'] : null;
            $data['end_date'] = ($sheetData['end_date'] && $sheetData['end_date'] != '') ?$sheetData['end_date'] : null;
            $data['dauphin'] = $sheetData['dauphin'];
            $data['edf_open'] = $sheetData['edf_open'];
            $data['showcase'] = $sheetData['showcase'];
            $data['committee'] = $sheetData['committee'] ?? 0;
            $data['category'] = $sheetData['category'] ?? 0;
            $data['stipulation_for_others'] = $sheetData['stipulation_for_others'];
            $data['partnership'] = $sheetData['partnership'];
            $data['protected_sector'] = $sheetData['protected_sector'];
            $data['contrat_groupe'] = $sheetData['contrat_groupe'] ?? 'false';
            $data['mise_a_jour_donnee'] = $sheetData['mise_a_jour_donnee'] ?? 'non';
            $data['id_supplier_size'] = $sheetData['id_supplier_size'] ?? null;
            $data['supplier_vat_number'] = $sheetData['supplier_vat_number'] ?? null;
            $data['contract_manager'] = $sheetData['contract_manager'] ?? null;
            $data['pcm_link'] = $sheetData['pcm_link'] ?? null;
            $data['entites_beneficiaires_edf_enedis'] = $sheetData['entites_beneficiaires_edf_enedis'] ?? null;
            $data['geographic_detail'] = $sheetData['geographic_detail'] ?? null;
            $data['acheteur'] = $sheetData['acheteur'] ?? null;
            $data['status'] = $sheetData['status'] ?? '0';

            if (isset($sheetData['draft']) && $sheetData['draft'] == true) {
                $data['status'] = '0';
            } else {
                if (empty($data['label'])) {
                    throw SheetException::forSheetSave('Le titre est obligatoire');
                }
            }
            
            date_default_timezone_set('Europe/Paris');
            $date = date('Y-m-d H:i:s');
            $data['updated_at'] = $date;
            $data['updated_by'] = $user->getNni();
            $data['updated_by_full_name'] = $user->getFullname() ?: $user->getNni();
            $data['contracts'] = $sheetData['contracts'] ?? [];
            $fileUpload = new FileUpload();

            $data['files'] = $fileUpload->uploadSheetFiles([
                'dirpath' => $dirPath,
                'extensions' => ['doc', 'docx', 'pdf', 'ppt', 'pptx', 'xls', 'xlsx'],
                'size' => 21000000
            ]);

            $data['lots'] = $sheetData['lots'] ?? [];
            $data['filiales'] = $sheetData['filiales'] ?? [];
            $data['filiales0'] = $sheetData['filiales0'] ?? [];//...
            $data['providers'] = $sheetData['providers'] ?? [];
            $data['groups'] = $sheetData['groups'] ?? [];
            $data['domains'] = $sheetData['domains'] ?? [];
            $data['subdomains'] = $sheetData['subdomains'] ?? [];
            $data['zones'] = $sheetData['zones'] ?? [];
            $data['deleted_files'] = $sheetData['deletedFiles'] ?? [];
            $data['options'] = $sheetData['options'] ?? [];

            $sheet = new Sheet();
            $commitee = new Committee();
            $category = new Category();

            try {
                $id = $data['id'];
                $previousSheet = $sheet->get($id);
                $this->db->getPDO()->beginTransaction();
                $sheet->update($data);
                $currentSheet = $sheet->get($id);
                $labelsFields =[
                    'status'=>'Statut',
                    'committee'=>'Comité de Gestion de la Demande',
                    'dauphin'=>'Approvisionnement sous dauphin',
                    'edf_open'=>'Marché ouvert au personnel EDF',
                    'stipulation_for_others'=>'Stipulationautrui',
                    'partnership'=>'Partenariat de productivité',
                    'protected_sector'=>'Secteur protégé',
                    'label'=>'Titre',
                    'start_date'=>'Date de début de validité',
                    'end_date'=>'Date de fin de validité',
                    'description'=>'Commentaires',
                    'search_words'=>'Mots clés',
                    'content'=>'Contenu',
                    'category'=>'Catégorie Achats',
                    'contrat_groupe'=>'Contrat ouvert aux filiales'
                ];
                $diff = array_diff_assoc($previousSheet, $currentSheet);
                $logs = new SheetLogs();
                $status = [0 => 'En attente', 1 => 'Publiée', '-1' => 'Archivée'];
                foreach ($diff as $k => $d) {
                    $diffLogs = [];
                    $diffLogs['source'] = $id;
                   
                    $diffLogs['old'] = $previousSheet[$k] ?? '';
                    $diffLogs['new'] = $currentSheet[$k] ?? '';
                    $diffLogs['date'] = $date;

                    switch ($k) {
                        case 'updated_at':
                        case 'updated_by':
                        case 'updated_by_full_name':
                            continue 2;
                        case 'content':
                            continue 2;
                            break;
                        case 'status':
                            $diffLogs['old'] = $status[$previousSheet[$k] ?? 0] ?? 'Inconnu';
                            $diffLogs['new'] = $status[$currentSheet[$k] ?? 0] ?? 'Inconnu';
                            break;
                        case 'committee':
                            $prevCommitteeCode = $previousSheet[$k] ?? 0;
                            $currCommitteeCode = $currentSheet[$k] ?? 0;
                            if (is_numeric($prevCommitteeCode) && $prevCommitteeCode > 1) {
                                $diffLogs['old'] = $commitee->getByCode($prevCommitteeCode)['label'];
                            }

                            if (is_numeric($currCommitteeCode) && $currCommitteeCode > 1) {
                                $diffLogs['new'] = $commitee->getByCode($currCommitteeCode)['label'];
                            }
                            break;

                        case 'category':
                            $prevCategoryCode = $previousSheet[$k] ?? 0;
                            $currCategoryCode = $currentSheet[$k] ?? 0;
                            if (is_numeric($prevCategoryCode) && $prevCategoryCode >= 1) {
                                $diffLogs['old'] = $category->getByCode($prevCategoryCode)['label'];
                            }

                            if (is_numeric($currCategoryCode) && $currCategoryCode >= 1) {
                                $diffLogs['new'] = $category->getByCode($currCategoryCode)['label'];
                            }
                            break;
                        case 'dauphin':
                        case 'edf_open':
                        case 'stipulation_for_others':
                        case 'partnership':
                        case 'protected_sector':
                        case 'contrat_groupe':
                            $oldValue = $previousSheet[$k] ?? false;
                            $newValue = $currentSheet[$k] ?? false;
                            if ($k === 'contrat_groupe') {
                                $diffLogs['old'] = ($oldValue === 'oui' || $oldValue === 'true' || $oldValue === true) ? 'Oui' : 'Non';
                                $diffLogs['new'] = ($newValue === 'oui' || $newValue === 'true' || $newValue === true) ? 'Oui' : 'Non';
                            } else {
                                $diffLogs['old'] = ($oldValue == 1) ? 'Oui' : 'Non';
                                $diffLogs['new'] = ($newValue == 1) ? 'Oui' : 'Non';
                            }
                            break;
                    }

                    $diffLogs['fields'] = $labelsFields[$k] ?? $k;
                    $logs->add($diffLogs);
                }

                $sheetFile = new SheetFile();
                foreach ($data['files'] as $file) {
                    if (isset($file['file_name']) && isset($file['file_url'])) {
                        $sheetFile->add($id, $file);
                    }
                }

                foreach ($data['deleted_files'] as $deleted) {
                    $sheetFile->delete($deleted['file_id']);
                }
                //*
                $sheetContract = new SheetContract();
                $previousContracts = $sheetContract->getBySheet($id);
                
                $sheetContract->deleteContracts($id, $data['contracts']);
                
                foreach ($data['contracts'] as $contract) {
                    $sheetContract->add($id, $contract);
                }
                
                $currentsContracts = $sheetContract->getBySheet($id);
                
                if (
                    is_array(Arrays::arrayDiffAssocRecursive($currentsContracts, $previousContracts)) ||
                    is_array(Arrays::arrayDiffAssocRecursive($previousContracts, $currentsContracts))
                ) {
                    $diffLogs = [];
                    $diffLogs['source'] = $id;
                    $old = '<ul>';
                    foreach ($previousContracts as $p) {
                        $old .= "<li >";
                        $old .= "contract:" . $p['contract_code'] . " Fournisseur :" . $p['provider'] . " contacts :" . $p['contacts'] . " lots :" . $p['lots'];
                        $old .= "</li >";
                    }
                    $old .= '</ul>';
                
                    $new = '<ul>';
                    foreach ($currentsContracts as $p) {
                        $new .= "<li >";
                        $new .= "contract:" . $p['contract_code'] . " Fournisseur :" . $p['provider'] . " contacts :" . $p['contacts'] . " lots :" . $p['lots'];
                        $new .= "</li >";
                    }
                    $new .= '</ul>';
                
                    $diffLogs['old'] =  $old;
                    $diffLogs['new'] = $new;
                    $diffLogs['date'] = $date;
                    $diffLogs['fields'] =  'Contrats';
                    $logs->add($diffLogs);
                }
                
            //     $sheetContract = new SheetContract();
            //     $previousContracts  = $sheetContract->getBySheet($id);
               

            //     $sheetLot = new SheetLot();


            //     foreach ($previousContracts as $k => $c) {
            //         $previousContracts[$k]['lots'] = $sheetLot->getByContract($id, $c['contract_code']);
            //     }

            //     $sheetContract->deleteContracts($id, $data['contracts']);

            //     foreach ($data['contracts'] as $contract) {
            //         $lots = [];
            //         $sheetContract->add($id, $contract);

            //         foreach ($contract['lots'] as $lot) {
            //             $lots[] = $lot['name'];
            //             $sheetLot->addLot($id, $contract['contract_code'], $lot['name']);
            //         }

            //         $sheetLot->deleteContractLots($id, $contract['contract_code'], $lots);
            //     }
            //     $currentsContracts  = $sheetContract->getBySheet($id);
            //     foreach ($currentsContracts as $k => $c) {
            //         $currentsContracts[$k]['lots'] = $sheetLot->getByContract($id, $c['contract_code']);
            //     }
              

            //     if(is_array(Arrays::arrayDiffAssocRecursive($currentsContracts, $previousContracts))
            //     || is_array(Arrays::arrayDiffAssocRecursive($previousContracts, $currentsContracts))){
            //        $diffLogs = [];
            //        $diffLogs['source'] = $id;
            //        $old = '<ul>';
            //         foreach ($previousContracts as $p) {
            //             $old .= "<li >";

            //               $old .="contract:". $p['contract_code'] ." Fournisseur :" . $p['provider'] ." contacts :" . $p['contacts']."lots:";
            //               foreach ($p['lots'] as $p) {
            //                 $old .= ", "
            //                     . $p['name'] ;
            //             }
            //                 $old .= "</li >";
            //         }

            //        $old .= '</ul>';
            //        $new = '<ul>';
            //         foreach ($currentsContracts as $p) {
            //             $new .= "<li >";

            //               $new .="contract:". $p['contract_code'] ." Fournisseur :" . $p['provider'] ." contacts :" . $p['contacts']."lots:";
            //               foreach ($p['lots'] as $p) {
            //                 $new .= ", "
            //                     . $p['name'] ;
            //             }
            //                 $new .= "</li >";
            //         }

            //        $new .= '</ul>';
            //        $diffLogs['old'] =  $old;
            //        $diffLogs['new'] = $new;
            //        $diffLogs['date'] = $date;
            //        $diffLogs['fields'] =  'Contrats';
            //        $logs->add($diffLogs);
            //    }


                $sheetFiliale = new SheetBeneficiary();
                $previousFiliale  = $sheetFiliale->getBySheet($id);
                $filialesId = [];
                foreach ($data['filiales'] as $f) {
                    $filialesId[] = $f['id'];
                    $sheetFiliale->add($id, $f['id']);
                }
                $sheetFiliale->deleteFiliales($id, $filialesId);

                $currentFiliale  = $sheetFiliale->getBySheet($id);

                if(is_array(Arrays::arrayDiffAssocRecursive($currentFiliale, $previousFiliale))
                || is_array(Arrays::arrayDiffAssocRecursive($previousFiliale, $currentFiliale))){
                   $diffLogs = [];
                   $diffLogs['source'] = $id;
                   $old = '<ul>';
                   foreach ($previousFiliale as $p) {
                       $old .= "<li >"
                               .$p['name'].
                               "</li>";
                   }

                   $old .= '<ul>';
                   $new = '<ul>';

                   foreach ($currentFiliale as $p) {
                       $new .= "<li >"
                           . $p['name'] .
                           "</li>";
                   }

                   $new .= '<ul>';
                   $diffLogs['old'] =  $old;
                   $diffLogs['new'] = $new;
                   $diffLogs['date'] = $date;
                   $diffLogs['fields'] =  'Bénéficiaires';
                   $logs->add($diffLogs);
               }

                $sheetFiliale0 = new SheetStipulation();
                $previousFiliale0  = $sheetFiliale0->getBySheet($id);
                $filiales0Id = [];
                foreach ($data['filiales0'] as $f) {
                    $filiales0Id[] = $f['id'];
                    $sheetFiliale0->add($id, $f['id']);
                }
                $sheetFiliale0->deleteFiliales($id, $filiales0Id);

                $currentFiliale0  = $sheetFiliale0->getBySheet($id);


                if(is_array(Arrays::arrayDiffAssocRecursive($currentFiliale0, $previousFiliale0))
                || is_array(Arrays::arrayDiffAssocRecursive($previousFiliale0, $currentFiliale0))){
                   $diffLogs = [];
                   $diffLogs['source'] = $id;
                   $old = '<ul>';
                   foreach ($previousFiliale0 as $p) {
                       $old .= "<li >"
                               .$p['name'].
                               "</li>";
                   }

                   $old .= '<ul>';
                   $new = '<ul>';

                   foreach ($currentFiliale0 as $p) {
                       $new .= "<li >"
                           . $p['name'] .
                           "</li>";
                   }

                   $new .= '<ul>';
                   $diffLogs['old'] =  $old;
                   $diffLogs['new'] = $new;
                   $diffLogs['date'] = $date;
                   $diffLogs['fields'] =  'Stipulation pour autrui';
                   $logs->add($diffLogs);
               }

                $sheetProvider = new SheetProvider();

                $previousProviders = $sheetProvider->getBySheet($id);
                $providers = [];
                foreach ($data['providers'] as $p) {
                    $providers[] = $p['id'];
                    $sheetProvider->add($id, $p['id']);
                }

                $sheetProvider->deleteProviders($id, $providers);
                $currentProviders = $sheetProvider->getBySheet($id);
                if(is_array(Arrays::arrayDiffAssocRecursive($currentProviders, $previousProviders))
                 || is_array(Arrays::arrayDiffAssocRecursive($previousProviders, $currentProviders))){
                    $diffLogs = [];
                    $diffLogs['source'] = $id;
                    $old = '<ul>';
                    foreach ($previousProviders as $p) {
                        $old .= "<li >"
                                .$p['name'].
                                "</li>";
                    }

                    $old .= '<ul>';
                    $new = '<ul>';

                    foreach ($currentProviders as $p) {
                        $new .= "<li >"
                            . $p['name'] .
                            "</li>";
                    }

                    $new .= '<ul>';
                    $diffLogs['old'] =  $old;
                    $diffLogs['new'] = $new;
                    $diffLogs['date'] = $date;
                    $diffLogs['fields'] =  'Fournisseurs';
                    $logs->add($diffLogs);
                }
                $sheetGroup = new SheetGroup();
                $previousGroups  = $sheetGroup->getBySheet($id);
                $groups = [];
                foreach ($data['groups'] as $p) {
                    $groups[] = $p['id'];
                    $sheetGroup->add($id, $p['id']);
                }
                $sheetGroup->deleteGroups($id, $groups);

                $currentGroups  = $sheetGroup->getBySheet($id);

                if(is_array(Arrays::arrayDiffAssocRecursive($currentGroups, $previousGroups))
                || is_array(Arrays::arrayDiffAssocRecursive($previousGroups, $currentGroups))){
                   $diffLogs = [];
                   $diffLogs['source'] = $id;
                   $old = '<ul>';
                   foreach ($previousGroups as $p) {
                       $old .= "<li >"
                               .$p['name'].
                               "</li>";
                   }

                   $old .= '<ul>';
                   $new = '<ul>';

                   foreach ($currentGroups as $p) {
                       $new .= "<li >"
                           . $p['name'] .
                           "</li>";
                   }

                   $new .= '<ul>';
                   $diffLogs['old'] =  $old;
                   $diffLogs['new'] = $new;
                   $diffLogs['date'] = $date;
                   $diffLogs['fields'] =  'Directions opérationnelles (DOA)';
                   $logs->add($diffLogs);
               }

                $sheetDomain = new SheetDomain();
                $previousDomains  = $sheetDomain->getBySheet($id);
                $domains = [];
                foreach ($data['domains'] as $d) {
                    $domains[] = $d['code'];
                    $sheetDomain->add($id, $d['code']);
                }
                $sheetDomain->deleteDomains($id, $domains);


                $currentDomains  = $sheetDomain->getBySheet($id);

                if(is_array(Arrays::arrayDiffAssocRecursive($currentDomains, $previousDomains))
                || is_array(Arrays::arrayDiffAssocRecursive($previousDomains, $currentDomains))){
                   $diffLogs = [];
                   $diffLogs['source'] = $id;
                   $old = '<ul>';
                   foreach ($previousDomains as $p) {
                       $old .= "<li >"
                               .$p['name'].
                               "</li>";
                   }

                   $old .= '<ul>';
                   $new = '<ul>';

                   foreach ($currentDomains as $p) {
                       $new .= "<li >"
                           . $p['name'] .
                           "</li>";
                   }

                   $new .= '<ul>';
                   $diffLogs['old'] =  $old;
                   $diffLogs['new'] = $new;
                   $diffLogs['date'] = $date;
                   $diffLogs['fields'] =  'Segments';
                   $logs->add($diffLogs);
               }

                $sheetSubDomain = new SheetSubDomain();
                $previousSubDomains  = $sheetSubDomain->getBySheet($id);
                $subDomains = [];
                foreach ($data['subdomains'] as $d) {
                    $subDomains[] = $d['code'];
                    $sheetSubDomain->add($id, $d['code']);
                }
                $sheetSubDomain->deleteSubDomains($id, $subDomains);


                $currentSubDomains  = $sheetSubDomain->getBySheet($id);

                if(is_array(Arrays::arrayDiffAssocRecursive($currentSubDomains, $previousSubDomains))
                || is_array(Arrays::arrayDiffAssocRecursive($previousSubDomains, $currentSubDomains))){
                   $diffLogs = [];
                   $diffLogs['source'] = $id;
                   $old = '<ul>';
                   foreach ($previousSubDomains as $p) {
                       $old .= "<li >"
                               .$p['name'].
                               "</li>";
                   }

                   $old .= '<ul>';
                   $new = '<ul>';

                   foreach ($currentSubDomains as $p) {
                       $new .= "<li >"
                           . $p['name'] .
                           "</li>";
                   }

                   $new .= '<ul>';
                   $diffLogs['old'] =  $old;
                   $diffLogs['new'] = $new;
                   $diffLogs['date'] = $date;
                   $diffLogs['fields'] =  'Sous-ensemble';
                   $logs->add($diffLogs);
               }

                $sheetZone = new SheetArea();
                $previousZones  = $sheetZone->getBySheet($id);
                $zones = [];
                foreach ($data['zones'] as $p) {
                    $zones[] = $p['id'];
                    $sheetZone->add($id, $p['id']);
                }
                $sheetZone->deleteZones($id, $zones);
                $currentsZones  = $sheetZone->getBySheet($id);

                if(is_array(Arrays::arrayDiffAssocRecursive($currentsZones, $previousZones))
                || is_array(Arrays::arrayDiffAssocRecursive($previousZones, $currentsZones))){
                   $diffLogs = [];
                   $diffLogs['source'] = $id;
                   $old = '<ul>';
                   foreach ($previousZones as $p) {
                       $old .= "<li >"
                               .$p['name'].
                               "</li>";
                   }

                   $old .= '<ul>';
                   $new = '<ul>';

                   foreach ($currentsZones as $p) {
                       $new .= "<li >"
                           . $p['name'] .
                           "</li>";
                   }

                   $new .= '<ul>';
                   $diffLogs['old'] =  $old;
                   $diffLogs['new'] = $new;
                   $diffLogs['date'] = $date;
                   $diffLogs['fields'] =  'Zone géographique d’application des marchés';
                   $logs->add($diffLogs);
               }
                $sheetLot = new SheetLot();
                $lots = [];
                foreach ($data['lots'] as $lot) {
                    $lots[] = $lot['name'];
                    $sheetLot->add($id, $lot);
                }

                $sheetLot->deleteLots($id, $lots);

                $sheetOption = new SheetOption();
                $previousOptions = $sheetOption->getBySheet($id);
                $options = [];
                foreach ($data['options'] as $option) {
                    if (isset($option['id'])) {
                        $sheetOption->update($option);
                        $options[] = $option['id'];
                    } else {
                        $option['sheet_id'] = $id;
                        $options[] = $sheetOption->add($option);
                    }
                }

                $allOptions = $sheetOption->getBySheet($id);

                foreach ($allOptions as $op) {
                    if (!in_array($op['id'], $options)) {
                        $sheetOption->delete($op['id']);
                    }
                }
                $currentOptions = $sheetOption->getBySheet($id);

                $diffOptions = Arrays::arrayDiffAssocRecursive($currentOptions, $previousOptions);
                if(is_array($diffOptions)){
                    $diffLogs = [];
                    $diffLogs['source'] = $id;
                   
                    $old = '<ul>';
                    $c=1;
                    foreach ($previousOptions as $p) {
                        $old .= "<li >
                                <label>Option  $c : du ".$p['start_date']." au ".$p['start_date']."</label>
                                </li>";
                                $c++;
                    }
                    $old .= '<ul>';

                    $new = '<ul>';
                    $cn=1;
                    foreach ($currentOptions as $co) {
                        $new .= "<li >
                                <label>Option  $cn : du ".$co['start_date']." au ".$co['start_date']."</label>
                                </li>";
                                $cn++;
                    }
                    $new .= '<ul>';
                    $diffLogs['old'] =  $old;
                    $diffLogs['new'] = $new;
                    $diffLogs['date'] = $date;
                    $diffLogs['fields'] =  'Options';
                    $logs->add($diffLogs);
                }
                $this->db->getPDO()->commit();
            } catch (\Exception $e) {
                if ($this->db->getPDO()->inTransaction()) {
                    $this->db->getPDO()->rollback();
                }
                throw SheetException::forSheetSave($e->getMessage());
            }

            return json_encode([
                'data' => $data,
                'warning' => $fileUpload->deleteFilesFromFolder($dirPath, $data['deleted_files'])
            ]);
        } catch (Exception $ex) {
            http_response_code(500);
            return json_encode([
                'status' => 500,
                'error' => $ex->getMessage(),
                'warning' => (new FileUpload())->deleteFilesFromFolder($dirPath, $data['files'] ?? [])
            ]);
        }
    }

    

    public function add(User $user, $sheetData) {
        $dirPath = getenv("CONFIG_UPLOAD_PATH");

        try {
            $data['label'] = $sheetData['label'] ?? '';
            $data['description'] = $sheetData['description'] ?? '';
            $data['words'] = $sheetData['words'] ?? '';
            $data['start_date'] = ($sheetData['start_date'] && $sheetData['start_date'] != '') ? $sheetData['start_date']: null;
            $data['end_date'] = ($sheetData['end_date'] && $sheetData['end_date'] != '') ?$sheetData['end_date']: null;
            $data['dauphin'] = $sheetData['dauphin'];
            $data['committee'] = $sheetData['committee'] ?? 0;
            $data['category'] = $sheetData['category'] ?? 0;
            $data['contrat_groupe'] = $sheetData['contrat_groupe'] ?? 'false';
            $data['mise_a_jour_donnee'] = $sheetData['mise_a_jour_donnee'] ?? 'non';
            $data['id_supplier_size'] = $sheetData['id_supplier_size'] ?? null;
            $data['supplier_vat_number'] = $sheetData['supplier_vat_number'] ?? null;
            $data['contract_manager'] = $sheetData['contract_manager'] ?? null;
            $data['pcm_link'] = $sheetData['pcm_link'] ?? null;
            $data['entites_beneficiaires_edf_enedis'] = $sheetData['entites_beneficiaires_edf_enedis'] ?? null;
            $data['geographic_detail'] = $sheetData['geographic_detail'] ?? null;
            $data['acheteur'] = $sheetData['acheteur'] ?? null;
            $data['edf_open'] = $sheetData['edf_open'];
            $data['showcase'] = $sheetData['showcase'];
            $data['partnership'] = $sheetData['partnership'];
            $data['protected_sector'] = $sheetData['protected_sector'];
            $data['filiales'] = $sheetData['filiales'] ?? [];
            $data['filiales0'] = $sheetData['filiales0'] ?? [];
            $data['providers'] = $sheetData['providers'] ?? [];
            $data['zones'] = $sheetData['zones'] ?? [];
            $data['groups'] = $sheetData['groups'] ?? [];
            $data['stipulation_for_others'] = $sheetData['stipulation_for_others'];
            $data['options'] = $sheetData['options'] ?? [];
            $data['status'] = $sheetData['status'] ?? '0';
            if (isset($sheetData['draft']) && $sheetData['draft'] == true) {
                $data['status'] = '0';
            } else {
                if (empty($data['label'])) {
                    throw SheetException::forSheetSave('Le titre est obligatoire');
                }
            }
            date_default_timezone_set('Europe/Paris');
            $date = date('Y-m-d H:i:s');
            $data['created_at'] = $date;
            $data['updated_at'] = $date;
            
            // Vérifier si c'est un appel API externe avec override utilisateur
            $isExternalApiCall = isset($sheetData['external_api_call']) && $sheetData['external_api_call'] === true;
            
            if ($isExternalApiCall) {
                // Override des champs utilisateur pour les appels API externes (depuis le token JWT)
                $this->overrideUserFieldsFromJson($data, $sheetData);
            } else {
                // Comportement normal : utiliser l'utilisateur authentifié
                $fullName = $user->getFullname() ?: $user->getNni();
                $data['created_by'] = $user->getNni();
                $data['updated_by'] = $user->getNni();
                $data['created_by_full_name'] = $fullName;
                $data['updated_by_full_name'] = $fullName;
            }
            
            $data['contracts'] = $sheetData['contracts'] ?? [];
            $data['lots'] = $sheetData['lots'] ?? [];
            $data['domains'] = $sheetData['domains'] ?? [];
            $data['subdomains'] =$sheetData['subdomains'] ?? [];

            $fileUpload = new FileUpload();
            
            $fileUpload = new FileUpload();
            
            $data['files'] = $fileUpload->uploadSheetFiles([
                'dirpath' => $dirPath,
                'extensions' => ['doc', 'docx', 'pdf', 'ppt', 'pptx', 'xls', 'xlsx'],
                'size' => 21000000
            ]);


            $id = $this->addSheet($data);
            
            return json_encode([
                'id' => $id,
                'data' => $data
            ]);
        } catch (Exception $ex) {
            http_response_code(500);
            return json_encode([
                'status' => 500,
                'error' => $ex->getMessage(),
                'warning' => (new FileUpload())->deleteFilesFromFolder($dirPath, $data['files'] ?? [])
            ]);
        }
    }

    /**
     * Override des champs utilisateur pour les appels API externes
     * Les informations utilisateur (user_nni, user_full_name) proviennent du token JWT 
     * et sont ajoutées par le contrôleur avant d'arriver ici
     * Gère aussi la création des fichiers physiques à partir du base64
     */
    private function overrideUserFieldsFromJson(&$data, $sheetData)
    {
        // Vérification que les données utilisateur du JWT sont présentes
        if (!isset($sheetData['user_nni']) || empty($sheetData['user_nni'])) {
            throw new Exception("Données utilisateur manquantes dans les données JWT (user_nni requis)");
        }
        
        $userNni = $sheetData['user_nni'];
        $userFullName = $sheetData['user_full_name'] ?? $userNni;
        
        // Validation du NNI (provenant du token JWT décodé)
        if (!$this->validateUserOverride($userNni)) {
            throw new Exception("NNI utilisateur non autorisé ou invalide: " . $userNni);
        }
        
        // Override des champs utilisateur avec les données du token JWT
        $data['created_by'] = $userNni;
        $data['updated_by'] = $userNni;
        $data['created_by_full_name'] = $userFullName;
        $data['updated_by_full_name'] = $userFullName;
        
        // Gestion des fichiers kits - création physique pour simuler un upload normal
        if (isset($sheetData['kits']) && is_array($sheetData['kits'])) {
            $this->createPhysicalFilesFromBase64($sheetData['kits']);
        }
    }

    /**
     * Valide que l'utilisateur peut être utilisé en override
     */
    private function validateUserOverride($nni)
    {
        // Validation 2: Liste d'utilisateurs autorisés depuis les variables d'environnement
        $authorizedOverrideUsersEnv = getenv('COCKPIT_API_AUTHORIZED_USERS');
        $authorizedOverrideUsers = [];
        
        if ($authorizedOverrideUsersEnv) {
            // Format attendu: "A67890,B12345" (séparés par des virgules)
            $authorizedOverrideUsers = array_map('trim', explode(',', $authorizedOverrideUsersEnv));
            $authorizedOverrideUsers = array_filter($authorizedOverrideUsers); // Supprimer les entrées vides
        }
        
        // Si la liste est définie et non vide, vérifier l'autorisation
        if (!empty($authorizedOverrideUsers) && !in_array($nni, $authorizedOverrideUsers)) {
            return false;
        }
        
        return true;
    }

    /**
     * Crée des fichiers physiques à partir des données base64 OU des chemins locaux
     * Les fichiers sont créés dans un dossier temporaire et $_FILES est rempli
     */
    private function createPhysicalFilesFromBase64($filesData)
    {
        if (!is_array($filesData) || empty($filesData)) {
            return;
        }

        $tempFiles = [];
        $processedFiles = [];

        foreach ($filesData as $index => $fileData) {
            try {
                // Validation des données du fichier
                if (!isset($fileData['name'])) {
                    continue;
                }

                // Sécurisation du nom de fichier : supprimer toute séquence de traversée
                $fileName = basename($fileData['name']);
                $decodedContent = null;
                $mimeType = null;

                // Méthode 1: Chemin local (recommandé)
                if (isset($fileData['path']) && !empty($fileData['path'])) {
                    $localPath = $fileData['path'];

                    // Bloquer les wrappers PHP (php://, data://, expect://, http://, ftp://, etc.)
                    if (preg_match('#^[a-zA-Z][a-zA-Z0-9+.\-]*://#', $localPath)) {
                        continue;
                    }

                    // Vérifier que le chemin ne contient pas de séquences de traversée
                    if (strpos($localPath, '..') !== false) {
                        continue;
                    }

                    if (!file_exists($localPath)) {
                        continue;
                    }

                    // Vérifier que c'est un fichier régulier (pas un lien symbolique, un répertoire, etc.)
                    if (!is_file($localPath) || is_link($localPath)) {
                        continue;
                    }

                    if (!is_readable($localPath)) {
                        continue;
                    }

                    // Valider que le chemin résolu est dans un répertoire autorisé
                    if (!$this->isAllowedSourcePath($localPath)) {
                        continue;
                    }

                    // Utiliser un contexte stream qui interdit les URL pour bloquer toute SSRF
                    $streamContext = stream_context_create([
                        'http' => ['ignore_errors' => true],
                        'ssl' => ['verify_peer' => false],
                    ]);
                    // Double vérification : s'assurer que le chemin n'est pas une URL
                    if (filter_var($localPath, FILTER_VALIDATE_URL)) {
                        continue;
                    }

                    $decodedContent = file_get_contents($localPath);
                    if ($decodedContent === false) {
                        continue;
                    }

                    // Détection automatique du type MIME
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mimeType = finfo_file($finfo, $localPath);
                    finfo_close($finfo);
                    
                // Méthode 2: Base64 (fallback)
                } elseif (isset($fileData['content']) && isset($fileData['type'])) {
                    $fileContent = $fileData['content'];
                    $mimeType = $fileData['type'];

                    // Décodage base64
                    $decodedContent = base64_decode($fileContent, true);
                    if ($decodedContent === false) {
                        continue;
                    }
                    
                } else {
                    continue;
                }

                // Validation du nom de fichier
                if (!$this->isValidFileName($fileName)) {
                    continue;
                }

                // Validation du type MIME
                if (!$this->isAllowedMimeType($mimeType)) {
                    continue;
                }

                // Validation de la taille (20MB max)
                if (strlen($decodedContent) > 21000000) {
                    continue;
                }

                // Création du fichier temporaire
                $tempFile = tempnam(sys_get_temp_dir(), 'eha_api_');
                if ($tempFile === false) {
                    continue;
                }

                // Écriture du contenu
                if (file_put_contents($tempFile, $decodedContent) === false) {
                    unlink($tempFile);
                    continue;
                }

                $tempFiles[] = $tempFile;

                // Préparation des données pour $_FILES
                $processedFiles[] = [
                    'name' => $fileName,
                    'type' => $mimeType,
                    'tmp_name' => $tempFile,
                    'error' => UPLOAD_ERR_OK,
                    'size' => strlen($decodedContent)
                ];

            } catch (Exception $e) {
                continue;
            }
        }

        // Simulation de $_FILES pour que FileUpload fonctionne normalement
        if (!empty($processedFiles)) {
            $this->simulateFilesUpload($processedFiles);
        }

        // Nettoyage des fichiers temporaires
        register_shutdown_function(function() use ($tempFiles) {
            foreach ($tempFiles as $tempFile) {
                if (file_exists($tempFile)) {
                    unlink($tempFile);
                }
            }
        });
    }

    /**
     * Simule $_FILES pour que le système d'upload normal fonctionne
     */
    private function simulateFilesUpload($filesData)
    {
        // Utiliser 'kits' au lieu de 'files' car c'est ce que uploadSheetFiles() attend
        $_FILES['kits'] = [
            'name' => [],
            'type' => [],
            'tmp_name' => [],
            'error' => [],
            'size' => []
        ];

        foreach ($filesData as $index => $fileData) {
            $_FILES['kits']['name'][] = $fileData['name'];
            $_FILES['kits']['type'][] = $fileData['type'];
            $_FILES['kits']['tmp_name'][] = $fileData['tmp_name'];
            $_FILES['kits']['error'][] = $fileData['error'];
            $_FILES['kits']['size'][] = $fileData['size'];
        }
    }

    public function addSheet($data) {
        $sheet = new Sheet();
        try {
            $this->db->getPDO()->beginTransaction();
            $id = $sheet->add($data);
            $sheetFile = new SheetFile();
            foreach ($data['files'] as $file) {
                if (isset($file['file_name']) && isset($file['file_url'])) {
                    $sheetFile->add($id, $file);
                }
            }

            // $sheetLot = new SheetLot();
            $sheetContract = new SheetContract();
            foreach ($data['contracts'] as $contract) {
                if (isset($contract['contract_code'])) {
                    $sheetContract->add($id, $contract);
                    // foreach ($contract['lots'] as $lot) {
                    //     $sheetLot->addLot($id, $contract['contract_code'], $lot['name']);
                    // }
                }
            }

            $sheetFiliale = new SheetBeneficiary();
            foreach ($data['filiales'] as $filiale) {
                if (isset($filiale['id'])) {
                    $sheetFiliale->add($id, $filiale['id']);
                }
            }
            if (array_key_exists('filiales0', $data)) {
                $sheetFiliale0 = new SheetStipulation();
                foreach ($data['filiales0'] as $filiale0) {
                    if (isset($filiale0['id'])) {
                        $sheetFiliale0->add($id, $filiale0['id']);
                    }
                }
            }
            // if (array_key_exists('filiales0', $data)) {
            //     $sheetFiliale0 = new SheetStipulation();
            //     $stipulation = [];
            //     foreach ($data['filiales0'] as $filiale0) {
            //         $stipulation[] = $filiale0['id'];
            //         $sheetFiliale0->add($id, $filiale0['id']);
            //     }
            // }

            $sheetProvider = new SheetProvider();
            foreach ($data['providers'] as $provider) {
                if (isset($provider['id'])) {
                    $sheetProvider->add($id, $provider['id']);
                }
            }

            $sheetZone = new SheetArea();
            foreach ($data['zones'] as $zone) {
                if (isset($zone['id'])) {
                    $sheetZone->add($id, $zone['id']);
                }
            }

            $sheetGroup = new SheetGroup();
            foreach ($data['groups'] as $group) {
                if (isset($group['id'])) {
                    $sheetGroup->add($id, $group['id']);
                }
            }

            // $lots = [];
            $sheetLot = new SheetLot();
            foreach ($data['lots'] as $lot) {
                // $lots[] = $lot['name'];
                $sheetLot->add($id, $lot);
            }

            $sheetOption = new SheetOption();
            if (array_key_exists('options', $data)) {
                foreach ($data['options'] as $option) {
                    $option['sheet_id'] = $id;
                    $sheetOption->add($option);
                }
            }

            if (array_key_exists('domains', $data)) {
            $sheetDomain = new SheetDomain();
                $domains = [];
                foreach ($data['domains'] as $d) {
                    $domains[] = $d['code'];
                    $sheetDomain->add($id, $d['code']);
                }
                $sheetDomain->deleteDomains($id, $domains);
            }
            if (array_key_exists('subdomains', $data)) {
                $sheetSubDomain = new SheetSubDomain();
                $subDomains = [];
                foreach ($data['subdomains'] as $d) {
                    $subDomains[] = $d['code'];
                    $sheetSubDomain->add($id, $d['code']);
                }
                $sheetSubDomain->deleteSubDomains($id, $subDomains);
            }

            $this->db->getPDO()->commit();
            return $id;
        } catch (\Exception $e) {
            
            if ($this->db->getPDO()->inTransaction()) {
                $this->db->getPDO()->rollback();
            }

            throw SheetException::forSheetSave($e->getMessage());
        }
    }

    
    /**
     * remove
     *
     * @param  mixed $id
     * @return void
     */
    function remove($id){
        return (new Sheet())->deleteSheet($id); 
    }

     /**
     * remove
     *
     * @param  mixed $id
     * @return void
     */
    function subscribe($sheetId, $userNni){
        return (new Subscription())->add($sheetId, $userNni);
    }

         /**
     * remove
     *
     * @param  mixed $id
     * @return void
     */
    function unsubscribe($sheetId, $userNni){
        return (new Subscription())->remove($sheetId, $userNni);
    }

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
            ,
        'datp_archivage' =>
            [
                'contrat' => [
                    'edf_enedis' => 'Contrats_EDF_Enedis/',
                    'enedis' => 'Contrats_Enedis/',
                ],
                'doc_dep' => 'Doc_Dep_DATP/',
            ]
    ];

    public function migrateDomain($type){
        $type = trim($type);
        $destinationMigrationPath =  getenv("CONFIG_UPLOAD_PATH");
        $sources = [
            "dait" => [
                "files" => ["dait1.json", "dait2.json"],
                "status" => '1'
            ],
            "dapi" => [
                "files" => ["dapi1.json", "dapi2.json"],
                "status" => '1'
            ],
            "datp" => [
                "files" => ["datp1.json", "datp2.json"],
                "status" => '1'
            ],
            "datp_archivage" => [
                "files" => ["datp_archivage1.json", "datp_archivage2.json"],
                "status" => '-1'
            ],
            "dapi_archivage" => [
                "files" => ["dapi_archivage.json"],
                "status" => '-1'
            ],
            "dait_archivage" => [
                "files" => ["dait_archivage"],
                "status" => '-1'
            ],
        ];

        if(!array_key_exists($type, $sources)){
            throw new Exception('type de fiche inconnu');
            exit;
        }

        $netachatData = [];
        foreach ($sources[$type]['files'] as $i => $f) {
            $s = json_decode(file_get_contents($destinationMigrationPath .'netachat/'.$f), true);
            $netachatData = array_merge($netachatData,$s['Row']);
        }

        $domain = new Domain();
        $subDomain = new SubDomain();
        $sheetDomain = new SheetDomain();
        $sheetSubDomain = new SheetSubDomain();
        $sheet = new Sheet();
        
        foreach ($netachatData as $data) {
            $sheetElement = $sheet->getSheetByNetAchat($data['ID']);
            if($type == 'dait'){
                $segment = $data['SegmentDAIT'];
                $subSegment = $data['SousEnsembleDAIT'];
                //$subSegement=
            }else if($type == 'dapi'){
                $segment = $data['SegmentAchatDAPI'];
                $subSegment = $data['SousEnsembleAchatDAPI'];
            }else if($type == 'datp'){
                $segment = $data['SegmentDATP'];
                $subSegment = $data['SousEnsembleDATP'];
            }

            foreach($subSegment as $sub){
                if( isset($sub['Label'])){
                $nameSubSegment = $sub['Label'];
                $p = $subDomain->get(['name' => $nameSubSegment, 'type' => $type]);
                if (is_array($p) && isset($p['id'])) {
                    $subDomainId = $p['id'];
                } else {
                    $subDomainId = $subDomain->add(['name' => $nameSubSegment, 'type' => $type]);
                }
                   
                if(is_array($sheetElement) && is_numeric($sheetElement['id']))
                $sheetSubDomain->add($sheetElement['id'], $subDomainId);
            }
            }
            

            foreach($segment as $seg){
            if(isset($seg['Label'])){
                $name = $seg['Label'];
                $p = $domain->get(['name' => $name, 'type' => $type]);
                if (is_array($p) && isset($p['id'])) {
                    $domainId = $p['id'];
                } else {
                    $domainId = $domain->add(['name' => $name, 'type' => $type]);
                }
                   
                if(is_array($sheetElement) && is_numeric($sheetElement['id']))
                $sheetDomain->add($sheetElement['id'], $domainId);
            }
            }
        }
    }


    public function migrate($type)
    {
        $type = trim($type);
        $destinationMigrationPath =  getenv("CONFIG_UPLOAD_PATH");
        $sources = [
            "dait" => [
                "files" => ["dait1.json", "dait2.json"],
                "status" => '1'
            ],
            "dapi" => [
                "files" => ["dapi1.json", "dapi2.json"],
                "status" => '1'
            ],
            "datp" => [
                "files" => ["datp1.json", "datp2.json"],
                "status" => '1'
            ],
            "datp_archivage" => [
                "files" => ["datp_archivage1.json", "datp_archivage2.json"],
                "status" => '-1'
            ],
            "dapi_archivage" => [
                "files" => ["dapi_archivage.json"],
                "status" => '-1'
            ],
            "dait_archivage" => [
                "files" => ["dait_archivage"],
                "status" => '-1'
            ],
        ];

        if(!array_key_exists($type, $sources)){
            throw new Exception('type de fiche inconnu');
            exit;
        }

        $netachatData = [];
        foreach ($sources[$type]['files'] as $i => $f) {
            $s = json_decode(file_get_contents($destinationMigrationPath .'netachat/'.$f), true);
            $netachatData = array_merge($netachatData,$s['Row']);
        }
        
       
        foreach ($netachatData as $row) {
            $this->migrateOneSheet(['sheet'=>$row ,'type'=>$type, 'status'=>$sources[$type]['status']]);
        }
    }
    public function migrateOneSheet($options)
    {
        $data = [];
        $sheet = $options['sheet'];
        $data['netachat_type'] = $options['type'];
        $data['status'] = $options['status'];
        $data['contracts'] = [];
        $data['lots'] = [];
        $data['netachat_id'] = $sheet['ID'] ?? '';
        $data['label'] = self::formateHtml($sheet['Title']);
        $data['content'] = self::formateHtml($sheet['Contenu']);
        $data['description'] = self::formateHtml($sheet['Desc']);
        $origDateDebut = trim($sheet['DateDeb']);
        $dateDebut = str_replace('/', '-', $origDateDebut);
        $newDateDebut = date("Y-m-d", strtotime($dateDebut));
        $origDateFin = trim($sheet['DateFin']);
        $dateFin = str_replace('/', '-', $origDateFin);
        $newDateFin = date("Y-m-d", strtotime($dateFin));
        $data['start_date'] = $newDateDebut;
        $data['end_date'] = $newDateFin;
        $data['dauphin'] = boolval($sheet['ApproDauphin.value']);
        $date = date('Y-m-d H:i:s');
        $data['created_at'] = $date;
        $data['updated_at'] = $date;
        $data['created_by'] = 'MIGRATION';
        $data['updated_by'] = 'MIGRATION';
        $data['created_by_full_name'] = 'MIGRATION';
        $data['updated_by_full_name'] = 'MIGRATION';
        $data['edf_open'] = boolval($sheet['MarcheOuvertPersEDF.value']);
        $data['showcase'] = false;
        $data['partnership'] = boolval($sheet['PartenariatProd.value']);
        $data['protected_sector'] = boolval($sheet['SecteurProt.value']);
        $data['stipulation_for_others'] = boolval($sheet['StipulationPourAutrui.value']);
        $data['providers'] = [];
        $data['filiales'] = [];
        $data['filiales0'] = [];
        $data['zones'] = [];
        $data['files'] = [];
        $data['groups'] = [];
        $group = new Group();
        $groupLabel = $options['type'];
        if (!is_numeric($groupLabel)  && !empty($groupLabel)) {
            if ($groupLabel === 'dapi') {
                $groupLabel = 'DOA PRODUCTION';
            }

            if ($groupLabel === 'dait' || $groupLabel === 'datp') {
                $groupLabel = 'DOA TP&IT';
            }

            $group->add(['name' => $groupLabel]);
            $p = $group->get(['name' => $groupLabel]);
            if (count($p) > 0) {
                $data['groups'][] = ['id' => $p[0]['id']];
            }
        }
        $data = array_merge($data, $this->migrateOptions($sheet));
        $data =  array_merge($data, $this->kitDep($sheet, $options['type']));
        $this->addSheet($data);
    }


    public function kitDep($list, $type)
    {
        $data = [];
        $data['files'] = [];
        $migrateConfig = self::$MigrateConfig;
        switch ($type) {
            case 'dait':
                $config = $migrateConfig['dait'];
                $sourceDepDoc =  getenv("CONFIG_MIGRATION_SOURCE") . $config['doc_dep'] . "Actifs/";
                $foldersList = $list['KitDAIT'];
                break;
            case 'dapi':
                $config = $migrateConfig['dapi'];
                $foldersList = $list['KitDAPI'];
                $sourceDepDoc =  getenv("CONFIG_MIGRATION_SOURCE") . $config['doc_dep'] . "Actifs/";
                break;
            case 'datp':
                $config = $migrateConfig['datp'];
                $foldersList = $list['KitDATP'];
                $sourceDepDoc =  getenv("CONFIG_MIGRATION_SOURCE") . $config['doc_dep'] . "Actifs/";
                break;
            case 'datp_archivage':
                $config = $migrateConfig['datp_archivage'];
                $sourceDepDoc =  getenv("CONFIG_MIGRATION_SOURCE") . $config['doc_dep'] . "Archives/";
                $foldersList = $list['KitDATP'];
                break;
            case 'dait_archivage':
                $config = $migrateConfig['dait_archivage'];
                $sourceDepDoc =  getenv("CONFIG_MIGRATION_SOURCE") . $config['doc_dep'] . "Archives/";
                $foldersList = $list['KitDAIT'];
                break;
            case 'dapi_archivage':
                $config = $migrateConfig['dapi_archivage'];
                $sourceDepDoc =  getenv("CONFIG_MIGRATION_SOURCE") . $config['doc_dep'] . "Archives/";
                $foldersList = $list['KitDAPI'];
                break;
            default:
                throw new Exception('type de contact inconnu');
                break;
        }

        $destinationMigrationPath =  getenv("CONFIG_UPLOAD_PATH");

        if(is_null($migrateConfig) || !is_array($foldersList)){
            return $data;
        }

        foreach ($foldersList as $kit) {
            $kitFolderName = str_replace(" ", "_", basename($kit['lookupValue']));
            if (!empty($kitFolderName) && strpos($kitFolderName, '..') === false) {
                if (is_dir($sourceDepDoc . $kitFolderName)) {
                    $docDep = scandir($sourceDepDoc . $kitFolderName);
                    if ($docDep !== false) {
                        foreach ($docDep as $fileName) {
                            if ('.' !== $fileName && '..' !== $fileName) {
                                $ext    =   pathinfo($fileName, PATHINFO_EXTENSION);
                                $name    =   pathinfo($fileName, PATHINFO_FILENAME);
                                $destFileName = uniqid('D_' . $name . '_') . '.' . $ext;
                                $path = $sourceDepDoc . $kitFolderName . '/' . basename($fileName);
                                if (copy($path, $destinationMigrationPath . $destFileName)) {
                                    $data['files'][] = ['file_name' => $destFileName, 'file_url' => $destFileName, 'file_size' => 12333, 'node_id' => null, 'type' => 1];
                                }
                            }
                        }
                    }
                }

            }
        }

        return $data;
    }

    public function migrateOptions($row)
    {
        $data = [];
        $data['providers'] = [];
        $data['filiales'] = [];
        $data['filiales0'] = [];
        $data['zones'] = [];
       
        $filiale = new Beneficiary();
        $filiale0 = new Stipulation();
        $provider = new Provider();
        $zone = new Area();
        

        foreach ($row['Fournisseur'] as $item) {
            if (!is_numeric($item)  && !empty($item)) {
                $p = $provider->get(['provider_label' => $item["Label"]]);
                if (count($p) > 0) {
                    $data['providers'][] = ['id' => $p[0]['provider_code']];
                }else{
                    $id = $provider->add(['provider_label' => $item["Label"]]);
                    $data['providers'][] = ['id' =>$id];
                }
            }
        }

        foreach ($row['Beneficiaires'] as $itemB) {
            
            $p = $filiale->get(['name' => $itemB['Label']]);
            if (count($p) > 0) {
                $data['filiales'][] = ['id' => $p[0]['id']];
            }else{
                $fId = $filiale->add(['name' => $itemB['Label']]);
                $data['filiales'][] = ['id' => $fId];
            }
        }

        foreach ($row['StipulationAutrui'] as $itemS) {
            
            $p = $filiale0->get(['name' => $itemS['Label']]);
            if (count($p) > 0) {
                $data['filiales0'][] = ['id' => $p[0]['id']];
            }else{
                $fId = $filiale0->add(['name' => $itemS['Label']]);
                $data['filiales0'][] = ['id' => $fId];
            }
        }


        foreach ($row['ZoneGeo'] as $itemZ) {
            
            $p = $zone->get(['name' => $itemZ['Label']]);
            if (count($p) > 0) {
                $data['zones'][] = ['id' => $p[0]['id']];
            }else{
               $zId = $zone->add(['name' => $itemZ['Label']]);
               $data['zones'][] = ['id' => $zId];
            }
        }
        return $data;
    }

    public static function formateHtml($content){
        
        $content = preg_replace('/\>\s+\</m', '><', $content);
        $content = str_replace('&nbsp;', 'replacedwords', $content);
        $content = html_entity_decode($content);
        $content = preg_replace('/\>[\x00-\x1F\x80-\xFF]+</', '><', $content);
        $content = preg_replace( '/[\x{200B}-\x{200D}]/u', '', $content );
        $content = preg_replace('/\>\s+\</m', '><', $content);
        $content = str_replace("<br>", "", $content);
        $content = str_replace("<br/>", "<br>", $content);
        $content = str_replace('<br><t', '<t', $content);
        $content = str_replace('<br></t', '</t', $content);
        $content = str_replace('&#160;', ' ', $content);
        $content = str_replace('<p>&#160;</p>', '', $content);
        $content = preg_replace('/\>\ +\</m', '><', $content);
        $content = preg_replace('/\>(replacedwords)+/', '>', $content);
        $content = preg_replace('/(replacedwords)+<\//', '</', $content);
        $content = preg_replace('/class="ms-rteTable[-0-9a-zA-Z.+_\s]+"/', 'border="1"', $content);

        $htmlReplace = [
            'replacedwords' => '',
            '<p>&#160;</p>' => '',
            '<strong>N° de contrat PGI</strong></td>' => '<p><strong>N° de contrat PGI</strong></p></td>',
            '<strong>Fournisseur</strong></td>' => '<p><strong>Fournisseur</strong></p></td>',
            '<strong>Fournisseurs</strong></td>' => '<p><strong>Fournisseurs</strong></p></td>',
            '<strong>Contact DAIT</strong></td>' => '<p><strong>Contact DAIT</strong></p></td>',
            '<strong>N° de contrat</strong></td>' => '<p><strong>N° de contrat</strong></p></td>',
            '<strong>contacts</strong></td>' => '<p><strong>contacts</strong></p></td>',
            '<strong>Contact</strong></td>' => '<p><strong>Contact</strong></p></td>',
            '<strong>N° de marché</strong></td>' => '<p><strong>N° de marché</strong></p></td>',
            '<strong>N° Marché</strong></td>' => '<p><strong>N° Marché</strong></p></td>',
            '<strong>Contact fournisseur</strong></td>' => '<p><strong>Contact fournisseur</strong></p></td>',
            '<strong>Interlocuteurs</strong></td>' => '<p><strong>Interlocuteurs</strong></p></td>',
            '<strong>Contacts fournisseur</strong></td>' => '<p><strong>Contacts fournisseur</strong></p></td>',
            '<strong>No de marché</strong></td>' => '<p><strong>No de marché</strong></p></td>',
            '<strong>ENEDIS n° de contrat</strong></td>' => '<p><strong>ENEDIS n° de contrat</strong></p></td>',
            '<strong>Fournisseur</strong></td>' => '<p><strong>Fournisseur</strong></p></td>',
            '<strong>EDF HYDRO N° de contrat</strong></td>' => '<p><strong>EDF HYDRO N° de contrat</strong></p></td>',
            '<strong>Contact </strong></td>' => '<p><strong>Contact</strong></p></td>',
            '&#39;' => "'",
            'class="ms-rteTable-8"' => 'border="1"'
        ];

        foreach ($htmlReplace as $src => $dest) {
            $content = str_replace($src, $dest, $content);
        }
        
        preg_match_all('/<a href="mailto:[-0-9a-zA-Z.+_]+@[-0-9a-zA-Z.+_]+.[a-zA-Z]+">mailto:[-0-9a-zA-Z.+_]+@[-0-9a-zA-Z.+_]+.[a-zA-Z]+<\/a>/', $content, $emails, PREG_PATTERN_ORDER);
        foreach ($emails[0] as $code) {
            if (preg_match("/@/", $code)) {
                $explode = explode('"', $code);
                $content = str_replace($code, $explode[1], $content);
            }
        }

        preg_match_all('/(<a href="<a href=").*("\>).*(<\/a\>")/', $content, $tttt, PREG_PATTERN_ORDER);
      
        foreach ($tttt[0] as $code) {
            
            if (preg_match('/<a href="<a href="/', $code)) {
                $c = substr($code, 17);
                $explode = explode('"', $c);
                $content = str_replace($code, '<a href="' . $explode[1] . '"', $content);
            }
        }

       
        preg_match_all("/C[a-zA-Z_0-9]{9}</", $content, $matches1, PREG_PATTERN_ORDER);

        foreach ($matches1[0] as $code) {
            if (preg_match("#\d#", $code)) {
                $c = substr($code, 0, -1);
                $content = str_replace($code, '<a href="app/contrat/details/' . $c . '" target="_blank" rel="noopener noreferrer" class="link">' . $c . '</a><', $content);
            }
        }

        preg_match_all("/C[a-zA-Z_0-9]{9}\s/", $content, $matches, PREG_PATTERN_ORDER);

        foreach ($matches[0] as $code) {
            if (preg_match("#\d#", $code)) {
                $c = substr($code, 0, -1);
                $content = str_replace($code, ' <a href="app/contrat/details/' . $code . '" target="_blank" rel="noopener noreferrer" class="link">' . $c . '</a> ', $content);
            }
        }

        preg_match_all("/C[a-zA-Z_0-9]{8}<strong>[a-zA-Z_0-9]{1}<\/strong>/", $content, $matches2, PREG_PATTERN_ORDER);

        foreach ($matches2[0] as $code) {
            if (preg_match("#\d#", $code)) {
                $c = substr($code, 0, -18);
                $i = substr($code, 17, -9);
                $content = str_replace($code, '<a href="app/contrat/details/' . $c . $i . '" target="_blank" rel="noopener noreferrer" class="link">' . $code . '</a>', $content);
            }
        }

        preg_match_all('/C[a-zA-Z_0-9]{8}<span class="ms-rteForeColor-2">[a-zA-Z_0-9]{1}<\/span>/', $content, $matches22, PREG_PATTERN_ORDER);

        foreach ($matches22[0] as $code) {
            if (preg_match("#\d#", $code)) {
                $c = substr($code, 0, 9);
                $i = substr($code, -8, -7);
                $content = str_replace($code, '<a href="app/contrat/details/' . $c . $i . '" target="_blank" rel="noopener noreferrer" class="link">' . $code . '</a>', $content);
            }
        }
        
        return $content;

    }

    /**
     * Vérifie que le chemin source est dans un répertoire autorisé.
     * Protection contre l'Absolute Path Traversal et la lecture de fichiers arbitraires.
     *
     * @param string $filePath
     * @return bool
     */
    private function isAllowedSourcePath($filePath)
    {
        $realPath = realpath($filePath);
        if ($realPath === false) {
            return false;
        }

        // Liste des répertoires autorisés pour la lecture de fichiers source
        $allowedDirs = [];

        // Répertoire d'upload configuré
        $uploadPath = getenv('CONFIG_UPLOAD_PATH');
        if ($uploadPath) {
            $realUploadPath = realpath($uploadPath);
            if ($realUploadPath !== false) {
                $allowedDirs[] = $realUploadPath;
            }
        }

        // Répertoire temporaire système
        $tempDir = realpath(sys_get_temp_dir());
        if ($tempDir !== false) {
            $allowedDirs[] = $tempDir;
        }

        // Vérifier que le chemin résolu est dans un des répertoires autorisés
        foreach ($allowedDirs as $allowedDir) {
            if (strpos($realPath, $allowedDir . DIRECTORY_SEPARATOR) === 0 || $realPath === $allowedDir) {
                return true;
            }
        }

        return false;
    }

    /**
     * Valide le nom de fichier pour la sécurité
     */
    private function isValidFileName($fileName)
    {
        // Interdire les caractères dangereux et les chemins relatifs
        $dangerousChars = ['..', '/', '\\', '<', '>', ':', '"', '|', '?', '*'];
        foreach ($dangerousChars as $char) {
            if (strpos($fileName, $char) !== false) {
                return false;
            }
        }
        
        // Vérifier que le nom n'est pas vide et a une extension
        return !empty($fileName) && strpos($fileName, '.') !== false;
    }

    /**
     * Vérifie si le type MIME est autorisé
     */
    private function isAllowedMimeType($mimeType)
    {
        $allowedTypes = [
            'application/pdf',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-excel',
            'application/msword',
            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'text/plain',
            'text/csv',
            'image/jpeg',
            'image/png',
            'image/gif'
        ];

        return in_array($mimeType, $allowedTypes);
    }

    public function getNetAchatSheet(){
        $sheet = new Sheet();
        return $sheet->getNetAchatSheet();
    }
}
