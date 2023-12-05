<?php

namespace App\Models;

use App\Exceptions\InternalServerException;
use App\Models\Subscription;
use App\Models\Sheet;

/**
 * Description of Notification
 *
 * @author HGDDEDBNF
 */
class Notification extends Model {

    protected $tableName = 'notifications';
    protected $subscriberTable;
    protected $sheetHeader;
    private $subscribers;

    public function __construct() {
        parent::__construct();
        $this->subscriberTable = (new Subscription())->table;
        $this->sheetHeader = (new Sheet())->table;
        $this->$subscribers = new Subscription();
    }

    /**
     * 
     * Afficher les mises a jours des fiches marchés sur lesquels l'utlisateur inscrit
     * 
     * @param string $userNni
     * @return type
     * @throws InternalServerException
     */
    public function getList($userNni = ''): array {       
        try {
            $query = "SELECT DISTINCT n.*, sheet.label FROM {$this->table} n  INNER JOIN {$this->subscriberTable} s on n.sheet_id = s.sheet_id INNER JOIN {$this->sheetHeader} sheet on sheet.id=s.sheet_id " ;
            if (!empty($userNni) && !is_null($userNni)):
                $query .= "where n.user_nni= :user_nni and n.is_notified = false";
            endif;
          
            $sth = ($this->db->getPDO()->prepare($query));
            $sth->bindParam(':user_nni', $userNni, \PDO::PARAM_STR);
            $sth->execute();
            return $sth->fetchall(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            throw new InternalServerException($e->getMessage());
        }
    }
    
    public function findBySheetId(int $sheetId): array {       
        try {
            $query = "SELECT id  FROM {$this->table} where sheet_id= :sheet_id";
            $sth = ($this->db->getPDO()->prepare($query));
            $sth->bindParam(':sheet_id', $sheetId, \PDO::PARAM_STR);
            $sth->execute();
            return $sth->fetchall(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
            throw new InternalServerException($e->getMessage());
        }
    }
    
    /**
     * @param int $id
     * @return void
     * @throws \Exception
     */
    public function updateNotified(int $id): void {
        try {
            $query = 'UPDATE ' . $this->table . ' set is_notified=:notified, updated_at=:date WHERE id=:id;';
            $notified = true;
            $updatedAt = (new \DateTime())->format('Y-m-d H:i:s');
            $sth = $this->db->getPDO()->prepare($query);
            $sth->bindParam(':notified', $notified, \PDO::PARAM_BOOL);
            $sth->bindParam(':date', $updatedAt, \PDO::PARAM_STR);
            $sth->bindParam(':id', $id, \PDO::PARAM_INT);
            $sth->execute();
        } catch (\Exception $e) {
            throw new InternalServerException($e->getMessage());
        }
    }
    
    /**
     * 
     *
     */
    public function insertNotification($sheet_id): void{
        try {
            $updatedAt = (new \DateTime())->format('Y-m-d H:i:s');
            $notified = false ;

            // On récupére tous les user_nni abonnés à la sheet_id
            $listAbonnees = $this->subscribers->getSubscribers($sheet_id);

            // Pour chaque user_nni abonné, on insérere une nouvelle notification
            foreach ($listAbonnees as $user_nni) {
                $query = 'INSERT INTO ' . $this->table . '  (user_nni, is_notified, sheet_id, updated_at) VALUES(:user_nni,:notified,:sheet_id,:date)';
                $sth = $this->db->getPDO()->prepare($query);
                $sth->bindParam(':notified', $notified, \PDO::PARAM_BOOL);
                $sth->bindParam(':date', $updatedAt, \PDO::PARAM_STR);
                $sth->bindParam(':user_nni', $user_nni, \PDO::PARAM_STR);
                $sth->bindParam(':sheet_id', $sheet_id, \PDO::PARAM_INT);
                $sth->execute();
            }
        } catch (\Exception $e) {
            throw new InternalServerException($e->getMessage());
        }
    }
    
    public function findByEndDateWithIn($endDate){
        $sth = $this->db->getPDO() // Execution de la requete
                ->prepare('SELECT * FROM ' . $this->table . ' WHERE updated_at<=:endDate and is_notified != true'); 
        $sth->bindParam(':endDate', $endDate, \PDO::PARAM_STR); // Bind params
        $sth->execute(); // Lancer la requete
        
        return $sth->fetchAll(\PDO::FETCH_ASSOC); // Recupérer les infos sous forme une table
    }

}
