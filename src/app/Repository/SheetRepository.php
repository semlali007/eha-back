<?php

namespace App\Repository;

use App\Models\Category;
use App\Models\Committee;
use App\Models\Provider;
use App\Models\SearchAssociated;
use App\Models\SearchNoList;
use App\Models\SearchSynonymous;
use App\Models\Sheet;
use App\Models\SheetArea;
use App\Models\SheetBeneficiary;
use App\Models\SheetStipulation;
use App\Models\SheetContract;
use App\Models\SheetDomain;
use App\Models\SheetFile;
use App\Models\SheetGroup;
use App\Models\SheetLogs;
use App\Models\SheetLot;
use App\Models\SheetOption;
use App\Models\SheetProvider;
use App\Models\SheetSubDomain;
use App\Models\Subscription;
use Core\Util\{Date,Math, Arrays } ;
use App\Repository\ContractRepository;
use App\Services\HttpClientService;
use DOMDocument;
use Exception;

class SheetRepository extends AbstractRepository {

    public function search($options, $userNni = null) {

        try {
            $options['words'] = $this->getFormattedWords($options['search']['searchText']);
        } catch (\Exception $e) {
            $options['words'] = '';
        }
        $sheet = new Sheet();
        $options['userNni'] = $userNni;
        $sectors = $sheet->groupedBySector($options);
        $openEdfs = $sheet->groupedByOpenEdf($options);
        $dauphins = $sheet->groupedByDauphin($options);
        $partnerships = $sheet->groupedByPartnership($options);
        $status = $sheet->groupedByStatus($options);
        $directions = $sheet->groupedByDirections($options);
        $contratGroupes = $sheet->groupedByContratGroupe($options);
        $categories = $sheet->groupedByCategories($options);
        
        // Récupérer les données mise à jour donnée avec gestion d'erreur
        $miseAJourDonnees = [];
        try {
            $miseAJourDonnees = $sheet->groupedByMiseAJourDonnee($options);
        } catch (\Exception $e) {
            // Si la colonne n'existe pas encore, on retourne un tableau vide
            $miseAJourDonnees = [];
        }
        
        // Récupérer les données supplier size avec gestion d'erreur
        $supplierSizes = [];
        try {
            $supplierSizes = $sheet->groupedBySupplierSize($options);
        } catch (\Exception $e) {
            // Si la colonne n'existe pas encore, on retourne un tableau vide
            $supplierSizes = [];
        }
       
        return
                [
                    'sheets' => $sheet->search($options, $userNni),
                    'total' => $sheet->getCount($options),
                    'filter' => [
                        'subscribe' => $sheet->groupedBySubscribed($options),
                        'edf_open' =>  self::addAttribute($openEdfs),
                        'showcase' => $sheet->groupedByShowcase($options),
                        'status' => self::addStatus($status),
                        'partnership' => self::addAttribute($partnerships),
                        'protected_sector' => self::addAttribute($sectors),
                        'dauphin' => self::addAttribute($dauphins),
                        'beneficiaries' => $sheet->groupedByFiliale($options),
                        'stipulationAutrui' => $sheet->groupedByFiliale0($options),
                        'areas' => $sheet->groupedByZone($options),
                        'providers' =>$sheet->groupedByProvider($options),
                        'directions' => self::addTooltip($directions),
                        'categories' => $categories,
                        'committees' => $sheet->groupedByCommittees($options),
                        'contratGroupe' => self::addContratGroupeAttribute($contratGroupes),
                        'miseAJourDonnee' => self::addMiseAJourDonneeAttribute($miseAJourDonnees),
                        'supplierSize' => self::addSupplierSizeAttribute($supplierSizes),
                    ],
        ];
    }


    public static function addAttribute($items){
        return self::addElements($items, [true,false]);
    }

    public static function addStatus($items){
        $statusMapping = [
            -1 => 'Archivée',
            0 => 'Brouillon', 
            1 => 'Publiée'
        ];
        
        $result = [];
        
        // Ajouter les éléments existants avec libellés
        foreach ($items as $item) {
            $result[] = [
                'code' => $item['code'],
                'label' => $statusMapping[$item['code']] ?? 'Inconnu',
                'count' => $item['count']
            ];
        }
        
        // Ajouter les statuts manquants avec count = 0
        $existingCodes = array_column($result, 'code');
        foreach ([-1, 0, 1] as $code) {
            if (!in_array($code, $existingCodes)) {
                $result[] = [
                    'code' => $code,
                    'label' => $statusMapping[$code],
                    'count' => 0
                ];
            }
        }
        
        // Trier par code pour avoir un ordre cohérent (Archivée, Brouillon, Publiée)
        usort($result, function($a, $b) {
            return $a['code'] <=> $b['code'];
        });
        
        return $result;
    }

