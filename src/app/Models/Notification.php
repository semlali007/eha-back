<?php

namespace App\Models;

use App\Exceptions\InternalServerException;
use App\Models\Subscription;

/**
 * Description of Notification
 *
 * @author HGDDEDBNF
 */
class Notification extends Model {

    protected $tableName = 'notifications';
    protected $subscriberTable;

    public function __construct() {
        parent::__construct();
        $this->subscriberTable = (new Subscription())->table;
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
            
            $query = "SELECT n.* FROM {$this->table} n INNER JOIN {$this->subscriberTable} s on n.sheet_id = s.sheet_id ";
            if (!empty($userNni) && !is_null($userNni)):
                $query .= "where n.user_nni= :user_nni";
            endif;
           
            $sth = ($this->db->getPDO()->prepare($query));
            $sth->bindParam(':user_nni', $userNni, \PDO::PARAM_STR);
            $sth->execute();
            return $sth->fetchall(\PDO::FETCH_ASSOC);
        } catch (\Exception $e) {
           
            throw new InternalServerException();
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
            $updatedAt = (new \DateTime())->format('Y-m-d');
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
     * @param array $notif
     */
    public function insertNotification(array $notif): void{
         try {
            $query = 'INSERT INTO ' . $this->table . ' '
                    . '(user_nni, sheet_id) VALUES(:user_nni,:sheet_id)';
            $sth = $this->db->getPDO()->prepare($query);
            $sth->bindParam(':user_nni', $notif['user_nni'], \PDO::PARAM_STR);
            $sth->bindParam(':sheet_id', $notif['sheet_id'], \PDO::PARAM_INT);
            $sth->execute();
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
