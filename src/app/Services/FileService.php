<?php

namespace App\Services;
use Core\Http\Request ;

class FileService
{
    public static function download($options)
    {
        if (isset($options["file"])) {
            $filepath = getenv("CONFIG_UPLOAD_PATH") .DIRECTORY_SEPARATOR.urldecode($options["file"]);
            if (file_exists($filepath)) {
                header('Content-Description: File Transfer');
                header('Content-Type: application/octet-stream');
                header('Content-Disposition: attachment; filename="' . basename($filepath) . '"');
                header('Expires: 0');
                header('Cache-Control: must-revalidate');
                header('Pragma: public');
                header('Content-Length: ' . filesize($filepath));
                flush(); // Flush system output buffer
                return readfile($filepath); 
            } 
        }
    }
}