    public static function addContratGroupeAttribute($items){
        // Map database values to display values
        $mapping = [
            'true' => 'Oui',
            'false' => 'Non'
        ];
        
        // Transform existing items
        $result = [];
        foreach ($items as $item) {
            $code = $item['code'];
            $label = isset($mapping[$code]) ? $mapping[$code] : $code;
            $result[] = [
                'code' => $code,
                'label' => $label,
                'count' => $item['count']
            ];
        }
        
        // Add missing options with count 0
        $allCodes = ['true', 'false'];
        $existingCodes = array_column($result, 'code');
        
        foreach ($allCodes as $code) {
            if (!in_array($code, $existingCodes)) {
                $label = isset($mapping[$code]) ? $mapping[$code] : $code;
                $result[] = [
                    'code' => $code,
                    'label' => $label,
                    'count' => 0
                ];
            }
        }
        
        return $result;
    }

    public static function addMiseAJourDonneeAttribute($items){
        
        // Map database values to display values
        $mapping = [
            'oui' => 'Oui',
            'en_cours' => 'En cours',
            'non' => 'Non'
        ];
        
        // Transform existing items
        $result = [];
        foreach ($items as $item) {
            $code = $item['code'];
            $label = isset($mapping[$code]) ? $mapping[$code] : $code;
            $result[] = [
                'code' => $code,
                'label' => $label,
                'count' => $item['count']
            ];
        }
        
        // Add missing options with count 0
        $allCodes = ['oui', 'en_cours', 'non'];
        $existingCodes = array_column($result, 'code');
        
        foreach ($allCodes as $code) {
            if (!in_array($code, $existingCodes)) {
                $label = isset($mapping[$code]) ? $mapping[$code] : $code;
                $result[] = [
                    'code' => $code,
                    'label' => $label,
                    'count' => 0
                ];
            }
        }
        
        return $result;
    }

    public static function addSupplierSizeAttribute($items){
        
        // Récupérer dynamiquement les tailles depuis la base de données
        $supplierSizeModel = new \App\Models\SupplierSize();
        $allSupplierSizes = $supplierSizeModel->getAll();
        
        // Créer le mapping dynamique
        $mapping = [];
        $allCodes = [];
        foreach ($allSupplierSizes as $size) {
            $mapping[$size['id']] = $size['name'];
            $allCodes[] = (string)$size['id'];
        }
        
        // Transform existing items (sans le compteur total)
        $result = [];
        foreach ($items as $item) {
            $code = $item['code'];
            $label = isset($mapping[$code]) ? $mapping[$code] : $code;
            $result[] = [
                'code' => $code,
                'label' => $label,
                'count' => $item['count']
            ];
        }
        
        // Add missing options with count 0
        $existingCodes = array_column($result, 'code');
        
        foreach ($allCodes as $code) {
            if (!in_array($code, $existingCodes)) {
                $label = isset($mapping[$code]) ? $mapping[$code] : $code;
                $result[] = [
                    'code' => $code,
                    'label' => $label,
                    'count' => 0
                ];
            }
        }
        
        return $result;
    }

    public static function addElements($firstElements, $allCodes){
        $firstElementsCode = array_column($firstElements, 'code');
         
            foreach($allCodes as $code){
                if(!in_array($code, $firstElementsCode)){
                    $firstElements[] = ["code" => $code, "count" => 0];
                }
            }
        return $firstElements;
    }

    public static function addTooltip($tab){
            $newtab = [];
            foreach($tab as $row){
                if(strtolower($row['name']) == 'doa tp&it')
                {
                    $row['tooltip'] = 'La direction opérationnelle DOA TP&IT regroupe les fiches marchés des anciens domaines DATP et DAIT.';
                }
                else if(strtolower($row['name']) == 'doa production')
                {
                    $row['tooltip'] = "La direction opérationnelle DOA Production regroupe provisoirement les fiches marchés de l'ancien domaine DAPI.";
                }
                else 
                {
                    $row['tooltip'] = '';
                }
                $newtab[] = $row;
            }
        return $newtab;
    }

    /**
     * 
     * @param int $id
     * @return mixed
     */
    public function findById(int $id): mixed {
        return (new Sheet())->get($id);
    }
    
    /**
     * 
     * @param int $endDays , default = 15
     * @return array|null
     */
    public function findByEndDateWithIn( int $endDays = 15): ?array {
        $endDate = Date::generateDateMoins($endDays) ;
        return (new Sheet()) // Generer le jour Datetime format + 30 (exemple)
                ->findByEndDateWithIn($endDate); 
    }
    
    /**
     * 
     * @param int $days , default = 30
     * @return array|null
     */
    public function findByEndDatePlus(int $days = 30): ?array {
        // Generer le jour Datetime format + 30 (exemple)
        return (new Sheet())->findByEndDate(Date::generateDateMoins($days));
    }
    
