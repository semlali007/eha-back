<?php

namespace Core\Logger;

use Core\Logger\Observer\DatabaseLogger;

/**
 * Description of LoggerHandler
 *
 * @author HGDDEDBN
 */
class LoggerHandler {
    
  /**
   * @var LoggerHandler
   * @access private
   * @static
   */
    private static $_instance = null;
    
   /** les channels qui existent dans le fichier app/config/logger.xml, Exemple APP/DB etc ... **/
    public string $channel; 
    
    /**
     * 
     * @param string $channel
     * @param int $level
     */
    function __construct(string $channel = 'DEFAULT') {

        $this->channel = $channel;
    }
    
   /**
    * Méthode qui crée l'unique instance de la classe
    * si elle n'existe pas encore puis la retourne.
    *
    * @param void
    * @return self|null
    */
   public static function getInstance(string $channel):?self {
 
     if(is_null(self::$_instance)) {
       self::$_instance = new LoggerHandler($channel);  
     }
 
     return self::$_instance;
   }

    /**
     * Lancer la création du log
     * @return void
     */
    public function log(string $message, int $level = Level::INFO): void {

        (new LoggerNotifier())
                ->attach(new Logger(new DatabaseLogger($this->channel)))
                ->send($message, $level); // Ecrire le message avec le level qui correspond
    }

}
