<?php

namespace App\Controllers;

use Core\Logger\{LoggerHandler as Logger, Level} ;
use \Core\Http\Request;
/**
 * Class FrontController
 * @package App\Controllers
 */
class FrontController extends Controller {

    /**
     * FrontController constructor.
     * 
     */
    public function __construct($roles = []) {
        parent::__construct($roles);
    }
    
    /**
     * 
     * @param mixed $input
     * @return void
     */
    public function Logs():void {
        $input = Request::input() ;
        (new Logger(FRONT_CHANNEL))->log("test front message hello", Level::INFO) ;
    }
}