    /**
     * 
     * @param int $days
     * @return int|null
     */
    public function archiverByEndDatePlus(int $days = 25):?int{
        // Generer le jour Datetime format + 30 (exemple)
        return (new Sheet())->archiverFichesByDate(Date::generateDateMoins($days));
    }

    public function getDetails($id, $userNni = null) {
        try {
            $details = (new Sheet())->get($id);

            // Ajouter l'objet supplier_size dynamiquement depuis la base
            if (isset($details['id_supplier_size']) && !empty($details['id_supplier_size'])) {
                $supplierSizeModel = new \App\Models\SupplierSize();
                $supplierSizeData = $supplierSizeModel->getById($details['id_supplier_size']);
                
                if ($supplierSizeData) {
                    $details['supplier_size'] = [
                        'id' => (int)$details['id_supplier_size'],
                        'name' => $supplierSizeData['name']
                    ];
                } else {
                    $details['supplier_size'] = null;
                }
                // Supprimer id_supplier_size de la réponse API (gardé seulement en interne)
                unset($details['id_supplier_size']);
            } else {
                $details['supplier_size'] = null;
                // Supprimer id_supplier_size de la réponse API
                unset($details['id_supplier_size']);
            }

            $category = new Category();
            $categorySheet = $category->getByCode($details['category']);
           
            if (!is_array($categorySheet)) {
                $categorySheet = [];
            }
            
            $details['category'] = $categorySheet;

            $committee = new Committee();
            $committetSheet = $committee->getByCode($details['committee']);
           
            if (!is_array($committetSheet)) {
                $committetSheet = [];
            }
            
            $details['committee'] = $committetSheet;

 
            $sub = new Subscription();
            $details['subscribed'] = $sub->isSubscribed($id, $userNni);


            $sheetContract = new SheetContract();
            $details['contracts'] = $sheetContract->getBySheet($id);
            $sheetFiliale = new SheetBeneficiary();
            $details['filiales'] = $sheetFiliale->getBySheet($id);
            $sheetFiliale0 = new SheetStipulation();
            $details['filiales0'] =$sheetFiliale0->getBySheet($id) ;
            $sheetProvider = new SheetProvider();
            $details['providers'] = $sheetProvider->getBySheet($id);
            $sheetZone = new SheetArea();
            $details['zones'] = $sheetZone->getBySheet($id);
            $sheetGroup = new SheetGroup();
            $details['groups'] = $sheetGroup->getBySheet($id);
            $sheetLot = new SheetLot();
            $details['lots'] = $sheetLot->getBySheet($id);
            // foreach ($details['contracts'] as $k => $c) {
            //     $details['contracts'][$k]['lots'] = $sheetLot->getByContract($id, $c['contract_code']);
            // }

            $sheetFile = new SheetFile();
            $documents = $sheetFile->getBySheet($id);
            $details['documents'] = $documents;

            $sheetOption = new SheetOption();
            $details['options'] = $sheetOption->getBySheet($id);

            $sheetDomain = new SheetDomain();
            $sheetSubDomain = new SheetSubDomain();
            $details['domain'] = $sheetDomain->getBySheet($id);
            $details['subdomain'] = $sheetSubDomain->getBySheet($id);
            $sheetLogs = new SheetLogs();
           // $details['history'] = $sheetLogs->getBySheet($id);

            return $details;
        } catch (\Exception $e) {
            error_log("SheetRepository::getDetails error: " . $e->getMessage());
            throw new Exception($e->getMessage());
        }
    }

    public function getHistory($options){
        $sheetLogs = new SheetLogs();
        return ['data'=>$sheetLogs->getBySheet($options['id'], $options['page']),'total'=>$sheetLogs->countBySheet($options['id'])];
        
    }

