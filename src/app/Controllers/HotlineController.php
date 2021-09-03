<?php

namespace App\Controllers;

use App\Manager\Hotline\HotlineManager;
use App\Models\HotlineEmail;
use App\Models\HotlineGroup;
use App\Models\HotlineTheme;
use App\Repository\Hotline\HotlineRepository;
use Database\DBConnection;
use Exception;

class HotlineController extends Controller
{
    private  $hotlineRepository;
    private  $hotlineManager;
    public function __construct(DBConnection $db, $roles = [])
    {
        parent::__construct($db, $roles);
        $this->hotlineRepository = new HotlineRepository($db);
        $this->hotlineManager = new HotlineManager($db);
    }

    public function index()
    {
        try {
            $options = $this->decode(file_get_contents("php://input"), true);
            return $this->response($this->hotlineRepository->getList($options));
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
            $options = $this->decode(file_get_contents("php://input"));
            $this->hotlineManager->create($options);
            return $this->response([
                'status' => true
            ]);
        } catch (Exception $ex) {
            return $this->error($ex);
        }
    }

    public function show($id, $sha)
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
}
