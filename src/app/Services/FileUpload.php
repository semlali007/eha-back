<?php
namespace App\Services;

use App\Services\FileManager;
use App\Exceptions\SheetException;

class FileUpload
{
    public function uploadSheetFiles($files, $params)
    {
        $uploadedFiles = [];
        $fileManager   =   new FileManager();
        $fileManager->setExtensions($params['extensions']);
        $fileManager->setMaxSize($params['size']);
        $fileManager->setDir($params['dirpath']);


        $medias = [
            [
                'type' => 1,
                'value' => isset($files['kits']) ? $files['kits'] : null,
            ],
            [
                'type' => 2,
                'value' => isset($files['privates']) ? $files['privates'] : null,
            ],
            [
                'type' => 3,
                'value' => isset($files['privates_enedis']) ? $files['privates_enedis'] : null,
            ],
            [
                'type' => 4,
                'value' => isset($files['privates_edf_enedis']) ? $files['privates_edf_enedis'] : null,
            ]
        ];

        foreach ($medias as $media) {
            if ($media['value'] != null && isset($media['value']['name']) && !empty(array_filter($media['value']['name']))) {
                $data = $media['value'];
                foreach ($data['tmp_name'] as $key => $value) {
                    $file['tmp_name'] = $data['tmp_name'][$key];
                    $file['name'] = $data['name'][$key];
                    $file['size'] = $data['size'][$key];

                    if ($fileManager->uploadFile($file)) {
                        $suc_file  =   $fileManager->getUploadName();
                        $nodeId = null;
                        $uploadedFiles[] = ['file_name' => $file['name'], 'file_url' => $suc_file, 'file_size' => $file['size'], 'node_id' => $nodeId, 'type' => $media['type']];
                    } else {
                        throw SheetException::forSheetSave($fileManager->getMessage());
                    }
                }
            }
        }

        return $uploadedFiles;
    }

    public function deleteFilesFromFolder($folder, $files)
    {
        $errorMessage = [];
        if (is_array($files) && count($files) > 0) {
            $fileManager = new FileManager();
            $fileManager->setDir($folder);
            foreach ($files as $uploadedFile) {
                if (!$fileManager->deleteUploaded($uploadedFile['file_url'])) {
                    $errorMessage[] = $uploadedFile['file_url'] . ' non supprimé. ';
                }
            }
        }
        return $errorMessage;
    }
}