    public function export($options, $userNni = null) {
        $options['userNni'] = $userNni;
        $options['words'] = Arrays::getFormattedWords($options['search']['searchText']);
        $status = [0 => 'En attente', 1 => 'Publiée', '-1' => 'Archivée'];
        $sheet = new Sheet();
        $answer = [];
        $sheetProvider = new SheetProvider();
        $sheetFiliale = new SheetBeneficiary();
        $sheetFiliale0 = new SheetStipulation();
        $sheetZone = new SheetArea();
        $sheetGroup = new SheetGroup();
        $committee = new Committee();
        $sheetContract = new SheetContract();
        $result = $sheet->searchWithoutPagination($options, $userNni);
        $header = [
            'N° / Compteur',
            'Nom fiche',
            'Contrat groupe',
            'Mots clés',
            'Date de début de validité',
            'Date de fin de validité',
            'Options',
            'Fournisseurs',
            'Fournisseur numéro TVA intracommunautaire',
            'Bénéficiaires',
            'Stipulation pour autrui',
            'Entités bénéficiaires d\'EDF SA / ENEDIS',
            'Numéros de contrats',
            'Kit de portage joint',
            'Pièces jointes',
            'Comité de Gestion de la Demande',
            'Zone géographique d\'application des marchés',
            'Zone géographique détail si région',
            'Approvisionnement sous dauphin',
            'Marché ouvert au personnel EDF',
            'Acheteur',
            'Contract manager',
            'Mise à jour de la donnée',
            'Statut',
            'Dernière modification',
            'Commentaires'
        ];
        $csv = [
            'header' => $header,
            'data' => []
        ];
        $sheetOption = new SheetOption();
        $sheetDomain = new SheetDomain();
        $sheetSubDomain = new SheetSubDomain();
        $compteur = 1; // Ajout du compteur
        foreach ($result['sheets'] as $sheet) {
            if (isset($sheet['committee'])) {
                $committeeItem = $committee->getByCode($sheet['committee']);
                if (!is_null($committeeItem) && is_array($committeeItem) && isset($committeeItem['label'])) {
                    $sheet['committee'] = $committeeItem['label'];
                }
            }

            $providers = $sheetProvider->getBySheet($sheet['id']);
            $sheet['providers'] = '';
                foreach ($providers as $key => $provider) {
                    if ($key !== 0) {
                        $sheet['providers'] .= '|';
                    }

                    $sheet['providers'] .= $provider['name'];
                }

            $filiales = $sheetFiliale->getBySheet($sheet['id']);
            $sheet['filiales'] = '';
            foreach ($filiales as $key => $filiale) {
                if ($key !== 0) {
                    $sheet['filiales'] .= '|';
                }

                $sheet['filiales'] .= $filiale['name'];
            }

            $filiales0 = $sheetFiliale0->getBySheet($sheet['id']);
            $sheet['filiales0'] = '';
            foreach ($filiales0 as $key => $filiale0) {
                if ($key !== 0) {
                    $sheet['filiales0'] .= '|';
                }

                $sheet['filiales0'] .= $filiale0['name'];
            }

            $zones = $sheetZone->getBySheet($sheet['id']);
            $sheet['zones'] = '';
            foreach ($zones as $key => $zone) {
                if ($key !== 0) {
                    $sheet['zones'] .= '|';
                }
                $sheet['zones'] .= $zone['name'];
            }

            $groups = $sheetGroup->getBySheet($sheet['id']);
            $sheet['groups'] = '';
            foreach ($groups as $key => $group) {
                if ($key !== 0) {
                    $sheet['groups'] .= '|';
                }
                $sheet['groups'] .= $group['name'];
            }

           
            $options = $sheetOption->getBySheet($sheet['id']);
            $sheet['options'] = '';

            foreach ($options as $key => $option) {

                if ($key !== 0) {
                    $sheet['options'] .= '|';
                }

                $startDate = date_create($option['start_date']);
                $endDate = date_create($option['end_date']);
                $sheet['options'] .= date_format($startDate, "d/m/Y") . ' - '  . date_format($endDate, "d/m/Y");
            }

            $domains = $sheetDomain->getBySheet($sheet['id']);

            $sheet['domains'] = '';
            foreach ($domains as $key => $domain) {
                if ($key !== 0) {
                    $sheet['domains'] .= '|';
                }
                $sheet['domains'] .= $domain['name'];
            }

            $subdomains = $sheetSubDomain->getBySheet($sheet['id']);

            $sheet['subdomains'] = '';
            foreach ($subdomains as $key => $subdomain) {
                if ($key !== 0) {
                    $sheet['subdomains'] .= '|';
                }
                $sheet['subdomains'] .= $subdomain['name'];
            }


            $sheet['dauphin'] = ($sheet['dauphin'] == 1) ? 'Oui' : 'Non';
            $sheet['edf_open'] = ($sheet['edf_open'] == 1) ? 'Oui' : 'Non';
            $sheet['stipulation_for_others'] = ($sheet['stipulation_for_others'] == 1) ? 'Oui' : 'Non';
            $sheet['protected_sector'] = ($sheet['protected_sector'] == 1) ? 'Oui' : 'Non';
            $sheet['partnership'] = ($sheet['partnership'] == 1) ? 'Oui' : 'Non';
            $sheet['status'] = $status[$sheet['status']];
            
            // Ajouter les nouveaux champs
            if (isset($sheet['contrat_groupe'])) {
                if ($sheet['contrat_groupe'] === 'true') {
                    $sheet['contrat_groupe'] = 'Oui';
                } elseif ($sheet['contrat_groupe'] === 'false') {
                    $sheet['contrat_groupe'] = 'Non';
                } else {
                    $sheet['contrat_groupe'] = 'Non renseigné';
                }
            } else {
                $sheet['contrat_groupe'] = 'Non renseigné';
            }
            
            if (isset($sheet['mise_a_jour_donnee'])) {
                if ($sheet['mise_a_jour_donnee'] === 'oui') {
                    $sheet['mise_a_jour_donnee'] = 'Oui';
                } elseif ($sheet['mise_a_jour_donnee'] === 'en_cours') {
                    $sheet['mise_a_jour_donnee'] = 'En cours';
                } elseif ($sheet['mise_a_jour_donnee'] === 'non') {
                    $sheet['mise_a_jour_donnee'] = 'Non';
                } else {
                    $sheet['mise_a_jour_donnee'] = 'Non renseigné';
                }
            } else {
                $sheet['mise_a_jour_donnee'] = 'Non renseigné';
            }
            
            $sheet['supplier_vat_number'] = isset($sheet['supplier_vat_number']) ? $sheet['supplier_vat_number'] : '';
            
            // Traitement de la taille du fournisseur
            $sheet['supplier_size_name'] = '';
            if (isset($sheet['id_supplier_size']) && !empty($sheet['id_supplier_size'])) {
                $supplierSizeModel = new \App\Models\SupplierSize();
                $supplierSizeData = $supplierSizeModel->getById($sheet['id_supplier_size']);
                if ($supplierSizeData) {
                    $sheet['supplier_size_name'] = $supplierSizeData['name'];
                }
            }
            
            $sheet['contract_manager'] = isset($sheet['contract_manager']) ? $sheet['contract_manager'] : '';
            $sheet['pcm_link'] = isset($sheet['pcm_link']) ? $sheet['pcm_link'] : '';
            
            // Gestion du kit de portage joint et des pièces jointes
            $sheetFileModel = new SheetFile();
            
            // Vérifier s'il y a un kit de portage (type 1)
            $kitFiles = $sheetFileModel->getBySheetAndType($sheet['id'], 1);
            $kitPortageJoint = !empty($kitFiles) ? 'Oui' : 'Non';
            
            // Récupérer toutes les pièces jointes
            $allFiles = $sheetFileModel->getAllBySheet($sheet['id']);
            $piecesJointes = '';
            if (!empty($allFiles)) {
                $fileNames = array_map(function($file) {
                    return $file['file_name'];
                }, $allFiles);
                $piecesJointes = implode(' | ', $fileNames);
            }
            
            $sheet['description'] = $this->toPlainText($sheet['description']);
            $sheet['contracts'] = '';
            $contracts = $sheetContract->getBySheet($sheet['id']);
            preg_match_all('#app/contrat/details/(.*?)\"#', $sheet['content'], $match);
            $contractCodes = [];
            
            // Préparer la ligne de données dans l'ordre du header
            $rowData = [
                $compteur, // 1. N° / Compteur
                $sheet['label'], // 2. Nom fiche
                $sheet['contrat_groupe'], // 3. Contrat groupe
                isset($sheet['keywords']) ? $sheet['keywords'] : '', // 4. Mots clés
                isset($sheet['start_date']) ? $sheet['start_date'] : '', // 5. Date de début de validité
                isset($sheet['end_date']) ? $sheet['end_date'] : '', // 6. Date de fin de validité
                $sheet['options'], // 7. Options
                $sheet['providers'], // 8. Fournisseurs
                $sheet['supplier_vat_number'], // 9. Fournisseur numéro TVA intracommunautaire
                $sheet['filiales'], // 10. Bénéficiaires
                $sheet['filiales0'], // 11. Stipulation pour autrui
                isset($sheet['entites_beneficiaires_edf_enedis']) ? $sheet['entites_beneficiaires_edf_enedis'] : '', // 12. Entités bénéficiaires d'EDF SA / ENEDIS
                '', // 13. Numéros de contrats (sera rempli plus bas)
                $kitPortageJoint, // 14. Kit de portage joint
                $piecesJointes, // 15. Pièces jointes
                isset($sheet['committee']) ? $sheet['committee'] : '', // 16. Comité de Gestion de la Demande
                $sheet['zones'], // 17. Zone géographique d'application des marchés
                isset($sheet['geographic_detail']) ? $sheet['geographic_detail'] : '', // 18. Zone géographique détail si région
                $sheet['dauphin'], // 19. Approvisionnement sous dauphin
                $sheet['edf_open'], // 20. Marché ouvert au personnel EDF
                isset($sheet['acheteur']) ? $sheet['acheteur'] : '', // 21. Acheteur
                $sheet['contract_manager'], // 22. Contract manager
                $sheet['mise_a_jour_donnee'], // 23. Mise à jour de la donnée
                $sheet['status'], // 24. Statut
                trim((isset($sheet['formated_updated_date']) ? $sheet['formated_updated_date'] : '') . ' par ' . (isset($sheet['updated_by_full_name']) ? $sheet['updated_by_full_name'] : ''), ' par '), // 25. Dernière modification
                isset($sheet['comments']) ? $sheet['comments'] : $sheet['description'] // 26. Commentaires
            ];
            
            unset($sheet['id']);
            unset($sheet['content']);
            
            if(isset($match[1])){
                foreach($match[1] as $m){
                    $contractCodes[] = $m;
                    $rowData[12] = $m; // Numéros de contrats (position 13, index 12)
                    $csv['data'][] = $rowData;
                }
            }
           
            foreach ($contracts as $key => $contract) {
                if(!in_array($contract['contract_code'], $contractCodes)){
                    $contractCodes[] = $contract['contract_code'];
                    $rowData[12] = $contract['contract_code']; // Numéros de contrats (position 13, index 12)
                    $csv['data'][] = $rowData;
                }
            }

            if(count($contracts) == 0 && count($contractCodes) == 0){
                $csv['data'][] = $rowData;
            }
            
            $compteur++; // Incrémenter le compteur pour la fiche suivante
        }

        return $csv;
    }


