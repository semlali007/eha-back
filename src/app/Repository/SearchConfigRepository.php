<?php

namespace App\Repository;

use App\Models\Media;
use App\Models\SearchAssociated;
use App\Models\SearchLog;
use App\Models\SearchLogItem;
use App\Models\SearchNoList;
use App\Models\SearchSynonymous;
use App\Models\SearchWords;
use Exception;

class SearchConfigRepository extends AbstractRepository {

    public function getLogsList()
    {
        $log = new SearchLog();
        return
            $log->getList();
    }

    public function getIgnoredList()
    {
        $log = new SearchNoList();
        return
            $log->getList();
    }
    
    public function getAssociatedList()
    {
        $log = new SearchAssociated();
        $syn = new SearchSynonymous();
          $ass =   $log->getList();
          foreach($ass as $key=>$associated){
            $ass[$key]['synonymous'] = $syn->getList($associated['id']);
          }
          return $ass;
    }


    public function get(mixed $id):mixed {
        $media = new Media();
        return $media->get($id);
    }

    public function getList()
    {
        $area = new Media();
        return
            $area->getList();
    }

    public function create($options, $file)
    {
        $e = explode('.', $file['name']);
        $e = end($e);
        $filename =  basename(date('Ymdhis') . '.' . $e);
        if (!move_uploaded_file($file['tmp_name'], getenv("CONFIG_UPLOAD_PATH") . $filename)) {
            throw new Exception('fichier non enregistré');
        }
       // var_dump($filename);exit;
        (new Media())->add([
            'name' => $options['title'],
            'file_name' => $filename,
        ]);
    }
    public function addIgnored($options)
    {
        $log = new SearchNoList();
        return
            $log->add($options['title']);

    }
    public function addSynonymous($options)
    {
        $log = new SearchSynonymous();
        return
            $log->add($options);

    }
    

    public function addAssociated($options)
    {
        $log = new SearchAssociated();
        return
            $log->add($options['title']);

    }

    public function update($id, $options, $file)
    {
        $e = explode('.', $file['name']);
        $e = end($e);
        $filename =  basename(date('Ymdhis') . '.' . $e);
        if (!move_uploaded_file($file['tmp_name'], getenv("CONFIG_UPLOAD_PATH") . $filename)) {
            throw new Exception('fichier non enregistré');
        }
        (new Media())->update($id, [
            'name' => $options['title'],
            'file_name' => $filename,
        ]);
    }
   
    
    public function delete(mixed $id):mixed {
        $media = new SearchNoList();
        return $media->delete($id);
    }

    public function getWordslist()
    {
        $area = new SearchLogItem();
        return
            $area->getList();
    }
    
    

    public function deleteSynonymous(mixed $id):mixed {
        $media = new SearchSynonymous();
        return $media->delete($id);
    }

    
    public function updateAssociated($id, $options)
    {
        $log = new SearchAssociated();
        return
            $log->update($id, $options['title']);

    }

    public function deleteAssociated(mixed $id):mixed {
        $media = new SearchAssociated();
        return $media->delete($id);
    }
    
}
