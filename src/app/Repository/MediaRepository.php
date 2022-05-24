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
        $filename =  basename($id . '_' . date('Ymdhis') . '.' . $e);
        if (!move_uploaded_file($file['tmp_name'], getenv("CONFIG_UPLOAD_PATH") . $filename)) {
          throw new Exception('fichier non enregistré');
        }


        (new Media())->add([
            'name' => $id,
            'file_code' => $filename,
            'file_name' => $name
        ]);

    }
   
}
