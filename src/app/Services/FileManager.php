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
        $name   =   $fileBrowse["name"];
        $ext    =   pathinfo($name,PATHINFO_EXTENSION);
        
        $this->uploadName = bin2hex(random_bytes(10)) . "." . $ext;

        if(empty($name))
        {
            $this->setMessage("File not selected ");
        }
        else if(in_array($ext,$this->extensions))
        {
            if(!is_dir($this->destinationPath))
                mkdir($this->destinationPath);
        
            if(file_exists($this->destinationPath.'/'.$this->uploadName))
                $this->setMessage("File already exists. !");
            else if(!is_writable($this->destinationPath))
                $this->setMessage("Destination is not writable !");
            else
            {
                if(move_uploaded_file($fileBrowse["tmp_name"],$this->destinationPath.'/'.$this->uploadName))
                {
                    $result =   true;
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
