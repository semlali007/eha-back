<?php

namespace App\Controllers;

use App\Manager\SheetManager;
use Database\DBConnection;
use App\Repository\SheetRepository;
use App\Services\FileService;
use Core\Http\Request;

class SheetController extends Controller {

    private $sheetRepository;
    private $sheetManager;

    public function __construct(DBConnection $db, $roles = []) {
        parent::__construct($db, $roles);
        $this->sheetRepository = new SheetRepository($db);
        $this->sheetManager = new SheetManager($db);
    }

    public function index() {
        $options = $this->decode(file_get_contents("php://input"));
        return $this->response($this->sheetRepository->search($options));
    }

    public function show(int $id) {
        return $this->response(['sheet' => $this->sheetRepository->getDetails($id)]);
    }

    public function export() {
        $options = json_decode(file_get_contents("php://input"), true) ?? [];
        return $this->response(['sheets' => $this->sheetRepository->export($options)]);
    }

    public function update() {
        $sheetData = json_decode($_POST['model'], true);
        return $this->sheetManager->update($this->currentUser,$sheetData);
    }

    public function add() {
        $sheetData = json_decode($_POST['model'], true);
        return $this->sheetManager->add($this->currentUser,$sheetData);
    }

    public function download() {
        $options = $this->decode(file_get_contents("php://input"));
        return FileService::download($options);
    }

}
