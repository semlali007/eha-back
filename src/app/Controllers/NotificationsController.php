<?php
 
namespace App\Controllers;
use Core\Http\Request ;
/**
 * Description of NotificationsController
 *
 * @author HGDDEDBN
 */
class NotificationsController extends Controller{
   
    private $notificationsRepository;

    /**
     * 
     * @param type $roles
     */
    public function __construct($roles = []) {
        parent::__construct($roles);
        $this->notificationsRepository = new \App\Repository\NotificationsRepository();
    }
    
    
    
    /**
     * Liste des elements dans le menu 
     * @return Array
     */
    public function index(string $nni) {
        return $this->response($this->notificationsRepository->getListByNni($nni));
    }
    
     
    /**
     * Liste des elements dans le menu 
     * @return Array
     */
    public function update() {
        $input = Request::input();
        return $this->response($this->notificationsRepository->updateNotified($input['id']));
    }

        /**
     * Liste des elements dans le menu 
     * @return Array
     */
    public function updateList() {
        $input = Request::input();
        return $this->response($this->notificationsRepository->updateAllNotified($input['nni']));
    }
    
    

    /**
     * 
     */
    public function add(){
        $data = [
            0 => ["sheet_id" => 3],
            1 => ["sheet_id" => 5]
            ];
            $this->notificationsRepository->insertNotification($data);
            return $this->response(['message' => 'Notifications inserted successfully']);
    }
    
}
