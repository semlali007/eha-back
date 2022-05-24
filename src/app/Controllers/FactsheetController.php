<?php

namespace App\Controllers;

use App\Repository\MediaRepository;
use App\Services\FileService;
use Exception;

class FactsheetController extends Controller
{
    private  $mediaRepository;
    
    public function __construct($roles = [])
    {
        parent::__construct($roles);
        $this->mediaRepository = new MediaRepository();
    }

    public function getList()
    {
        return $this->response($this->mediaRepository->getList());
    }

    public function show(string $code)
    {
        return $this->response($this->providerRepository->getProvider($code));
    }

    public function add()
    {
        $options = json_decode(file_get_contents("php://input"), true) ?? [];
        return  $this->response($this->mediaRepository->create($options));
    }

    public function upload()
    {
        try {
            if (empty($_FILES['file'])) {
                throw new Exception('le fichier est obligatoire');
            }

            return $this->response($this->mediaRepository->create($_POST, $_FILES['file']));
        } catch (Exception $ex) {
            return $this->error($ex);
        }
    }

    public function download(){
        $options = $this->decode(file_get_contents("php://input"));
        $media = $this->mediaRepository->get($options['id']);
        return FileService::download(['file'=>$media['file_name']]);
    }

}
