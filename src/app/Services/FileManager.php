<?php

namespace App\Services;

class FileManager
{
    private $destinationPath;
    private $errorMessage;
    private $extensions;
    private $maxSize;
    private $uploadName;
    public $name='Uploader';

    public function setDir($path){
        $this->destinationPath  =   $path;
    }

    public function setMaxSize($sizeMB){
        $this->maxSize  =   $sizeMB * (1024*1024);
    }

    public function setExtensions($options){
        $this->extensions   =   $options;
    }

    public function setMessage($message){
        $this->errorMessage =   $message;
    }

    public function getMessage(){
        return $this->errorMessage;
    }

    public function getUploadName(){
        return $this->uploadName;
    }

    public function uploadFile($fileBrowse){
        $result =   false;
        $size   =   $fileBrowse["size"];
        $name   =   basename($fileBrowse["name"]);
        $ext    =   strtolower(pathinfo($name,PATHINFO_EXTENSION));
        
        // Valider que l'extension ne contient que des caractères alphanumériques
        if (!preg_match('/^[a-zA-Z0-9]+$/', $ext)) {
            $this->setMessage("Invalid file extension !");
            return false;
        }

        $this->uploadName = bin2hex(random_bytes(10)) . "." . $ext;

        if(empty($name))
        {
            $this->setMessage("File not selected ");
        }
        else if(in_array($ext,$this->extensions))
        {
            // Limite applicative — jamais vérifiée avant (setMaxSize() était
            // appelée mais $this->maxSize n'était lu nulle part). À distinguer
            // de la limite PHP (upload_max_filesize/post_max_size), qui, elle,
            // s'applique AVANT que le fichier n'arrive jusqu'ici.
            if ($this->maxSize !== null && $size > $this->maxSize) {
                $this->setMessage(sprintf(
                    'File too large (%.1f MB, max %.1f MB) !',
                    $size / (1024 * 1024),
                    $this->maxSize / (1024 * 1024)
                ));
                return false;
            }

            if(!is_dir($this->destinationPath))
                mkdir($this->destinationPath, 0755, true);
        
            // Vérifier que le chemin destination résolu reste dans le répertoire prévu
            $realDestination = realpath($this->destinationPath);
            if ($realDestination === false) {
                $this->setMessage("Invalid destination path !");
                return false;
            }

            $targetFile = $realDestination . DIRECTORY_SEPARATOR . $this->uploadName;

            if(file_exists($targetFile))
                $this->setMessage("File already exists. !");
            else if(!is_writable($realDestination))
                $this->setMessage("Destination is not writable !");
            else
            {
                // Essayer d'abord move_uploaded_file (pour les vrais uploads HTTP)
                if(move_uploaded_file($fileBrowse["tmp_name"], $targetFile))
                {
                    $result =   true;
                }
                // Si échec, essayer copy() pour les fichiers temporaires créés manuellement (API)
                // Vérifier que tmp_name est dans le répertoire temporaire système
                else if(file_exists($fileBrowse["tmp_name"])
                    && $this->isInTempDirectory($fileBrowse["tmp_name"])
                    && copy($fileBrowse["tmp_name"], $targetFile))
                {
                    $result = true;
                    // Nettoyer le fichier temporaire après copie
                    unlink($fileBrowse["tmp_name"]);
                }
                else
                {
                    $this->setMessage("Upload failed , try later !");
                }
            }
        }
        else
        {
            $this->setMessage("Invalid file format !");
        }
        return $result;
    }

    /**
     * Vérifie que le fichier se trouve dans le répertoire temporaire système.
     * Protection contre la lecture de fichiers arbitraires via le fallback copy().
     *
     * @param string $filePath
     * @return bool
     */
    private function isInTempDirectory($filePath)
    {
        $tempDir = realpath(sys_get_temp_dir());
        $realFilePath = realpath($filePath);
        if ($tempDir === false || $realFilePath === false) {
            return false;
        }
        return strpos($realFilePath, $tempDir) === 0;
    }

    public function deleteUploaded($uploadName)
    {
        $path = $this->destinationPath . '/' .  basename($uploadName);
        if (file_exists($path)) {
            return  @unlink($path);
        } else {
            return false;
        }
    }
}
