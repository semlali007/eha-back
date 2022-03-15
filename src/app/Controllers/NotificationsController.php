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
        return $this->response($this->notificationsRepository->updateNotified(Request::request("id")));
    }
    
    /**
     * 
     */
    public function add(){
        $data = [
            0 => ["user_nni" => "HGDDEDBN", "sheet_id" => 3],
            1 => ["user_nni" => "HGDDEDBN", "sheet_id" => 5]
            ];
        return $this->response($this->notificationsRepository->insertNotification($data));
    }
}
