<?php

namespace App\Repository;

use App\Models\Media;
use Exception;

class MediaRepository extends AbstractRepository {

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
        $media = new Media();
        return $media->delete($id);
    }

}
