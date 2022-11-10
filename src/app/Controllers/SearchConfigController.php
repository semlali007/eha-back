<?php

namespace App\Controllers;

use App\Repository\SearchConfigRepository;
use App\Services\FileService;
use Exception;

class SearchConfigController extends Controller
{
    private  $searchConfigRepository;
    
    public function __construct($roles = [])
    {
        parent::__construct($roles);
        $this->searchConfigRepository = new SearchConfigRepository();
    }

    public function getLogsList()
    {
        return $this->response($this->searchConfigRepository->getLogsList());
    }

    public function getAssociatedList()
    {
        return $this->response($this->searchConfigRepository->getAssociatedList());
    }

    public function getIgnoredList()
    {
        return $this->response($this->searchConfigRepository->getIgnoredList());
    }

    public function getList()
    {
        return $this->response($this->mediaRepository->getList());
    }

    public function edit($id)
    {
        return $this->response($this->mediaRepository->get($id));
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

    public function save($id)
    {
        try {
            if (empty($_FILES['file'])) {
                throw new Exception('le fichier est obligatoire');
            }

            return $this->response($this->mediaRepository->update($id, $_POST, $_FILES['file']));
        } catch (Exception $ex) {
            return $this->error($ex);
        }
    }
    public function delete($id)
    {
        return $this->response($this->mediaRepository->delete($id));
    }

    public function addIgnored()
    {
        try {
            $options = $this->decode(file_get_contents("php://input"));
            return $this->response($this->searchConfigRepository->addIgnored($options));
        } catch (Exception $ex) {
            return $this->error($ex);
        }
    }
    public function addAssociated()
    {
        try {
            $options = $this->decode(file_get_contents("php://input"));
            return $this->response($this->searchConfigRepository->addAssociated($options));
        } catch (Exception $ex) {
            return $this->error($ex);
        }
    }
    public function addSynonymous()
    {
        try {
            $options = $this->decode(file_get_contents("php://input"));
            return $this->response($this->searchConfigRepository->addSynonymous($options));
        } catch (Exception $ex) {
            return $this->error($ex);
        }
    }

    public function deleteIgnored($id)
    {
        return $this->response($this->searchConfigRepository->delete($id));
    }

    public function wordslist()
    {
        return $this->response($this->searchConfigRepository->getWordslist());
    }

    
    public function deleteSynonymous($id)
    {
        return $this->response($this->searchConfigRepository->deleteSynonymous($id));
    }

    public function updateAssociated($id)
    {
        try {
            $options = $this->decode(file_get_contents("php://input"));
            return $this->response($this->searchConfigRepository->updateAssociated($id,$options));
        } catch (Exception $ex) {
            return $this->error($ex);
        }
    }
    
    public function deleteAssociated($id)
    {
        return $this->response($this->searchConfigRepository->deleteAssociated($id));
    }
     

}
