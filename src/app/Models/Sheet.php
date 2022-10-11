<?php

namespace App\Models;

use App\Exceptions\InternalServerException;
use App\Exceptions\SheetException;
use App\Models\Beneficiary;
use App\Models\Area;
use App\Models\Group;
use App\Models\SheetArea;
use App\Models\SheetProvider;
use App\Models\SheetGroup;
use App\Models\SheetBeneficiary;
use App\Models\Committee;
use App\Models\Subscription;
use App\Services\SheetService ;

use Exception;
use PDO;

class Sheet extends Model
{
    protected $tableName = 'sheet_headers';
    protected $beneficiaryTable;
    protected $areaTable;
    protected $groupTable;
    protected $providerTable;
    protected $sheetBeneficiaryTable;
    protected $sheetAreaTable;
    protected $sheetProviderTable;
    protected $sheetGroupTable;
    protected $searchCondition;
    protected $committeeTable;
    protected $sheetContract ;

    public function __construct()
    {
        parent::__construct();
        $this->beneficiaryTable = (new Beneficiary())->table;
        $this->areaTable = (new Area())->table;
        $this->groupTable = (new Group())->table;
        $this->providerTable = (new Provider())->table;
        $this->sheetBeneficiaryTable = (new SheetBeneficiary())->table;
        $this->sheetAreaTable = (new SheetArea())->table;
        $this->sheetProviderTable = (new SheetProvider())->table;
        $this->sheetGroupTable = (new SheetGroup())->table;
        $this->committeeTable = (new Committee())->table;
        $this->subscriptionTable = (new Subscription())->table;
        $this->searchCondition = null;
        $this->sheetContract = (new SheetContract())->table ;
    }

    public function searchWithoutPagination($options)
    {
        try {
            $orderBy = $options['order_by'] ?? 'id';
            $direction = $options['direction'] ?? 'asc';
            $data = [];
            $query = 'SELECT s.id, s.label, s.status, s.description, s.start_date, s.end_date,s.dauphin,s.edf_open,s.stipulation_for_others, s.partnership, s.protected_sector,s.committee   FROM ' . $this->table . '  as s ' . $this->getSearchCondition($options) . '  ORDER BY s.' . $orderBy . ' ' . $direction;
            $sth = $this->db->getPDO()->prepare($query);
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
            $worldsList = explode(" ", $options['search']['searchText']);
            $list = [];
            foreach ($worldsList as $d) {
                $w = str_replace(
                    "'",
                    '',
                    $d
                );
                $w = str_replace(
                    " ",
                    '',
                    $w
                );
                if(strlen($w)>0)
                $list[] = $w;
            }

            $words = implode("|", $list);
            //$orderBy = $options['order_by'] ?? 'rank';
            //$direction = $options['direction'] ?? 'desc';
            $orderBy = 'rank';
            $direction = 'desc';
            $perpage = $options['perpage'] ?? 10;
            $page =  $options['page'] ?? 1;
            $page = ($page - 1) * $perpage;
            $query = "select distinct *,
            CASE 
            WHEN sub.sheet_id is not NULL THEN true
            ELSE false
            END subscribed
            from ( SELECT distinct s.id,TS_RANK_CD(TO_TSVECTOR(unaccent(lower(S.label)) || ' ' || unaccent(lower(S.description))),

            QUERY) AS RANK, s.label, s.status,s.updated_at,s.created_at, to_char(s.updated_at, 'DD/MM/YYYY à HH24:MI:SS') as formated_updated_date 
                     
                      FROM  TO_TSQUERY(unaccent(lower('{$words}'))) QUERY, {$this->table}  as s 
                       
                      
                      {$this->getSearchCondition($options)}  ORDER BY RANK desc  LIMIT {$perpage} offset {$page} 
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
    public function getFormattedWords($words){
        $worldsList = $pieces = explode(" ", $words);
            $list = [];
            foreach ($worldsList as $d) {
                $w = str_replace(
                    "'",
                    '',
                    $d
                );
                $w = str_replace(
                    " ",
                    '',
                    $w
                );
                if(strlen($w)>0)
                $list[] = $w;
            }
            
            return implode("|", $list);
    }
    public function getSearchCondition($params, $forFilter=null)
    {
        $search = '';
        $schema = pg_escape_string(getenv("DB_SCHEMA"));
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

        $searchProvider = pg_escape_string(strtolower($options['searchProvider'] ?? ""));
        if (($searchProvider != '')) {
            $searchProvider = $this->getFormattedWords(strtolower($searchProvider));
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
        $search_input = pg_escape_string(strtolower($options['searchText'] ?? ""));
        $isCode = SheetService::isCodeContract($search_input);
        if ($isCode) {
            $search .= " LEFT JOIN {$this->sheetContract} sc ON  sc.sheet_id = s.ID ";
        }
        if ($search_input !== '') {
            if ($isCode) {
                $search .= " WHERE   TO_TSVECTOR(unaccent(lower(S.content))) @@ TO_TSQUERY(unaccent(lower('{$search_input}'))) ";
            } else {
                $search .= " WHERE   TO_TSVECTOR(unaccent(lower(S.label)) || ' ' || unaccent(lower(S.description))) @@ TO_TSQUERY(unaccent(lower('{$params['words']}'))) ";
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
    if (!is_array($forFilter) || !array_key_exists('committees', $forFilter)) {
        $committees = $options['committees'] ?? [];
        if (!empty($committees)) {
            $this->addCondition($search, 's.committee', $committees);
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
    
    function addCondition(&$sql, $key, $value)
    {
        if (is_null($sql) || is_null($key) || is_null($value)) {
            return $this;
        }

        $valueStr = '(' . implode(', ', $value) . ')';
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
            $query = '
                UPDATE ' . $this->table . ' set label=:label, description=:description, updated_at=:updated_at, updated_by=:updated_by, status=:status, updated_by_full_name =:updated_by_full_name, start_date=:start_date, end_date=:end_date, dauphin=:dauphin, edf_open=:edf_open, stipulation_for_others=:stipulation_for_others, partnership=:partnership, protected_sector=:protected_sector, committee=:committee, showcase=:showcase, content=:content WHERE id =:id;
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
            $sth->bindParam(':showcase', $data['showcase'], PDO::PARAM_BOOL);
            $sth->bindParam(':content', $data['content'], PDO::PARAM_BOOL);
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
            $query = '
        INSERT INTO ' . $this->table . ' (label, description, created_at, updated_at, created_by, updated_by, status, created_by_full_name, updated_by_full_name, start_date, end_date, dauphin, edf_open, stipulation_for_others, partnership, protected_sector, committee, showcase, content, netachat_id, netachat_type) VALUES (:label, :description ,:created_at, :updated_at,:created_by, :updated_by, :status, :created_by_full_name, :updated_by_full_name, :start_date, :end_date, :dauphin, :edf_open, :stipulation_for_others, :partnership, :protected_sector, :committee, :showcase, :content, :netachat_id, :netachat_type);
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
            $sth->bindParam(':showcase', $data['showcase'], PDO::PARAM_BOOL);
            $sth->bindParam(':content', $data['content'], PDO::PARAM_STR);
            $sth->bindParam(':netachat_type', $data['netachat_type'], PDO::PARAM_STR);
            $sth->bindParam(':netachat_id', $data['netachat_id'], PDO::PARAM_INT);
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
            var_dump($e);exit;
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

}
