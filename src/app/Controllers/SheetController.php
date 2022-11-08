<?php

namespace App\Controllers;

use App\Manager\SheetManager;
use App\Repository\SheetRepository;
use App\Services\FileService;
use App\Repository\NotificationsRepository ;
use Core\Http\Request;

class SheetController extends Controller {

    private $sheetRepository;
    private $sheetManager;
    private $notificationsRepository;
    private $currentNni  ;

    public function __construct($roles = [])
    {
        parent::__construct($roles);

        $this->sheetRepository = new SheetRepository();
        $this->sheetManager = new SheetManager();
        $this->notificationsRepository = new NotificationsRepository();
    }

    public function index() {
        $this->currentNni = $this->getCurrentUser()->getNni();
        $options = $this->decode(file_get_contents("php://input"));
	$user = $this->getCurrentUser();
	if( !$user->hasRole(['EHA_ADMIN', 'EHA_GESTIONNAIRE'])){
            $options['search']['status'] = ['1'];
        }
        return $this->response($this->sheetRepository->search($options, $this->currentNni));
    }

    public function show(int $id) {
        $this->currentNni = $this->getCurrentUser()->getNni();
        return $this->response(['sheet' => $this->sheetRepository->getDetails($id, $this->currentNni)]);
    }

    public function export() {
        $options = json_decode(file_get_contents("php://input"), true) ?? [];
        return $this->response(['sheets' => $this->sheetRepository->export($options)], $this->currentUser->getNni());
    }

    public function update() {
        $sheetData = json_decode($_POST['model'], true);
        $this->notificationsRepository->insertNotification($this->currentUser->getNni(), $sheetData['id']);
        return $this->sheetManager->update($this->currentUser,$sheetData);
    }

    public function updateDraft() {
        $sheetData = json_decode($_POST['model'], true);
        $this->notificationsRepository->insertNotification($this->currentUser->getNni(), $sheetData['id']);
        $sheetData['draft'] = true;
        return $this->sheetManager->update($this->currentUser,$sheetData);
    }

    public function add() {
        $sheetData = json_decode($_POST['model'], true);
        return $this->sheetManager->add($this->currentUser,$sheetData);
    }

    public function addDraft() {
        $sheetData = json_decode($_POST['model'], true);
        $sheetData['draft'] = true;
        return $this->sheetManager->add($this->currentUser,$sheetData);
    }

    public function download() {
        $options = $this->decode(file_get_contents("php://input"));
        return FileService::download($options);
    }
    

    public function subscribe() {
        $options = json_decode(file_get_contents("php://input"), true) ?? [];
        $this->currentNni = $this->getCurrentUser()->getNni();
        if($this->currentNni!=$options['user']){
            var_dump('problème');
        }

        return $this->response(['sheet' => $this->sheetManager->subscribe($options['sheet'],$options['user'])]);
    }

    public function unsubscribe() {
        $options = json_decode(file_get_contents("php://input"), true) ?? [];
        $this->currentNni = $this->getCurrentUser()->getNni();
        if($this->currentNni!=$options['user']){
            var_dump('problème');
        }

        return $this->response(['sheet' => $this->sheetManager->unsubscribe($options['sheet'],$options['user'])]);
    }


    /**
     * remove
     *
     * @param  mixed $id
     * @return void
     */
    public function remove(int $id) {
        return $this->response($this->sheetManager->remove($id));
    }


    public function filter()
    {
        $options = json_decode(file_get_contents("php://input"), true) ?? [];
        switch ($options['type']) {
            case 'beneficiary':
                return $this->response($this->sheetRepository->getSheetByBeneficiary($options['code']));
                break;
            case 'provider':
                return $this->response($this->sheetRepository->getSheetByProvider($options['code']));
                break;
            default:
            //
                break;

        }
        
    }

}