    public function exportAll($options, $userNni = null) {
        $options['userNni'] = $userNni;
        $options['words'] = Arrays::getFormattedWords($options['search']['searchText']);
        $status = [0 => 'En attente', 1 => 'Publiée', '-1' => 'Archivée'];
        $sheet = new Sheet();
        $answer = [];
        $sheetProvider = new SheetProvider();
        $sheetFiliale = new SheetBeneficiary();
        $sheetFiliale0 = new SheetStipulation();
        $sheetZone = new SheetArea();
        $sheetGroup = new SheetGroup();
        $committee = new Committee();
        $sheetContract = new SheetContract();
        $result = $sheet->searchWithoutPagination($options, $userNni);
        
        $header = [
            'Nom fiche',
            'Date de début part ferme',
            'Date de fin part ferme',
            'Options',
            'Commentaires',
            'Approvisionnement sous dauphin',
            'Marché ouvert au personnel EDF',
            'Stipulationautrui',
            'Partenariat de productivité',
            'Secteur protégé',
            'Fournisseurs',
            'Bénéficiaires',
            'Entités bénéficiaires d\'EDF SA / ENEDIS',
            'Stipulation pour autrui',
            'Zone géographique d’application des marchés',
            'Comité de Gestion de la Demande',
            'Statut',
            'Kit de portage',
            'Mots clés',
            'Contract manager / Acheteur',
            'Commentaires'
        ];
        $csv = [
            'header' => $header,
            'data' => []
        ];
        $sheetOption = new SheetOption();
        $sheetDomain = new SheetDomain();
        $sheetSubDomain = new SheetSubDomain();
        $array = ['contrat'=>[], 'provider'=>[], 'contact'=>[]];
        foreach ($result['sheets'] as $sheet) {
            if (isset($sheet['committee'])) {
                $committeeItem = $committee->getByCode($sheet['committee']);
                if (!is_null($committeeItem) && is_array($committeeItem) && isset($committeeItem['label'])) {
                    $sheet['committee'] = $committeeItem['label'];
                }
            }

            $providers = $sheetProvider->getBySheet($sheet['id']);
            $sheet['providers'] = '';
                foreach ($providers as $key => $provider) {
                    if ($key !== 0) {
                        $sheet['providers'] .= '|';
                    }

                    $sheet['providers'] .= $provider['name'];
                }

            $filiales = $sheetFiliale->getBySheet($sheet['id']);
            $sheet['filiales'] = '';
            foreach ($filiales as $key => $filiale) {
                if ($key !== 0) {
                    $sheet['filiales'] .= '|';
                }

                $sheet['filiales'] .= $filiale['name'];
            }

            $filiales0 = $sheetFiliale0->getBySheet($sheet['id']);
            $sheet['filiales0'] = '';
            foreach ($filiales0 as $key => $filiale0) {
                if ($key !== 0) {
                    $sheet['filiales0'] .= '|';
                }

                $sheet['filiales0'] .= $filiale0['name'];
            }

            $zones = $sheetZone->getBySheet($sheet['id']);
            $sheet['zones'] = '';
            foreach ($zones as $key => $zone) {
                if ($key !== 0) {
                    $sheet['zones'] .= '|';
                }
                $sheet['zones'] .= $zone['name'];
            }

            $groups = $sheetGroup->getBySheet($sheet['id']);
            $sheet['groups'] = '';
            foreach ($groups as $key => $group) {
                if ($key !== 0) {
                    $sheet['groups'] .= '|';
                }
                $sheet['groups'] .= $group['name'];
            }

           
            $options = $sheetOption->getBySheet($sheet['id']);
            $details['options'] = '';

            foreach ($options as $key => $option) {

                if ($key !== 0) {
                    $sheet['options'] .= '|';
                }

                $startDate = date_create($option['start_date']);
                $endDate = date_create($option['end_date']);
                $sheet['options'] .= date_format($startDate, "d/m/Y") . ' - '  . date_format($endDate, "d/m/Y");
            }

            $domains = $sheetDomain->getBySheet($sheet['id']);
            $details['domain'] = '';
            foreach ($domains as $key => $domain) {
                if ($key !== 0) {
                    $sheet['domains'] .= '|';
                }
                $sheet['domains'] .= $domain['name'];
            }

            $subdomains = $sheetSubDomain->getBySheet($sheet['id']);

            $details['subdomains'] = '';
            foreach ($subdomains as $key => $subdomain) {
                if ($key !== 0) {
                    $sheet['subdomains'] .= '|';
                }
                $sheet['subdomains'] .= $subdomain['name'];
            }


            $sheet['dauphin'] = ($sheet['dauphin'] == 1) ? 'Oui' : 'Non';
            $sheet['edf_open'] = ($sheet['edf_open'] == 1) ? 'Oui' : 'Non';
            $sheet['stipulation_for_others'] = ($sheet['stipulation_for_others'] == 1) ? 'Oui' : 'Non';
            $sheet['protected_sector'] = ($sheet['protected_sector'] == 1) ? 'Oui' : 'Non';
            $sheet['partnership'] = ($sheet['partnership'] == 1) ? 'Oui' : 'Non';
            $sheet['status'] = $status[$sheet['status']];
            $sheet['description'] = $this->toPlainText($sheet['description']);
            unset($sheet['contracts']);
            $sheet['contrat'] = '';
            $sheet['provider'] = '';
            $sheet['contact'] = '';
            $contracts = $sheetContract->getBySheet($sheet['id']);
            $contractCodes = [];
            $content=$sheet['content'];
            unset($sheet['id']);
            unset($sheet['content']);
            $netAchatContracts = false;
            foreach ($contracts as $key => $contract) {
                if (!in_array($contract['contract_code'], $contractCodes)) {
                    $contractCodes[] = $contract['contract_code'];
                    $sheet['contrat'] = $contract['contract_code'];
                    $sheet['provider'] = $contract['provider'];
                    $sheet['contact'] = $contract['contacts'];
                    $csv['data'][] = $sheet;
                }
            }
            
            if (!empty($content)) {

                libxml_use_internal_errors(true);
                $dom = new DOMDocument('1.0', 'UTF-8');
                @$dom->loadHTML(mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8'));
                $dom->encoding = 'utf-8';
                $table = $dom->getElementsByTagName('table');
                foreach ($table as $index => $tble) {
                    $tr = $tble->getElementsByTagName('tr');
                    foreach ($tr as $k => $h2) {
                        if ($k == 0) continue;
                        $td = $h2->getElementsByTagName('td');
                        if ($td->length != 3) {
                            continue;
                        }
                    $empty = true;
                    $sheet['contrat'] = '';
                    $sheet['provider'] = '';
                    $sheet['contact'] = '';

                        foreach ($td as $key => $te) {
                            if ($key == 0 && !empty($te->textContent)){
                                $empty = false;
                                $sheet['contrat'] = $te->textContent;
                            }
                               
                            if ($key == 1 && !empty($te->textContent)){
                                $empty = false;
                                $sheet['provider'] = $te->textContent;
                            }
                                
                            if ($key == 2 && !empty($te->textContent)){
                                 $empty = false;
                                 $sheet['contact'] = $te->textContent;
                            }
                        }

                        if(!$empty){
                            $csv['data'][] = $sheet;
                            $netAchatContracts = true;
                        }
                    }
                }
            }


            if (count($contracts) == 0 && !$netAchatContracts ) {
                $csv['data'][] = $sheet;
            }

        }
        return $csv;
    }

    /**
     * 
     * @param string $str
     * @return string
     */
    public function toPlainText(string $str): string {
        $str = str_replace('&nbsp;', ' ', $str);
        $str = html_entity_decode($str, ENT_QUOTES | ENT_COMPAT, 'UTF-8');
        $str = html_entity_decode($str, ENT_HTML5, 'UTF-8');
        $str = html_entity_decode($str);
        $str = htmlspecialchars_decode($str);
        $str = strip_tags($str);

        return $str;
    }

    public function getSheetByBeneficiary($beneficiaryId)
    {
        try {
           
            $sheet = new Sheet();
            return [
                'status' => true,
                'sheets' => $sheet->getSheetByBeneficiary($beneficiaryId)
            ];
        } catch (Exception $ex) {
            http_response_code(500);
            return [
                'error' => $ex->getCode(),
                'message' => $ex->getMessage(),
            ];
        }
    }

    public function getSheetByStipulation($stipulationId)
    {
        try {
           
            $sheet = new Sheet();
            return [
                'status' => true,
                'sheets' => $sheet->getSheetByStipulation($stipulationId)
            ];
        } catch (Exception $ex) {
            http_response_code(500);
            return [
                'error' => $ex->getCode(),
                'message' => $ex->getMessage(),
            ];
        }
    }

    public function getSheetByProvider($pId)
    {
        try {
           
            $sheet = new Sheet();
            return [
                'status' => true,
                'sheets' => $sheet->getSheetByProvider($pId)
            ];
        } catch (Exception $ex) {
            http_response_code(500);
            return [
                'error' => $ex->getCode(),
                'message' => $ex->getMessage(),
            ];
        }
    }


    public function optimiseSearch(mixed $option):mixed{
        $searchText = trim($option["search"]["searchText"]) ;
        if (!empty($searchText)):
            $arraySearch = explode(" ", $searchText);
            shuffle($arraySearch);
            $lengthArray = count($arraySearch);
            if ($lengthArray > 2) {
                if (fmod($lengthArray, 2) == 0) {
                    $nbrPercent = floor(Math::getPercentOfNumber($lengthArray));
                } else {
                    $nbrPercent = floor(Math::getPercentOfNumber($lengthArray)) + 1;
                }
                $option["search"]["searchText"] = Arrays::getRandomElements($arraySearch, $nbrPercent);
            }
        endif;
        return $option ;
    }

    public function getFormattedWords($text)
    {
        $wordList = Arrays::getWordsFromText($text);
        $wordListSynonyme = $this->getAllSynonymous($wordList);
        $wordListSource = $this->getAllSource($wordList);
        $list = array_merge($wordListSynonyme, $wordListSource, $wordList);
        $list = Arrays::getWithOrWithoutPlural($list) ;
        $list = $this->removeIgnored($list);
        return implode("|", $list);
    }

    public function removeIgnored($wordList)
    {
        $ignored = new SearchNoList();
        foreach ($wordList as $key => $word) {
            if ($ignored->checkExist($word)) {
                unset($wordList[$key]);
            }
        }
        
        return $wordList;
    }

    public function getAllSynonymous($wordList)
    {
        $associated = new SearchAssociated();
        $synonymous = new SearchSynonymous();
        $globalList = [];
        foreach ($wordList as $key => $word) {
            $item = $associated->getItem($word);
            if(is_array($item) && is_numeric($item['id'])){
                   $synonymousList = $synonymous->getBySource($item['id']);
                    foreach($synonymousList as $s){
                        $globalList[] = $s['synonymous'];
                    }
            }
        }

         return $globalList;
        
    }

    public function getAllSource($wordList)
    {
        $associated = new SearchAssociated();
        $synonymous = new SearchSynonymous();
        $globalList = [];
        foreach ($wordList as $key => $word) {
            $sources = $synonymous->getBySynonymous($word);
            foreach($sources as $source){
                $item = $associated->get($source['source']);
                $globalList[] = $item['word'];
            }
        }

        return $globalList;
       
    }


    public function getSheetByContract($contractCode){
        $sheet = new Sheet();
        return $sheet->getSheetByContract($contractCode);
    }

    public function updateSheetEndDate($sheetId){
        $contractRepository = new ContractRepository();
        $sheet = new Sheet();
        var_dump($sheetId);
        $sheetData =  $sheet->get($sheetId);
        $contracts = [];
        preg_match_all('#app/contrat/details/(.*?)\"#', $sheetData['content'], $match);
            if(isset($match[1])){
                foreach($match[1] as $m){
                    $contracts[] = $m;
                }
            }

            $dateMax = '';
        foreach ($contracts as $code) {
            $contract = null;
            $contract4 = $this->getContract(['word' => $code . "-PGI-4"]);
            if (!is_null($contract4)) {
                $contract = $contract4;
            } else {
                $contract2 = $this->getContract(['word' => $code . "-PGI-2"]);
                if (!is_null($contract2)) {
                    $contract = $contract2;
                } else {
                    $contract1 = $this->getContract(['word' => $code . "-PGI-1"]);
                    if (!is_null($contract1)) {
                        $contract = $contract1;
                    } else {
                        $contract = $this->getContract(['word' => $code]);
                    }
                }
              
              
            }   
          
            if($contract['infos']['contract_date_end']>$dateMax){
                    $dateMax = $contract['infos']['contract_date_end'];
                }
        }
        
        $sheet->updateEndDate($sheetId, $dateMax);

            //récupérer les contrats de la fiche
       //appeler HLK et récupérer les information de contrat
       //Mettre à jour la fiche

        return 12;

    }


    public function getContract($options) {

            $queryData = [
                'password' => getenv("HLK_API_PASS"),
                'action_code' => 'get_contract',
                'action_info' => $options['word'],
                'limit' => 20,
                'line' => ''
            ]; 
            return HttpClientService::post(getenv("HLK_API_URL"), $queryData);
        return [];
    }
    

}
