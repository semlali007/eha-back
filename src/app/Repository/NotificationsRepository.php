<?php
 
namespace App\Repository;

use App\Models\Notification ;
use Core\Util\Date ;
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



    public function updateAllNotified(string $nni): void
    {
        $notif = (new Notification());
        $list = $notif->getList($nni);
        foreach ($list as $n) {
            $notif->updateNotified($n['id']);
        }
    }

    
    /**
     * Example value of $notifs = [ 
     *   0 => ["sheet_id" => "sheetid"],
     *   1 => ["sheet_id" => "sheetid"]
     * ]
     * @param array $notifs
     * @return void
     */
    public function insertNotification(int $sheet_id):void{
       
        try {
            foreach ($sheetIds as $sheet_id) {
                (new Notification())->insertNotification($sheet_id['sheet_id']);
            }
        } catch (\Exception $e) {
            throw new InternalServerException($e->getMessage());
        }
    }

    
    
    public function getLengthNotifsByNni($nni){
        return count($this->getListByNni($nni));
    }
    
    public function autoRemoveNotifsAfter(int $days  = 30){
        $endDate = Date::generateDateMoins($days) ;
        return (new Notification())// Generer le jour Datetime format + 30 (exemple)
                ->findByEndDateWithIn($endDate); 
    }
}
