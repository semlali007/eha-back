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
        $this->searchCondition = null;
    }

    public function searchWithoutPagination($options)
    {
        try {
            $orderBy = $options['order_by'] ?? 'id';
            $direction = $options['direction'] ?? 'asc';
            $data = [];
            $query = 'SELECT s.id, s.label, s.status, s.description, s.start_date, s.end_date,s.dauphin,s.edf_open,s.stipulation_for_others, s.partnership, s.protected_sector,s.committee   FROM ' . $this->table . '  as s ' . $this->searchCondition . '  ORDER BY s.' . $orderBy . ' ' . $direction;
            $sth = $this->db->getPDO()->prepare($query);
            $sth->execute();
            $data['sheets'] = $sth->fetchall(PDO::FETCH_ASSOC);
            return $data;
        } catch (\Exception $e) {
            throw new InternalServerException();
        }
    }

    public function search($options)
    {
        try {
            $orderBy = $options['order_by'] ?? 'id';
            $direction = $options['direction'] ?? 'asc';
            $perpage = $options['perpage'] ?? 10;
            $page =  $options['page'] ?? 1;
            $page = ($page - 1) * $perpage;
            $query = "SELECT s.id, s.label, s.status,s.updated_at,s.created_at, to_char(s.updated_at, 'DD/MM/YYYY à HH24:MI:SS') as formated_updated_date FROM {$this->table}  as s {$this->searchCondition}  ORDER BY s.{$orderBy} {$direction} LIMIT {$perpage} offset {$page} ";

            $sth = $this->db->getPDO()->prepare($query);
            $sth->execute();
            return $sth->fetchall(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            throw new InternalServerException();
        }
    }


    public function getCount()
    {
        try {
            $query = "SELECT COUNT(s.*) FROM " . $this->table . " s $this->searchCondition";
            $sth = $this->db->getPDO()->prepare($query);
            $sth->execute();
            
            return  $sth->fetch(PDO::FETCH_ASSOC)['count'];
        } catch (\Exception $e) {
            throw new InternalServerException();
        }
    }

    public function initSearchCondition($params)
    {
        $options = $params['search'] ?? [];
        $beneficiariesList = $options['beneficiaries'] ?? [];
        $areas = $options['areas'] ??  [];
        $providersList = $options['providers'] ?? [];
        $providers = [];
        foreach ($providersList as $provider) {
            $providers[] = $provider['code'];
        }
        $beneficiaries = [];
        foreach ($beneficiariesList as $beneficiary) {
            $beneficiaries[] = $beneficiary['code'];
        }


        $directions = $options['directions'] ?? [];
        
        $search = '';

        if (count($beneficiaries) > 0) {
            $beneficiariesStr = '(' . implode(', ', $beneficiaries) . ')';
            $beneficiariesCount = count($beneficiaries);
            $search .= " INNER JOIN (select sheet_id from {$this->sheetBeneficiaryTable}  where filiale_id in $beneficiariesStr  group by sheet_id having count(filiale_id)=$beneficiariesCount) sf on sf.sheet_id = s.id  ";
        }

        if (count($areas) > 0) {
            $areasStr = '(' . implode(', ', $areas) . ')';
            $areasCount = count($areas);
            $search .= " INNER JOIN (select sheet_id from {$this->sheetAreaTable}  where zone_id in $areasStr  group by sheet_id having count(zone_id)=$areasCount) sz on sz.sheet_id = s.id  ";
        }

        if (count($providers) > 0) {
            $providersStr = '(\'' . implode('\',\'', $providers) . '\')';
            $providersCount = count($providers);
            $search .= " INNER JOIN (select sheet_id from {$this->sheetProviderTable}  where provider_code in $providersStr  group by sheet_id having count(provider_code) = $providersCount) sp on sp.sheet_id = s.id  ";
        }

        if (count($directions) > 0) {
            $directionsStr = '(' . implode(', ', $directions) . ')';
            $directionsCount = count($directions);
            $search .= " INNER JOIN (select sheet_id from {$this->sheetGroupTable}  where group_id in $directionsStr  group by sheet_id having count(group_id)=$directionsCount) sg on sg.sheet_id = s.id  ";
        }

        $search_input = strtolower($options['searchText'] ?? "");
        $searchProvider = strtolower($options['searchProvider'] ?? "");

        if (($searchProvider != '')) {
            $searchProvider = strtolower($searchProvider);
            $queryListId = " (SELECT DISTINCT provider_code FROM {$this->providerTable} WHERE  lower(provider_label) LIKE '%$searchProvider%' ) ";
            $condition = "  provider_code in $queryListId";
            $search .= " INNER JOIN 
            (
                select sheet_id 
                from {$this->sheetProviderTable}  
                where $condition
            ) spa 
            on spa.sheet_id = s.id  ";
        }

        $status = $options['status'] ?? '';
        $edfOpen = $options['edf_open'] ?? '';
        $showcase = $options['showcase'] ?? '';
        $partnership = $options['partnership'] ?? '';
        $protectedSector = $options['protected_sector'] ?? '';
        $committees = $options['committees'] ?? [];
        $dauphin = $options['dauphin'] ?? '';
        $search .= ' WHERE 1= 1 ';
        if ($search_input !== '') {
            $search .= " AND ( lower(s.label) LIKE '%$search_input%' OR lower(s.description) like '%$search_input%' ) ";
        }

        if ($status !== '') {
            $this->addCondition($search, 's.status', $status);
        }

        if ($edfOpen !== '') {
            $this->addCondition($search, 's.edf_open', ($edfOpen == true) ? 'true' : 'false');
        }

        if ($protectedSector  !== '') {
            $this->addCondition($search, 's.protected_sector', ($protectedSector  == true) ? 'true' : 'false');
        }

        if ($dauphin !== '') {
            $this->addCondition($search, 's.dauphin', ($dauphin == true) ? 'true' : 'false');
        }

        if ($partnership !== '') {
            $this->addCondition($search, 's.partnership', ($partnership == true) ? 'true' : 'false');
        }

        if ($showcase !== '') {
            $this->addCondition($search, 's.showcase', ($showcase == true) ? 'true' : 'false');
        }
        
        if (is_array($committees) && isset($committees[0])) {
            $this->addCondition($search, 's.committee', $committees[0]);
        }


        $this->searchCondition  = $search;
    }

    public function groupedByOpenEdf()
    {
        try {
            $sql = "SELECT COUNT(s.*) as count, s.edf_open as code FROM " . $this->table . " s {$this->searchCondition}" . " GROUP BY edf_open";
            $sth = $this->db->getPDO()->prepare($sql);
            $sth->execute();
            return  $sth->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            throw new InternalServerException();
        }
    }

    public function groupedByShowcase()
    {
        try {
            $sql = "SELECT COUNT(s.*) as count, s.showcase as code FROM " . $this->table . " s {$this->searchCondition}" . " GROUP BY showcase";
            $sth = $this->db->getPDO()->prepare($sql);
            $sth->execute();
            return  $sth->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            throw new InternalServerException();
        }
    }

    public function groupedByPartnership()
    {
        try {
            $sql = "SELECT COUNT(s.*) as count, s.partnership as code FROM " . $this->table . " s {$this->searchCondition}" . " GROUP BY partnership";
            $sth = $this->db->getPDO()->prepare($sql);
            $sth->execute();
            return  $sth->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            throw new InternalServerException();
        }
    }
    public function groupedBySector()
    {
        try {
            $sql = "SELECT COUNT(s.*) as count, s.protected_sector as code FROM " . $this->table . " s {$this->searchCondition}" . " GROUP BY protected_sector";
            $sth = $this->db->getPDO()->prepare($sql);
            $sth->execute();
            return  $sth->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            throw new InternalServerException();
        }
    }

    public function groupedByDauphin()
    {
        try {
            $sql = "SELECT COUNT(s.*) as count, s.dauphin as code FROM " . $this->table . "  s {$this->searchCondition}" . " GROUP BY dauphin";
            $sth = $this->db->getPDO()->prepare($sql);
            $sth->execute();
            return  $sth->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            throw new InternalServerException();
        }
    }

    public function groupedByStatus()
    {
        try {
            $sql = "SELECT COUNT(s.*) as count, s.status as code FROM " . $this->table . "  s {$this->searchCondition}" . " GROUP BY status";
            $sth = $this->db->getPDO()->prepare($sql);
            $sth->execute();
            return  $sth->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            throw new InternalServerException();
        }
    }

    public function groupedByFiliale()
    {
        try {
            $sql = "
       SELECT f.name , f.id as code , 1 as count  from {$this->beneficiaryTable} f 
       
         ";
            $sth = $this->db->getPDO()->prepare($sql);
            $sth->execute();
            return  $sth->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            throw new InternalServerException();
        }
    }

    public function groupedByProvider()
    {
        try {
            $sql = "
       SELECT p.provider_label as name , p.provider_code as code , providers.count  from {$this->providerTable} p INNER join 
         
        (SELECT  count(sp.*) as count, sp.provider_code as code FROM   {$this->sheetProviderTable} sp WHERE sp.sheet_id  in
         ( SELECT s.id  FROM " . $this->table . "  s  {$this->searchCondition} ) GROUP BY sp.provider_code)  providers on p.provider_code =  providers.code
         
         order by providers.count desc, p.provider_label 
         ";

            $sth = $this->db->getPDO()->prepare($sql);
            $sth->execute();
            return  $sth->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            throw new InternalServerException();
        }
    }

    public function groupedByZone()
    {
        try {
            $sql = "
       SELECT z.name , z.id as code , areas.count  from {$this->areaTable} z INNER join 
         
        (SELECT  count(sz.*) as count, sz.zone_id as code FROM   {$this->sheetAreaTable} sz WHERE sz.sheet_id  in
         ( SELECT s.id  FROM " . $this->table . "  s  {$this->searchCondition} ) GROUP BY sz.zone_id)  areas on z.id =  areas.code
         
         order by areas.count desc,z.name limit 15
         ";
            $sth = $this->db->getPDO()->prepare($sql);
            $sth->execute();
            return  $sth->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            throw new InternalServerException();
        }
    }

    public function groupedByDirections()
    {
        try {
            $sql = "
       SELECT d.name , d.id as code , directions.count  from {$this->groupTable} d INNER join 
         
        (SELECT  count(sd.*) as count, sd.group_id as code FROM   {$this->sheetGroupTable} sd WHERE sd.sheet_id  in
         ( SELECT s.id  FROM " . $this->table . "  s  {$this->searchCondition} ) GROUP BY sd.group_id)  directions on d.id =  directions.code
         
         order by directions.count desc,d.name limit 15
         ";
            $sth = $this->db->getPDO()->prepare($sql);
            $sth->execute();
            return  $sth->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            throw new InternalServerException();
        }
    }



    public function groupedByCommittees()
    {
        try {
            $sql = "SELECT COUNT(s.*) as count, s.committee as code, c.label as name FROM " . $this->table . "  s " . " INNER join {$this->committeeTable} c
             ON c.code = s.committee {$this->searchCondition} GROUP BY committee, c.label";
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

        $sql .= " AND ($key = $value) ";
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
                ->prepare('UPDATE ' . $this->table . ' SET status=\'-1\' WHERE end_date<=:endDate and (status=1 or status=0)');
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
                UPDATE ' . $this->table . ' set label=:label, description=:description, updated_at=:updated_at, updated_by=:updated_by, status=:status, updated_by_full_name =:updated_by_full_name, start_date=:start_date, end_date=:end_date, dauphin=:dauphin, edf_open=:edf_open, stipulation_for_others=:stipulation_for_others, partnership=:partnership, protected_sector=:protected_sector, committee=:committee, showcase=:showcase WHERE id =:id;
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
            $sth->bindParam(':id', $id, PDO::PARAM_INT);
            $sth->execute();
        } catch (\Exception $e) {
            throw new InternalServerException();
        }
    }

    public function add($data)
    {
        try {
            $query = '
        INSERT INTO ' . $this->table . ' (label, description, created_at, updated_at, created_by, updated_by, status, created_by_full_name, updated_by_full_name, start_date, end_date, dauphin, edf_open, stipulation_for_others, partnership, protected_sector, committee, showcase) VALUES (:label, :description ,:created_at, :updated_at,:created_by, :updated_by, :status, :created_by_full_name, :updated_by_full_name, :start_date, :end_date, :dauphin, :edf_open, :stipulation_for_others, :partnership, :protected_sector, :committee, :showcase);
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
}
