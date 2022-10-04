<?php
/**
 * Ce fichier gére les constantes utilisés dans toute l'application
 */

define("HLK",$GLOBALS['configuration']['hlk']) ; // Global config HLK
// Logger Constants
define("HLK_CHANNEL",$GLOBALS['configuration']['logger']["channels"]["hlk"]) ;
define("APP_CHANNEL",$GLOBALS['configuration']['logger']["channels"]["app"]) ;
define("FHM_CHANNEL",$GLOBALS['configuration']['logger']["channels"]["fhm"]) ;
define("FRONT_CHANNEL",$GLOBALS['configuration']['logger']["channels"]["front"]) ;