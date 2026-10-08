<?php
namespace App\Services;

use App\Services\FileManager;
use App\Exceptions\SheetException;

class FileUpload
{
    public function uploadSheetFiles($params)
    {
        $uploadedFiles = [];
        $fileManager   =   new FileManager();
        $fileManager->setExtensions($params['extensions']);
        $fileManager->setMaxSize($params['size']);
        $fileManager->setDir($params['dirpath']);


        $medias = [
            [
                'type' => 1,
                'value' => isset($_FILES['kits']) ? $_FILES['kits'] : null,
            ],
            [
                'type' => 2,
                'value' => isset($_FILES['privates']) ? $_FILES['privates'] : null,
            ],
            [
                'type' => 3,
                'value' => isset($_FILES['privates_enedis']) ? $_FILES['privates_enedis'] : null,
            ],
            [
                'type' => 4,
                'value' => isset($_FILES['privates_edf_enedis']) ? $_FILES['privates_edf_enedis'] : null,
            ]
        ];

        foreach ($medias as $media) {
            if ($media['value'] != null && isset($media['value']['name']) && !empty(array_filter($media['value']['name']))) {
                $data = $media['value'];
                foreach ($data['tmp_name'] as $key => $value) {
                    $file['tmp_name'] = $data['tmp_name'][$key];
                    $file['name'] = basename($data['name'][$key]);
                    $file['size'] = $data['size'][$key];

                    error_log(sprintf('[file-upload] Sauvegarde du fichier "%s" (%s octets) en cours...', $file['name'], $file['size']));

                    // Code d'erreur PHP natif ($_FILES[...]['error']) : un fichier
                    // rejeté par PHP lui-même (ex. dépasse upload_max_filesize/
                    // post_max_size) arrive ici avec un tmp_name vide/invalide —
                    // sans ce contrôle, il finissait en échec générique
                    // "Upload failed" côté FileManager, sans dire pourquoi.
                    $phpErrorCode = (int) ($data['error'][$key] ?? UPLOAD_ERR_OK);

                    if ($phpErrorCode !== UPLOAD_ERR_OK) {
                        throw SheetException::forSheetSave(sprintf(
                            "Échec de l'envoi du fichier '%s' : %s",
                            $file['name'], $this->uploadErrorCodeToMessage($phpErrorCode)
                        ));
                    }

                    if ($fileManager->uploadFile($file)) {
                        $suc_file  =   $fileManager->getUploadName();
                        $nodeId = null;
                        $uploadedFiles[] = ['file_name' => $file['name'], 'file_url' => $suc_file, 'file_size' => $file['size'], 'node_id' => $nodeId, 'type' => $media['type']];
                    } else {
                        throw SheetException::forSheetSave(sprintf(
                            "Échec de l'envoi du fichier '%s' : %s",
                            $file['name'], $fileManager->getMessage()
                        ));
                    }
                }
            }
        }

        return $uploadedFiles;
    }

    /**
     * Traduit un code d'erreur $_FILES[...]['error'] natif de PHP en message
     * clair — sans ça, un fichier rejeté avant même d'atteindre FileManager
     * (ex. trop volumineux pour upload_max_filesize) ne donnait aucune piste.
     */
    private function uploadErrorCodeToMessage(int $code): string
    {
        switch ($code) {
            case UPLOAD_ERR_OK:
                return 'OK';
            case UPLOAD_ERR_INI_SIZE:
                return 'fichier trop volumineux (dépasse upload_max_filesize côté serveur)';
            case UPLOAD_ERR_FORM_SIZE:
                return 'fichier trop volumineux (dépasse la limite MAX_FILE_SIZE du formulaire)';
            case UPLOAD_ERR_PARTIAL:
                return "le fichier n'a été que partiellement envoyé (upload interrompu)";
            case UPLOAD_ERR_NO_FILE:
                return 'aucun fichier envoyé';
            case UPLOAD_ERR_NO_TMP_DIR:
                return 'dossier temporaire manquant côté serveur';
            case UPLOAD_ERR_CANT_WRITE:
                return "échec d'écriture du fichier sur le disque côté serveur";
            case UPLOAD_ERR_EXTENSION:
                return 'upload bloqué par une extension PHP';
            default:
                return "erreur d'upload inconnue (code {$code})";
        }
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
