<?php

namespace Core\Logger;

use Core\Logger\Observer\DatabaseLogger;

/**
 * Description of LoggerHandler
 *
 * @author HGDDEDBN
 */
class LoggerHandler {

    public string $channel; // les channels qui existent dans le fichier app/config/logger.xml
    //Exemple APP/DB etc ...
    public int $level = Level::INFO;

    /**
     * 
     * @param string $channel
     * @param int $level
     */
    function __construct(string $channel, int $level) {

        $this->channel = $channel;
        $this->level = $level;
    }

    /**
     * Lancer la création du log
     * @return void
     */
    public function log(string $message): void {

        (new LoggerNotifier())
                ->attach(new Logger(new DatabaseLogger($this->channel)))
                ->send($message, $this->level); // Ecrire le message avec le level qui correspond
    }

}
