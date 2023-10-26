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

        $options['words'] = $this->getFormattedWords($options['search']['searchText']);
        $sheet = new Sheet();
        $options['userNni'] = $userNni;
        $sectors = $sheet->groupedBySector($options);
        $openEdfs = $sheet->groupedByOpenEdf($options);
        $dauphins = $sheet->groupedByDauphin($options);
        $partnerships = $sheet->groupedByPartnership($options);
        $status = $sheet->groupedByStatus($options);
        $directions = $sheet->groupedByDirections($options);
       
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
                        'areas' => $sheet->groupedByZone($options),
                        'providers' =>$sheet->groupedByProvider($options),
                        'directions' => self::addTooltip($directions),
                        'committees' => $sheet->groupedByCommittees($options),
                    ],
        ];
    }


    public static function addAttribute($items){
        return self::addElements($items, [true,false]);
    }

    public static function addStatus($items){
        return self::addElements($items, [-1,0,1]);
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
            var_dump($e->getMessage());exit;
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
        $sheetZone = new SheetArea();
        $sheetGroup = new SheetGroup();
        $committee = new Committee();
        $sheetContract = new SheetContract();
        $result = $sheet->searchWithoutPagination($options, $userNni);
        $header = [
            'Titre',
            'Date de début part ferme',
            'Date de fin part ferme',
            'Options',
            'Commentaires',
            'Approvisionnement sous dauphin',
            'Marché ouvert au personnel EDF',
            'Stipulation pour autrui',
            'Partenariat de productivité',
            'Secteur protégé',
            'Fournisseurs',
            'Bénéficiaires',
            'Zone géographique d’application des marchés',
            'Directions opérationnelles (DOA)',
            'Comité de Gestion de la Demande',
            'Segments',
            'Sous-ensemble',
            'Statut',
            'Kit de déploiement',
            'Numéro de contrat',
            'Mots clés'
        ];
        $csv = [
            'header' => $header,
            'data' => []
        ];
        $sheetOption = new SheetOption();
        $sheetDomain = new SheetDomain();
        $sheetSubDomain = new SheetSubDomain();
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
            $sheet['contracts'] = '';
            $contracts = $sheetContract->getBySheet($sheet['id']);
            preg_match_all('#app/contrat/details/(.*?)\"#', $sheet['content'], $match);
            $contractCodes = [];
            unset($sheet['id']);
            unset($sheet['content']);
            if(isset($match[1])){
                foreach($match[1] as $m){
                    $contractCodes[] = $m;
                    $sheet['contracts'] = $m;
                    $csv['data'][] = $sheet;
                }
            }
           
            foreach ($contracts as $key => $contract) {
                if(!in_array($contract['contract_code'], $contractCodes)){
                    $contractCodes[] = $contract['contract_code'];
                    $sheet['contracts'] = $contract['contract_code'];
                    $csv['data'][] = $sheet;
                }
            }

            if(count($contracts) == 0 && count($contractCodes) == 0){
                $csv['data'][] = $sheet;
            }
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
        $sheetZone = new SheetArea();
        $sheetGroup = new SheetGroup();
        $committee = new Committee();
        $sheetContract = new SheetContract();
        $result = $sheet->searchWithoutPagination($options, $userNni);
        
        $header = [
            'Titre',
            'Date de début part ferme',
            'Date de fin part ferme',
            'Options',
            'Commentaires',
            'Approvisionnement sous dauphin',
            'Marché ouvert au personnel EDF',
            'Stipulation pour autrui',
            'Partenariat de productivité',
            'Secteur protégé',
            'Fournisseurs',
            'Bénéficiaires',
            'Zone géographique d’application des marchés',
            'Directions opérationnelles (DOA)',
            'Comité de Gestion de la Demande',
            'Segments',
            'Sous-ensemble',
            'Statut',
            'Kit de déploiement',
            'Mots clés',
            'contrat',
            'fournisseur',
            'contact'
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
