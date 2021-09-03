<?php

namespace App\Services;

class UserService
{
    static function getUser()
    {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        if(isset($_SESSION['user'])){
            return $_SESSION['user'];
        }else{
            return null;
        }
    }
}