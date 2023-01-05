<?php

namespace App\Controllers;

use App\Manager\Hotline\HotlineManager;
use App\Models\HotlineEmail;
use App\Models\HotlineGroup;
use App\Models\HotlineTheme;
use App\Repository\Hotline\HotlineRepository;
use Exception;

class HotlineController extends Controller
{
    private  $hotlineRepository;
    private  $hotlineManager;
    public function __construct($roles = [])
    {
        parent::__construct($roles);
        $this->hotlineRepository = new HotlineRepository();
        $this->hotlineManager = new HotlineManager();
    }

    public function index()
    {
        try {
            $options = $this->decode(file_get_contents("php://input"), true);
            return $this->response($this->hotlineRepository->getList($options, $this->currentUser));
        } catch (Exception $ex) {
            return $this->error($ex);
        }
    }

    public function configuration()
    {
        try {
            return $this->response([
                'themes' => (new HotlineTheme($this->getDB()))->getList(),
                'families' => (new HotlineGroup($this->getDB()))->getList(),
                'categories' => (new HotlineEmail($this->getDB()))->getList()
            ]);
        } catch (Exception $ex) {
            return $this->error($ex);
        }
    }

    public function create()
    {
        try {
            $user = $this->currentUser ;
            $options = $this->decode(file_get_contents("php://input"));
            $this->hotlineManager->create($options,$user);
            return $this->response([
                'status' => true
            ]);
        } catch (Exception $ex) {
            return $this->error($ex);
        }
    }

    public function show($id, ?string $sha=null)
    {
        try {
            return $this->response($this->hotlineRepository->getHotline([
                'id' => $id,
                'sha' => $sha
            ]));
        } catch (Exception $ex) {
            return $this->error($ex);
        }
    }

    public function getList()
    {
        try {
           $header = [
                "Numéro de question", "Thème", "Famille", "Catégorie", "Entité de rattachement", "Date", "Heure", "Statut de la demande", "Type de clôture", "E-mail", "Message / Action"
                    ];
            $data =  ($this->hotlineRepository->getHotlines());
           
            return $this->response(["header"=>$header, "data"=>$data]);
        } catch (Exception $ex) {
            return $this->error($ex);
        }
    }


}
