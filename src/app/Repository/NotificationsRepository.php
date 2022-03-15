<?php
 
namespace App\Repository;

use App\Models\Notification ;
use Core\Http\Request ;

/**
 * Description of NotificationsRepository
 *
 * @author HGDDEDBN
 */
class NotificationsRepository {
    
    /**
     * retourner la liste des notifs par user ou all
     * @return array
     */
    public function getListByNni($userNni ):array{
        return (new Notification())->getList($userNni) ;
    }
    
     /**
     * retourner la liste des notifs par user ou all
     * @return array
     */
    public function updateNotified(int $id):void{
        
         (new Notification())
                 ->updateNotified($id) ;
    }
    
    /**
     * Example value of $notifs = [ 
     * 0 => ["user_nni" => "nni", "sheet_id" => "sheetid"],
     * 1 => ["user_nni" => "nni", "sheet_id" => "sheetid"]
     * ]
     * @param array $notifs
     * @return void
     */
    public function insertNotification(array $notifs = []):void{
        foreach ($notifs as $valeurNotif) {
            (new Notification())
                 ->insertNotification($valeurNotif) ;
        }
    }
    
    public function getLengthNotifsByNni($nni){
        return count($this->getListByNni($nni));
    }
}
