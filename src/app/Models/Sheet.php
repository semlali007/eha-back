<?php

namespace App\Models;

use App\Exceptions\InternalServerException;
use App\Exceptions\SheetException;
use App\Models\Beneficiary;
use App\Models\Stipulation;
use App\Models\Area;
use App\Models\Group;
use App\Models\SheetArea;
use App\Models\SheetProvider;
use App\Models\SheetGroup;
use App\Models\SheetBeneficiary;
use App\Models\SheetStipulation;
use App\Models\Committee;
use App\Models\Subscription;
use App\Services\SheetService ;
use Core\Util\{Date,Math, Arrays } ;

use Exception;
use PDO;

class Sheet extends Model
{
    protected $tableName = 'sheet_headers';
    protected $beneficiaryTable;
    protected $stipulationTable;
    protected $areaTable;
    protected $groupTable;
    protected $providerTable;
    protected $sheetBeneficiaryTable;
    protected $sheetStipulationTable;
    protected $sheetAreaTable;
    protected $sheetProviderTable;
    protected $sheetGroupTable;
    protected $searchCondition;
    protected $committeeTable;
    protected $categoryTable;
    protected $sheetContract ;
    protected $subscriptionTable ;

    public function __construct()
    {
        parent::__construct();
        $this->beneficiaryTable = (new Beneficiary())->table;
        $this->stipulationTable = (new Stipulation())->table;
        $this->areaTable = (new Area())->table;
        $this->groupTable = (new Group())->table;
        $this->providerTable = (new Provider())->table;
        $this->sheetBeneficiaryTable = (new SheetBeneficiary())->table;
        $this->sheetStipulationTable = (new SheetStipulation())->table;
        $this->sheetAreaTable = (new SheetArea())->table;
        $this->sheetProviderTable = (new SheetProvider())->table;
        $this->sheetGroupTable = (new SheetGroup())->table;
        $this->committeeTable = (new Committee())->table;
        $this->categoryTable = (new Category())->table;
        $this->subscriptionTable = (new Subscription())->table;
        $this->searchCondition = null;
        $this->sheetContract = (new SheetContract())->table ;
    }

    
 function searchWithoutPagination($options)
    {
        try {
            $words = $options['words'];
            $orderBy = $options['order_by'] ?? 'rank';
            $direction = $options['direction'] ?? 'desc';

            $data = [];
            $query = "select distinct  sss.label, to_char(sss.start_date, 'DD/MM/YYYY') as start_date, to_char(sss.end_date, 'DD/MM/YYYY') as end_date,  '' as options, sss.description, sss.dauphin,sss.edf_open,sss.stipulation_for_others, sss.partnership, sss.protected_sector, '' providers,'' as filiales, '' as filiales0, '' as zones, '' as groups,sss.committee, '' as domains, '' as subdomains, sss.status, '' as kits, '' as contracts, sss.id, sss.content, sss.search_words, sss.contrat_groupe, sss.mise_a_jour_donnee, sss.id_supplier_size, sss.supplier_vat_number, sss.entites_beneficiaires_edf_enedis, sss.contract_manager, sss.pcm_link, sss.geographic_detail, sss.acheteur 
            from ( SELECT distinct s.id,TS_RANK_CD(TO_TSVECTOR(unaccent(lower(S.label))),

            QUERY) AS RANK, TS_RANK_CD(TO_TSVECTOR(unaccent(lower(S.description)) || ' ' || unaccent(lower(S.search_words))),

            QUERY) AS RANK1,  s.description, s.start_date, s.end_date,s.dauphin,s.edf_open,s.stipulation_for_others, s.partnership, s.protected_sector,s.committee, s.label, s.status,s.updated_at,s.created_at, to_char(s.updated_at, 'DD/MM/YYYY à HH24:MI:SS') as formated_updated_date , s.content, s.search_words, s.contrat_groupe, s.mise_a_jour_donnee, s.id_supplier_size, s.supplier_vat_number, s.entites_beneficiaires_edf_enedis, s.contract_manager, s.pcm_link, s.geographic_detail, s.acheteur
                      FROM  TO_TSQUERY(unaccent(lower('{$words}'))) QUERY, {$this->table}  as s 
                      {$this->getSearchCondition($options)}  ORDER BY {$orderBy} {$direction} , RANK desc, RANK1 desc
                      )
                      as sss LEFT JOIN {$this->subscriptionTable} sub ON  sub.sheet_id = sss.ID and user_nni=:userNni
                      ";
            $sth = $this->db->getPDO()->prepare($query);
            $sth->bindParam(':userNni', $userNni, PDO::PARAM_STR);
            $sth->execute();
            $data['sheets'] = $sth->fetchall(PDO::FETCH_ASSOC);
            return $data;
        } catch (\Exception $e) {
            throw new InternalServerException();
        }
    }
    public function search($options, $userNni = null)
    {
        try {
            $words = $options['words'];
            $orderBy = $options['order_by'] ?? 'rank';
            $direction = $options['direction'] ?? 'desc';
            $perpage = $options['perpage'] ?? 10;
            $page =  $options['page'] ?? 1;
            $page = ($page - 1) * $perpage;
            $query = "select distinct *,
            CASE 
            WHEN sub.sheet_id is not NULL THEN true
            ELSE false
            END subscribed
            from ( SELECT distinct s.id,TS_RANK_CD(TO_TSVECTOR(unaccent(lower(S.label))),

            QUERY) AS RANK, TS_RANK_CD(TO_TSVECTOR(unaccent(lower(S.description)) || ' ' || unaccent(lower(S.search_words))),

            QUERY) AS RANK1, s.label, s.status,s.updated_at,s.created_at, to_char(s.updated_at, 'DD/MM/YYYY à HH24:MI:SS') as formated_updated_date, s.contrat_groupe, s.mise_a_jour_donnee, s.id_supplier_size, s.supplier_vat_number, s.entites_beneficiaires_edf_enedis, s.contract_manager, s.pcm_link, s.geographic_detail, s.acheteur 
                     
                      FROM  TO_TSQUERY(unaccent(lower('{$words}'))) QUERY, {$this->table}  as s 
                       
                      
                      {$this->getSearchCondition($options)}  ORDER BY {$orderBy} {$direction}, RANK desc, RANK1  desc  LIMIT {$perpage} offset {$page} 
                      )
                      
                      as sss LEFT JOIN {$this->subscriptionTable} sub ON  sub.sheet_id = sss.ID and user_nni=:userNni
                      
                      ORDER BY  $orderBy $direction
                      ";
                      
                      $sth = $this->db->getPDO()->prepare($query);
            $sth->bindParam(':userNni', $userNni, PDO::PARAM_STR);
            $sth->execute();
            return $sth->fetchall(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
           
            throw new InternalServerException($e->getMessage());
        }
    }


    public function getCount($options)
    {
        try {
            $query = "SELECT COUNT(distinct s.*) FROM " . $this->table . " s  {$this->getSearchCondition($options, [])}";
            $sth = $this->db->getPDO()->prepare($query);
            $sth->execute();
            
            return  $sth->fetch(PDO::FETCH_ASSOC)['count'];
        } catch (\Exception $e) {
            throw new InternalServerException();
        }
    }
    
    public function getSearchCondition($params, $forFilter=null)
    
    {
        $search = '';
        //$schema = pg_escape_string(getenv("DB_SCHEMA"));
        $options = $params['search'] ?? [];
        if (!is_array($forFilter) || !array_key_exists('beneficiary', $forFilter)) {
            $beneficiariesList = $options['beneficiaries'] ?? [];
            $beneficiaries = [];
            foreach ($beneficiariesList as $beneficiary) {
                $beneficiaries[] = $beneficiary['code'];
            }
            if (count($beneficiaries) > 0) {
                $beneficiariesStr = '(' . implode(', ', $beneficiaries) . ')';
                $search .= " INNER JOIN {$this->sheetBeneficiaryTable} sf on sf.sheet_id = s.id and sf.filiale_id in $beneficiariesStr  ";
            }
        }
        if (!is_array($forFilter) || !array_key_exists('stipulation', $forFilter)) {
            $stipulationAutruiList = $options['stipulationAutrui'] ?? [];
            $stipulationAutrui = [];
            foreach ($stipulationAutruiList as $stipulation) {
                $stipulationAutrui[] = $stipulation['code'];
            }
            if (count($stipulationAutrui) > 0) {
                $stipulationAutruiStr = '(' . implode(', ', $stipulationAutrui) . ')';
                $search .= " INNER JOIN {$this->sheetStipulationTable} sf on sf.sheet_id = s.id and sf.filiale_id in $stipulationAutruiStr  ";
            }
        }

        if (!is_array($forFilter) || !array_key_exists('direction', $forFilter)) {
            $directions = $options['directions'] ?? [];
            if (count($directions) > 0) {
                $directionsStr = '(' . implode(', ', $directions) . ')';
                $search .= " INNER JOIN {$this->sheetGroupTable} sg on sg.sheet_id = s.id and sg.group_id in $directionsStr";
            }
        }

        if (!is_array($forFilter) || !array_key_exists('area', $forFilter)) {
            $areas = $options['areas'] ??  [];
            if (count($areas) > 0) {
                $areasStr = '(' . implode(', ', $areas) . ')';
                $search .= " INNER JOIN {$this->sheetAreaTable} sz on sz.sheet_id = s.id  and sz.zone_id in $areasStr";
            }
        }
        

        if (!is_array($forFilter) || !array_key_exists('provider', $forFilter)) {
            $providersList = $options['providers'] ?? [];
            $providers = [];
            foreach ($providersList as $provider) {
                $providers[] = $provider['code'];
            }
            if (count($providers) > 0) {
                $providersStr = '(\'' . implode('\',\'', $providers) . '\')';
                $search .= " INNER JOIN {$this->sheetProviderTable} sp on sp.sheet_id = s.id and sp.provider_code in $providersStr ";
            }
        }

        if (isset($options['subscribe']) && $options['subscribe'] == true) {
                $userNni = $params['userNni'];
                $search .= " INNER JOIN {$this->subscriptionTable} ss on ss.sheet_id = s.id and ss.user_nni = '$userNni' ";
        }

        $searchProvider = (strtolower($options['searchProvider'] ?? ""));
        if (($searchProvider != '')) {
            $searchProvider = Arrays::getFormattedWords(strtolower($searchProvider));
            $queryListId = " (SELECT DISTINCT provider_code FROM {$this->providerTable} WHERE  TO_TSVECTOR(unaccent(lower(provider_label))) @@ TO_TSQUERY(unaccent(lower('{$searchProvider}'))) ) ";
            $condition = "  provider_code in $queryListId";
            $search .= " INNER JOIN 
            (
                select sheet_id 
                from {$this->sheetProviderTable}  
                where $condition
            ) spa 
            on spa.sheet_id = s.id  ";
        }
        $search_input = (strtolower($options['searchText'] ?? ""));
        $isCode = SheetService::isCodeContract($search_input);
        if ($isCode) {
            $search .= " LEFT JOIN {$this->sheetContract} sc ON  sc.sheet_id = s.ID ";
        }
        if ($search_input !== '') {
            if ($isCode) {
		$search .= " WHERE   ((TO_TSVECTOR(unaccent(lower(S.content))) @@ TO_TSQUERY(unaccent(lower('{$search_input}')))) OR (TO_TSVECTOR(unaccent(lower(sc.contract_code))) @@ TO_TSQUERY(unaccent(lower('{$search_input}'))))) ";
               // $search .= " WHERE   TO_TSVECTOR(unaccent(lower(S.content))) @@ TO_TSQUERY(unaccent(lower('{$search_input}'))) ";
            } else {
                $search .= " WHERE   TO_TSVECTOR(unaccent(lower(S.label)) || ' ' || unaccent(lower(S.description)) || ' ' || unaccent(lower(S.search_words))) @@ TO_TSQUERY(unaccent(lower('{$params['words']}'))) ";
            }
        } else {
            $search .= " WHERE   1=1 ";
        }


        if (!is_array($forFilter) || !array_key_exists('status', $forFilter)) {
            $status = $options['status'] ?? [];
            if (!empty($status)) {
                $this->addCondition($search, 's.status', $status);
            }
        }

        if (!is_array($forFilter) || !array_key_exists('edf_open', $forFilter)) {
            $edfOpen = $options['edf_open'] ?? '';
            if ($edfOpen !== '') {
                $this->addCondition($search, 's.edf_open', [($edfOpen == true) ? 'true' : 'false']);
            }
        }

        if (!is_array($forFilter) || !array_key_exists('protected_sector', $forFilter)) {
            $protectedSector = $options['protected_sector'] ?? '';
            if ($protectedSector  !== '') {
                $this->addCondition($search, 's.protected_sector', [($protectedSector  == true) ? 'true' : 'false']);
            }
        }

        if (!is_array($forFilter) || !array_key_exists('dauphin', $forFilter)) {
            $dauphin = $options['dauphin'] ?? '';
            if ($dauphin !== '') {
                $this->addCondition($search, 's.dauphin', [($dauphin == true) ? 'true' : 'false']);
            }
        }

        if (!is_array($forFilter) || !array_key_exists('partnership', $forFilter)) {
            $partnership = $options['partnership'] ?? '';
            if ($partnership !== '') {
                $this->addCondition($search, 's.partnership', [($partnership == true) ? 'true' : 'false']);
            }
        }
        if (!is_array($forFilter) || !array_key_exists('showcase', $forFilter)) {
        $showcase = $options['showcase'] ?? '';
        if ($showcase !== '') {
            $this->addCondition($search, 's.showcase', [($showcase == true) ? 'true' : 'false']);
        }
    }
    if (!is_array($forFilter) || !array_key_exists('categories', $forFilter)) {
        $categories = $options['categories'] ?? [];
        if (!empty($categories)) {
            $this->addCondition($search, 's.category', $categories);
        }
    }
    if (!is_array($forFilter) || !array_key_exists('committees', $forFilter)) {
        $committees = $options['committees'] ?? [];
        if (!empty($committees)) {
            $this->addCondition($search, 's.committee', $committees);
        }
    }

    if (!is_array($forFilter) || !array_key_exists('contrat_groupe', $forFilter)) {
        $contratGroupe = $options['contratGroupe'] ?? '';
        if ($contratGroupe !== '') {
            $this->addCondition($search, 's.contrat_groupe', [$contratGroupe]);
        }
    }

    if (!is_array($forFilter) || !array_key_exists('mise_a_jour_donnee', $forFilter)) {
        $miseAJourDonnee = $options['miseAJourDonnee'] ?? '';
        if ($miseAJourDonnee !== '') {
            $this->addCondition($search, 's.mise_a_jour_donnee', [$miseAJourDonnee]);
        }
    }

    if (!is_array($forFilter) || !array_key_exists('supplier_size', $forFilter)) {
        $supplierSize = $options['supplierSize'] ?? [];
        if (!empty($supplierSize)) {
            $this->addCondition($search, 's.id_supplier_size', $supplierSize);
        }
    }

    // Date range filtering
    if (!is_array($forFilter) || !array_key_exists('date_range', $forFilter)) {
        $dateStart = $options['dateStart'] ?? '';
        $dateEnd = $options['dateEnd'] ?? '';
        
        if ($dateStart !== '' || $dateEnd !== '') {
            if ($dateStart !== '' && $dateEnd !== '') {
                // Both dates provided - filter sheets with start_date >= dateStart AND end_date <= dateEnd
                $search .= " AND (s.start_date >= '" . addslashes($dateStart) . "' AND s.end_date <= '" . addslashes($dateEnd) . "')";
            } elseif ($dateStart !== '') {
                // Only start date - filter sheets with start_date >= dateStart
                $search .= " AND s.start_date >= '" . addslashes($dateStart) . "'";
            } elseif ($dateEnd !== '') {
                // Only end date - filter sheets with end_date <= dateEnd
                $search .= " AND s.end_date <= '" . addslashes($dateEnd) . "'";
            }
        }
    }

        return $search;
    }

    public function groupedByOpenEdf($options)
    {
        try {
            $sql = "SELECT COUNT(s.*) as count, s.edf_open as code FROM  " . $this->table . " s  {$this->getSearchCondition($options,['edf_open'=>true])}" . " GROUP BY edf_open";
            $sth = $this->db->getPDO()->prepare($sql);
            $sth->execute();
            return  $sth->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            throw new InternalServerException();
        }
    }

    public function groupedByShowcase($options)
    {
        try {
            $sql = "SELECT COUNT(s.*) as count, s.showcase as code FROM  " . $this->table . " s  {$this->getSearchCondition($options,['showcase'=>true])}" . " GROUP BY showcase";
            $sth = $this->db->getPDO()->prepare($sql);
            $sth->execute();
            return  $sth->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            throw new InternalServerException();
        }
    }

    public function groupedByPartnership($options)
    {
        try {
            $sql = "SELECT COUNT(s.*) as count, s.partnership as code FROM  " . $this->table . " s  {$this->getSearchCondition($options,['partnership'=>true])}" . " GROUP BY partnership";
            $sth = $this->db->getPDO()->prepare($sql);
            $sth->execute();
            return  $sth->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            throw new InternalServerException();
        }
    }

    public function groupedBySector($options)
    {
        try {
           $sql = "SELECT COUNT(s.*) as count, s.protected_sector as code FROM  " . $this->table . " s {$this->getSearchCondition($options,['protected_sector'=>true])}" . " GROUP BY protected_sector";
          
           $sth = $this->db->getPDO()->prepare($sql);
            $sth->execute();
            return  $sth->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            var_dump($e->getMessage());exit;
            throw new InternalServerException();
        }
    }

    public function groupedByDauphin($options)
    {
        try {
            $sql = "SELECT COUNT(s.*) as count, s.dauphin as code FROM " . $this->table . "  s  {$this->getSearchCondition($options,['dauphin'=>true])}" . " GROUP BY dauphin";
            $sth = $this->db->getPDO()->prepare($sql);
            $sth->execute();
            return  $sth->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            throw new InternalServerException();
        }
    }

    public function groupedByStatus($options)
    {
        try {
            $sql = "SELECT COUNT(s.*) as count, s.status as code FROM  " . $this->table . " s   {$this->getSearchCondition($options,['status'=>true])}" . " GROUP BY status";
            $sth = $this->db->getPDO()->prepare($sql);
            $sth->execute();
            return  $sth->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            throw new InternalServerException();
        }
    }

    public function groupedBySubscribed($options)
    {
        try {
            $sql = "SELECT COUNT(s.*) as count FROM " . $this->table . "  s INNER JOIN {$this->subscriptionTable} sub ON  sub.sheet_id = s.id and user_nni=:userNniCount {$this->getSearchCondition($options,['subscribe'=>true])}";
            $sth = $this->db->getPDO()->prepare($sql);
            $sth->bindParam(':userNniCount', $options['userNni'], PDO::PARAM_STR);
            $sth->execute();
            return  $sth->fetch(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            throw new InternalServerException();
        }
    }

    public function groupedByFiliale($options)
    {
        try {
            $sql = "
       SELECT f.name , f.id as code , CASE WHEN BENIFICIARIES.COUNT IS NULL 
       THEN 0 
       ELSE BENIFICIARIES.COUNT 
       END AS COUNT, f.id  from {$this->beneficiaryTable} f  LEFT join  
        (SELECT  count(sb.*) as count, sb.filiale_id as code FROM   {$this->sheetBeneficiaryTable} sb WHERE sb.sheet_id  in
         ( SELECT s.id  FROM  " . $this->table . "  s   {$this->getSearchCondition($options, ['beneficiary'=>true])} ) GROUP BY sb.filiale_id)  benificiaries on f.id =  benificiaries.code
         
         order by COUNT desc, f.id 
         ";

            $sth = $this->db->getPDO()->prepare($sql);
            $sth->execute();
            return  $sth->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            throw new InternalServerException();
        }
    }
    public function groupedByFiliale0($options)
    {
        try {
            $sql = "SELECT f.name, f.id as code, 
                CASE WHEN stipulationAutrui.COUNT IS NULL THEN 0 ELSE stipulationAutrui.COUNT END AS COUNT
            FROM {$this->stipulationTable} f
            LEFT JOIN (
                SELECT COUNT(sb.*) as COUNT, sb.filiale_id as code 
                FROM {$this->sheetStipulationTable} sb 
                WHERE sb.sheet_id IN (
                    SELECT s.id 
                    FROM " . $this->table . " s
                ) 
                GROUP BY sb.filiale_id
            ) stipulationAutrui ON f.id = stipulationAutrui.code
            ORDER BY COUNT DESC, f.id";
    //         $sql = "
    //    SELECT f.name , f.id as code , CASE WHEN STIPULATIONAUTRUI.COUNT IS NULL 
    //    THEN 0 
    //    ELSE STIPULATIONAUTRUI.COUNT 
    //    END AS COUNT, f.id  from {$this->stipulationTable} f  LEFT join  
    //     (SELECT  count(sb.*) as count, sb.filiale_id as code FROM   {$this->sheetStipulationTable} sb WHERE sb.sheet_id  in
    //      ( SELECT s.id  FROM  " . $this->table . "  s   {$this->getSearchCondition($options, ['stipulation'=>true])} ) GROUP BY sb.filiale_id)  stipulationAutrui on f.id =  stipulationAutrui.code
         
    //      order by COUNT desc, f.id 
    //      ";

            $sth = $this->db->getPDO()->prepare($sql);
            $sth->execute();
            return  $sth->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            throw new InternalServerException();
        }
    }

    public function groupedByProvider($options)
    {
        try {
            $sql = "
       SELECT p.provider_label as name , p.provider_code as code , CASE WHEN PROVIDERS.COUNT IS NULL 
       THEN 0 
       ELSE PROVIDERS.COUNT 
       END AS COUNT  from {$this->providerTable} p LEFT join 
         
        (SELECT  count(sp.*) as count, sp.provider_code as code FROM   {$this->sheetProviderTable} sp WHERE sp.sheet_id  in
         ( SELECT s.id  FROM  " . $this->table . "  s  {$this->getSearchCondition($options, ['provider'=>true])} ) GROUP BY sp.provider_code)  providers on p.provider_code =  providers.code
         
         order by COUNT desc, p.provider_label 
         ";
            $sth = $this->db->getPDO()->prepare($sql);
            $sth->execute();
            return  $sth->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            throw new InternalServerException();
        }
    }

    public function groupedByZone($options)
    {
        try {
            $sql = "
       SELECT z.name , z.id as code , CASE WHEN AREAS.COUNT IS NULL 
        THEN 0 
        ELSE AREAS.COUNT 
        END AS COUNT
            from {$this->areaTable} z LEFT join 
         
        (SELECT  count(sz.*) as count, sz.zone_id as code FROM   {$this->sheetAreaTable} sz WHERE sz.sheet_id  in
         ( SELECT s.id  FROM  " . $this->table . "  s  {$this->getSearchCondition($options, ['area'=>true])} ) GROUP BY sz.zone_id)  areas on z.id =  areas.code
         
         order by COUNT desc,z.name 
         ";
            $sth = $this->db->getPDO()->prepare($sql);
            $sth->execute();
            return  $sth->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            throw new InternalServerException();
        }
    }

    public function groupedByDirections($options)
    {
        try {
            $sql = "
       SELECT d.name , d.id as code , CASE WHEN DIRECTIONS.COUNT IS NULL 
       THEN 0 
       ELSE DIRECTIONS.COUNT 
       END AS COUNT  from {$this->groupTable} d LEFT join 
         
        (SELECT  count(sd.*) as count, sd.group_id as code FROM   {$this->sheetGroupTable} sd WHERE sd.sheet_id  in
         ( SELECT s.id  FROM  " . $this->table . "  s   {$this->getSearchCondition($options, ['direction'=>true])} ) GROUP BY sd.group_id)  directions on d.id =  directions.code
         
         order by COUNT desc,d.name limit 15
         ";
            $sth = $this->db->getPDO()->prepare($sql);
            $sth->execute();
            return  $sth->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            throw new InternalServerException();
        }
    }

    public function groupedByCategories($options)
    {
        try {
            $sql = "SELECT COUNT(s.*) as count, c.code as code, c.label as name FROM   " . $this->categoryTable . "  c " . " LEFT join {$this->table} s
             ON c.code = s.category {$this->getSearchCondition($options,['categories'=>true])} GROUP BY c.code, c.label order by COUNT desc, c.label";
            $sth = $this->db->getPDO()->prepare($sql);
            $sth->execute();
            return $sth->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            throw new \Exception("Error in groupedByCategories: " . $e->getMessage());
        }
    }

    public function groupedByCommittees($options)
    {
        try {
            $sql = "SELECT COUNT(s.*) as count, c.code as code, c.label as name FROM   " . $this->committeeTable . "  c " . " LEFT join {$this->table} s
             ON c.code = s.committee {$this->getSearchCondition($options,['committees'=>true])} GROUP BY c.code, c.label order by COUNT desc, c.label";
            $sth = $this->db->getPDO()->prepare($sql);
            $sth->execute();
            return  $sth->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            throw new InternalServerException();
        }
    }

    public function groupedByContratGroupe($options)
    {
        try {
            $sql = "SELECT COUNT(s.*) as count, s.contrat_groupe as code FROM  " . $this->table . " s  {$this->getSearchCondition($options,['contrat_groupe'=>true])}" . " GROUP BY contrat_groupe";
            $sth = $this->db->getPDO()->prepare($sql);
            $sth->execute();
            return  $sth->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            throw new InternalServerException();
        }
    }

    public function groupedByMiseAJourDonnee($options)
    {
        try {
            $sql = "SELECT COUNT(s.*) as count, s.mise_a_jour_donnee as code FROM  " . $this->table . " s  {$this->getSearchCondition($options,['mise_a_jour_donnee'=>true])}" . " GROUP BY mise_a_jour_donnee";
            $sth = $this->db->getPDO()->prepare($sql);
            $sth->execute();
            return  $sth->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            // Si la colonne n'existe pas encore, on retourne un tableau vide
            // Cela permet à l'application de fonctionner avant la migration
            return [];
        }
    }

    public function groupedBySupplierSize($options)
    {
        try {
            $sql = "SELECT COUNT(s.*) as count, s.id_supplier_size as code FROM  " . $this->table . " s  {$this->getSearchCondition($options,['supplier_size'=>true])}" . " AND s.id_supplier_size IS NOT NULL GROUP BY id_supplier_size";
            $sth = $this->db->getPDO()->prepare($sql);
            $sth->execute();
            return  $sth->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            // Si la colonne n'existe pas encore, on retourne un tableau vide
            // Cela permet à l'application de fonctionner avant la migration
            return [];
        }
    }
    
    function addCondition(&$sql, $key, $value)
    {
        if (is_null($sql) || is_null($key) || is_null($value)) {
            return $this;
        }

        // Ensure $value is always an array
        if (!is_array($value)) {
            $value = [$value];
        }

        // Quote string values properly for SQL
        $quotedValues = array_map(function($val) {
            return "'" . addslashes($val) . "'";
        }, $value);
        
        $valueStr = '(' . implode(', ', $quotedValues) . ')';
        $sql .= " AND ($key IN $valueStr) ";

        return $this;
    }

    /**
     * 
     * @param int $id
     * @return type
     * @throws InternalServerException
     */
    public function get(int $id) {
        try {
            $query = 'SELECT * FROM ' . $this->table . ' WHERE id=:id';
            $sth = $this->db->getPDO()->prepare($query);
            $sth->bindParam(':id', $id, PDO::PARAM_INT);
            $sth->execute();
            return $sth->fetch(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            throw new InternalServerException($e->getMessage());
        }
    }

    /**
     * Archiver les fiches marchés
     * 
     * @param string $date format (dd-mm-yyyy) exemple: 10/02/2022
     * @return int|null
     */
    public function archiverFichesByDate(string $date):?int {
        
        $sth = $this->db->getPDO() // Execution de la requete
                ->prepare('UPDATE ' . $this->table . ' SET status=\'-1\' WHERE updated_at <=:endDate and (status=1 or status=0)');
         // Bind params
        $sth->bindParam(':endDate', $date, PDO::PARAM_INT);
        $sth->execute(); // Lancer la requete
        // si plusieurs row sont affectés
        return $sth->rowCount();
    }
    
    /**
     * Archiver les fiches marchés
     * 
     * @param string $date format (dd-mm-yyyy) exemple: 10/02/2022
     * @return int|null
     */
    public function desarchiverFichesByDate(string $date):?int {
        
        $sth = $this->db->getPDO() // Execution de la requete
                ->prepare('UPDATE ' . $this->table . ' SET status=\'1\' WHERE end_date>=:endDate');
         // Bind params
        $sth->bindParam(':endDate', $date, PDO::PARAM_INT);
        $sth->execute(); // Lancer la requete
        
        // si plusieurs row sont affectés
        return $sth->rowCount();
    }
    
    /**
     * 
     * @param string $endDate
     * @return mixed
     */
    public function findByEndDateWithIn(string $endDate): mixed 
    { 
        $sth = $this->db->getPDO() // Execution de la requete
                ->prepare('SELECT id, label, start_date, end_date FROM ' . $this->table . ' WHERE end_date<=:endDate and status != -1'); 
        $sth->bindParam(':endDate', $endDate, PDO::PARAM_STR); // Bind params
        $sth->execute(); // Lancer la requete
        
        return $sth->fetchAll(\PDO::FETCH_ASSOC); // Recupérer les infos sous forme une table
    }

    /**
     * 
     * @param string $date
     * @return mixed
     */
    public function findByEndDate(string $date): mixed 
    {
        $sth = $this->db->getPDO() // Execution de la requete
                ->prepare('SELECT id,end_date FROM ' . $this->table . ' WHERE end_date<=:endDate and status!=-1'); 
        $sth->bindParam(':endDate', $date, PDO::PARAM_STR); // Bind params
        $sth->execute(); // Lancer la requete
        
        return $sth->fetchAll(\PDO::FETCH_ASSOC); // Recupérer les infos sous forme une table
    }

    public function update($data)
    {
        $id = $data['id'];
        try {
            // Assurer des valeurs par défaut pour éviter les NULL
            $data['mise_a_jour_donnee'] = $data['mise_a_jour_donnee'] ?? 'non';
            $data['contrat_groupe'] = $data['contrat_groupe'] ?? 'false';
            $data['id_supplier_size'] = $data['id_supplier_size'] ?? null;
            $data['supplier_vat_number'] = $data['supplier_vat_number'] ?? null;
            $data['contract_manager'] = $data['contract_manager'] ?? null;
            $data['pcm_link'] = $data['pcm_link'] ?? null;
            $data['entites_beneficiaires_edf_enedis'] = $data['entites_beneficiaires_edf_enedis'] ?? null;
            $data['geographic_detail'] = $data['geographic_detail'] ?? null;
            $data['acheteur'] = $data['acheteur'] ?? null;
            
            $query = '
                UPDATE ' . $this->table . ' set label=:label, description=:description, updated_at=:updated_at, updated_by=:updated_by, status=:status, updated_by_full_name =:updated_by_full_name, start_date=:start_date, end_date=:end_date, dauphin=:dauphin, edf_open=:edf_open, stipulation_for_others=:stipulation_for_others, partnership=:partnership, protected_sector=:protected_sector, committee=:committee, category=:category, showcase=:showcase, content=:content, search_words=:search_words, contrat_groupe=:contrat_groupe, mise_a_jour_donnee=:mise_a_jour_donnee, id_supplier_size=:id_supplier_size, supplier_vat_number=:supplier_vat_number, contract_manager=:contract_manager, pcm_link=:pcm_link, entites_beneficiaires_edf_enedis=:entites_beneficiaires_edf_enedis, geographic_detail=:geographic_detail, acheteur=:acheteur WHERE id =:id;
        ';
            $sth = $this->db->getPDO()->prepare($query);
            $sth->bindParam(':label', $data['label'], PDO::PARAM_STR);
            $sth->bindParam(':description', $data['description'], PDO::PARAM_STR);
            $sth->bindParam(':updated_at', $data['updated_at'], PDO::PARAM_STR);
            $sth->bindParam(':updated_by', $data['updated_by'], PDO::PARAM_STR);
            $sth->bindParam(':status', $data['status'], PDO::PARAM_INT);
            $sth->bindParam(':updated_by_full_name', $data['updated_by_full_name'], PDO::PARAM_STR);
            $sth->bindParam(':start_date', $data['start_date'], PDO::PARAM_STR);
            $sth->bindParam(':end_date', $data['end_date'], PDO::PARAM_STR);
            $sth->bindParam(':dauphin', $data['dauphin'], PDO::PARAM_BOOL);
            $sth->bindParam(':edf_open', $data['edf_open'], PDO::PARAM_BOOL);
            $sth->bindParam(':stipulation_for_others', $data['stipulation_for_others'], PDO::PARAM_BOOL);
            $sth->bindParam(':partnership', $data['partnership'], PDO::PARAM_BOOL);
            $sth->bindParam(':protected_sector', $data['protected_sector'], PDO::PARAM_BOOL);
            $sth->bindParam(':committee', $data['committee'], PDO::PARAM_INT);
            $sth->bindParam(':category', $data['category'], PDO::PARAM_INT);
            $sth->bindParam(':showcase', $data['showcase'], PDO::PARAM_BOOL);
            $sth->bindParam(':content', $data['content'], PDO::PARAM_STR);
            $sth->bindParam(':search_words', $data['words'], PDO::PARAM_STR);
            $sth->bindParam(':contrat_groupe', $data['contrat_groupe'], PDO::PARAM_STR);
            $sth->bindParam(':mise_a_jour_donnee', $data['mise_a_jour_donnee'], PDO::PARAM_STR);
            $sth->bindParam(':id_supplier_size', $data['id_supplier_size'], PDO::PARAM_INT);
            $sth->bindParam(':supplier_vat_number', $data['supplier_vat_number'], PDO::PARAM_STR);
            $sth->bindParam(':contract_manager', $data['contract_manager'], PDO::PARAM_STR);
            $sth->bindParam(':pcm_link', $data['pcm_link'], PDO::PARAM_STR);
            $sth->bindParam(':entites_beneficiaires_edf_enedis', $data['entites_beneficiaires_edf_enedis'], PDO::PARAM_STR);
            $sth->bindParam(':geographic_detail', $data['geographic_detail'], PDO::PARAM_STR);
            $sth->bindParam(':acheteur', $data['acheteur'], PDO::PARAM_STR);
            $sth->bindParam(':id', $id, PDO::PARAM_INT);
            $sth->execute();
        } catch (\Exception $e) {
            throw new InternalServerException();
        }
    }

    public function add($data)
    {
        try {
            $data['content'] = $data['content']?? '';
            $data['netachat_type'] = $data['netachat_type']?? '';
            $data['netachat_id'] = $data['netachat_id']?? 0;
            $data['contrat_groupe'] = $data['contrat_groupe']?? 'false';
            $data['mise_a_jour_donnee'] = $data['mise_a_jour_donnee']?? 'non';
            $data['id_supplier_size'] = $data['id_supplier_size'] ?? null;
            $data['supplier_vat_number'] = $data['supplier_vat_number'] ?? null;
            $data['contract_manager'] = $data['contract_manager'] ?? null;
            $data['pcm_link'] = $data['pcm_link'] ?? null;
            $data['entites_beneficiaires_edf_enedis'] = $data['entites_beneficiaires_edf_enedis'] ?? null;
            $data['geographic_detail'] = $data['geographic_detail'] ?? null;
            $data['acheteur'] = $data['acheteur'] ?? null;
            $query = '
        INSERT INTO ' . $this->table . ' (label, description, created_at, updated_at, created_by, updated_by, status, created_by_full_name, updated_by_full_name, start_date, end_date, dauphin, edf_open, stipulation_for_others, partnership, protected_sector, committee, category, showcase, content, netachat_id, netachat_type, search_words, contrat_groupe, mise_a_jour_donnee, id_supplier_size, supplier_vat_number, contract_manager, pcm_link, entites_beneficiaires_edf_enedis, geographic_detail, acheteur) VALUES (:label, :description ,:created_at, :updated_at,:created_by, :updated_by, :status, :created_by_full_name, :updated_by_full_name, :start_date, :end_date, :dauphin, :edf_open, :stipulation_for_others, :partnership, :protected_sector, :committee, :category, :showcase, :content, :netachat_id, :netachat_type, :search_words, :contrat_groupe, :mise_a_jour_donnee, :id_supplier_size, :supplier_vat_number, :contract_manager, :pcm_link, :entites_beneficiaires_edf_enedis, :geographic_detail, :acheteur);
        ';
            $sth = $this->db->getPDO()->prepare($query);
            $sth->bindParam(':label', $data['label'], PDO::PARAM_STR);
            $sth->bindParam(':description', $data['description'], PDO::PARAM_STR);
            $sth->bindParam(':created_at', $data['created_at'], PDO::PARAM_STR);
            $sth->bindParam(':updated_at', $data['updated_at'], PDO::PARAM_STR);
            $sth->bindParam(':created_by', $data['created_by'], PDO::PARAM_STR);
            $sth->bindParam(':updated_by', $data['updated_by'], PDO::PARAM_STR);
            $sth->bindParam(':status', $data['status'], PDO::PARAM_INT);
            $sth->bindParam(':created_by_full_name', $data['created_by_full_name'], PDO::PARAM_STR);
            $sth->bindParam(':updated_by_full_name', $data['updated_by_full_name'], PDO::PARAM_STR);
            $sth->bindParam(':start_date', $data['start_date'], PDO::PARAM_STR);
            $sth->bindParam(':end_date', $data['end_date'], PDO::PARAM_STR);
            $sth->bindParam(':dauphin', $data['dauphin'], PDO::PARAM_BOOL);
            $sth->bindParam(':edf_open', $data['edf_open'], PDO::PARAM_BOOL);
            $sth->bindParam(':stipulation_for_others', $data['stipulation_for_others'], PDO::PARAM_BOOL);
            $sth->bindParam(':partnership', $data['partnership'], PDO::PARAM_BOOL);
            $sth->bindParam(':protected_sector', $data['protected_sector'], PDO::PARAM_BOOL);
            $sth->bindParam(':committee', $data['committee'], PDO::PARAM_INT);
            $sth->bindParam(':category', $data['category'], PDO::PARAM_INT);
            $sth->bindParam(':showcase', $data['showcase'], PDO::PARAM_BOOL);
            $sth->bindParam(':content', $data['content'], PDO::PARAM_STR);
            $sth->bindParam(':netachat_type', $data['netachat_type'], PDO::PARAM_STR);
            $sth->bindParam(':netachat_id', $data['netachat_id'], PDO::PARAM_INT);
            $sth->bindParam(':search_words', $data['words'], PDO::PARAM_STR);
            $sth->bindParam(':contrat_groupe', $data['contrat_groupe'], PDO::PARAM_STR);
            $sth->bindParam(':mise_a_jour_donnee', $data['mise_a_jour_donnee'], PDO::PARAM_STR);
            $sth->bindParam(':id_supplier_size', $data['id_supplier_size'], PDO::PARAM_INT);
            $sth->bindParam(':supplier_vat_number', $data['supplier_vat_number'], PDO::PARAM_STR);
            $sth->bindParam(':contract_manager', $data['contract_manager'], PDO::PARAM_STR);
            $sth->bindParam(':pcm_link', $data['pcm_link'], PDO::PARAM_STR);
            $sth->bindParam(':entites_beneficiaires_edf_enedis', $data['entites_beneficiaires_edf_enedis'], PDO::PARAM_STR);
            $sth->bindParam(':geographic_detail', $data['geographic_detail'], PDO::PARAM_STR);
            $sth->bindParam(':acheteur', $data['acheteur'], PDO::PARAM_STR);
            $sth->execute();
            return  $this->db->getPDO()->lastInsertId();
        } catch (\Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function deleteSheet($id)
    {
        try {
            $this->db->getPDO()->beginTransaction();
            (new SheetFile())->deleteBySheet($id);
            (new SheetContract)->deleteBySheet($id);
            (new SheetLot())->deleteLots($id, []);
            (new SheetBeneficiary)->deleteFiliales($id, []);
            (new SheetStipulation)->deleteFiliales($id, []);
            (new SheetProvider)->deleteProviders($id, []);
            (new SheetArea)->deleteZones($id, []);
            (new SheetGroup)->deleteGroups($id, []);
            $this->delete($id);
            $this->db->getPDO()->commit();
        } catch (\Exception $e) {
            if ($this->db->getPDO()->inTransaction()) {
                $this->db->getPDO()->rollback();
            }
            throw SheetException::forSheetRemove($e->getMessage());
        }
    }
    
    /**
     * delete
     *
     * @param  mixed $id
     * @return void
     */
    private function delete(int $id)
    {
        if(!is_numeric($id)){
            throw new Exception('id est obligatoire');
        }

        try {
            $query = 'DELETE FROM ' . $this->table . ' WHERE id = :id';
            $sth = $this->db->getPDO()->prepare($query);
            $sth->bindParam(':id', $id, PDO::PARAM_INT);
            $sth->execute();
        } catch (\Exception $e) {
            throw new Exception('erreur de suppression de sheet_headers');
        }
    }

    /**
     * 
     * @param string $date
     * @return mixed
     */
    public function getNetAchatSheet(): mixed
    {
        try {

            $query = 'SELECT id,netachat_id FROM ' . $this->table . ' WHERE netachat_id is not null';
            $sth = $this->db->getPDO()->prepare($query);
            $sth->execute();
            return $sth->fetchall(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            throw new InternalServerException($e->getMessage());
        }
    }


    public function getSheetByBeneficiary($bId)
    {
        try {
            $query = 'SELECT DISTINCT  s.id, s.label FROM ' . $this->table . ' s INNER JOIN ' . $this->sheetBeneficiaryTable . ' sf ON sf.sheet_id = s.id WHERE sf.filiale_id = :filiale_id';
            $sth = $this->db->getPDO()->prepare($query);
            $sth->bindParam(':filiale_id', $bId, PDO::PARAM_INT);
            $sth->execute();
            return  $sth->fetchall(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            throw new InternalServerException();
        }
    }

    public function getSheetByStipulation($bId)
    {
        try {
            $query = 'SELECT DISTINCT  s.id, s.label FROM ' . $this->table . ' s INNER JOIN ' . $this->sheetStipulationTable . ' sf ON sf.sheet_id = s.id WHERE sf.filiale_id = :filiale_id';
            $sth = $this->db->getPDO()->prepare($query);
            $sth->bindParam(':filiale_id', $bId, PDO::PARAM_INT);
            $sth->execute();
            return  $sth->fetchall(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            throw new InternalServerException();
        }
    }


    public function getSheetByProvider($pId)
    {
        try {
            $query = 'SELECT DISTINCT  s.id, s.label FROM ' . $this->table . ' s INNER JOIN ' . $this->sheetProviderTable . ' sp ON sp.sheet_id = s.id WHERE sp.provider_code = :provider_code';
            $sth = $this->db->getPDO()->prepare($query);
            $sth->bindParam(':provider_code', $pId, PDO::PARAM_INT);
            $sth->execute();
            return  $sth->fetchall(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            throw new InternalServerException();
        }
    }

    public function getSheetByNetAchat($netAchatId)
    {
        try {
            $query = 'SELECT DISTINCT  s.id, s.label, s.netachat_id FROM ' . $this->table . ' s WHERE  netachat_id=:netachat_id';
            $sth = $this->db->getPDO()->prepare($query);
            $sth->bindParam(':netachat_id', $netAchatId, PDO::PARAM_INT);
            $sth->execute();
            return  $sth->fetch(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            throw new InternalServerException();
        }
    }
   

    public function getByContract($contract_code)
    {
        try {
            $query = 'SELECT DISTINCT  s.id,s.label  FROM ' . $this->table . ' s INNER JOIN ' . $this->sheetContract . ' sc ON sc.sheet_id = s.id WHERE sc.contract_code = :contract_code ORDER BY s.label ' ;
            $sth = $this->db->getPDO()->prepare($query);
            $sth->bindParam(':contract_code', $contract_code, PDO::PARAM_STR);
            $sth->execute();
            return  $sth->fetchall(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            throw new InternalServerException();
        }
        
    }

    public function getSheetByContract($contractCode)
    {
        $contractLink = 'app/contrat/details/' . $contractCode;
        try {
            $query = "SELECT s.id,s.label FROM {$this->table} s WHERE s.content LIKE :contractLink";
            $sth = $this->db->getPDO()->prepare($query);
            $sth->bindValue(':contractLink', '%' . $contractLink . '%', PDO::PARAM_STR);
            $sth->execute();
            return  $sth->fetchall(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            throw new InternalServerException();
        }
    }
    



    public function updateEndDate($id, $endDate)
    {
        try {
            $query = "UPDATE {$this->table}  set end_date=:end_date where id=:id   ";
            $sth = $this->db->getPDO()->prepare($query);
            $sth->bindParam(':end_date', $endDate, PDO::PARAM_STR);
            $sth->bindParam(':id', $id, PDO::PARAM_INT);
            
            return $sth->execute();
        } catch (\Exception $e) {
            throw new InternalServerException();
        }
    }
}
